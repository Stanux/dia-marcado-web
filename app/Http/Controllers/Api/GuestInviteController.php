<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Services\GuestInviteNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestInviteController extends Controller
{
    public function store(Request $request, GuestHousehold $household): JsonResponse
    {
        $this->authorize('create', GuestInvite::class);
        $weddingId = $request->user()->current_wedding_id;
        if ($household->wedding_id !== $weddingId) {
            return response()->json(['message' => 'Household inválido para este casamento.'], 422);
        }

        $validated = $request->validate([
            'guest_id' => 'nullable|uuid|exists:guests,id',
            'channel' => 'nullable|string|max:20',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        if (!empty($validated['guest_id'])) {
            $guest = \App\Models\Guest::find($validated['guest_id']);
            if ($guest && $guest->wedding_id !== $weddingId) {
                return response()->json(['message' => 'Convidado inválido para este casamento.'], 422);
            }
        }

        $expiresAt = null;
        if (!empty($validated['expires_in_days'])) {
            $expiresAt = now()->addDays((int) $validated['expires_in_days']);
        }

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $validated['guest_id'] ?? null,
            'created_by' => $request->user()->id,
            'token' => Str::random(48),
            'channel' => $validated['channel'] ?? 'email',
            'status' => 'sent',
            'expires_at' => $expiresAt,
        ]);

        app(GuestInviteNotificationService::class)->send($invite);

        return response()->json(['data' => $invite], 201);
    }

    public function reissue(Request $request, GuestInvite $invite): JsonResponse
    {
        $this->authorize('update', $invite);

        $validated = $request->validate([
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $invite->token = Str::random(48);
        $invite->status = 'sent';
        $invite->used_at = null;
        $invite->expires_at = !empty($validated['expires_in_days'])
            ? now()->addDays((int) $validated['expires_in_days'])
            : null;
        $invite->save();

        app(GuestInviteNotificationService::class)->send($invite);

        return response()->json(['data' => $invite]);
    }
}
