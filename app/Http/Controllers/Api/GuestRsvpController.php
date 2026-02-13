<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\GuestEvent;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\GuestRsvp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestRsvpController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GuestRsvp::class);

        $validated = $request->validate([
            'guest_id' => 'required|uuid|exists:guests,id',
            'event_id' => 'required|uuid|exists:guest_events,id',
            'status' => 'required|string|max:20',
            'responses' => 'nullable|array',
        ]);

        $event = GuestEvent::findOrFail($validated['event_id']);
        $guest = Guest::findOrFail($validated['guest_id']);
        if ($guest->wedding_id !== $event->wedding_id) {
            return response()->json(['message' => 'Convidado não pertence a este evento.'], 422);
        }

        $rsvp = GuestRsvp::updateOrCreate(
            ['guest_id' => $validated['guest_id'], 'event_id' => $validated['event_id']],
            [
                'status' => $validated['status'],
                'responses' => $validated['responses'] ?? null,
                'responded_at' => now(),
                'updated_by' => $request->user()->id,
            ]
        );

        $guest->status = $validated['status'];
        $guest->save();

        return response()->json(['data' => $rsvp]);
    }

    public function publicStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'nullable|string',
            'event_id' => 'required|uuid|exists:guest_events,id',
            'status' => 'required|string|max:20',
            'responses' => 'nullable|array',
            'guest' => 'required|array',
            'guest.name' => 'required|string|max:255',
            'guest.email' => 'nullable|email|max:255',
            'guest.phone' => 'nullable|string|max:30',
            'guest.is_child' => 'nullable|boolean',
            'household_name' => 'nullable|string|max:255',
        ]);

        $event = GuestEvent::withoutGlobalScopes()->findOrFail($validated['event_id']);
        if (!$event->is_active) {
            return response()->json(['message' => 'Evento indisponível para RSVP.'], 422);
        }
        $weddingId = $event->wedding_id;
        $wedding = $event->wedding;
        $rsvpAccess = $wedding?->settings['rsvp_access'] ?? 'open';

        $guest = null;
        if (!empty($validated['token'])) {
            $invite = GuestInvite::where('token', $validated['token'])->first();
            if (!$invite) {
                return response()->json(['message' => 'Convite inválido.'], 404);
            }
            if ($invite->expires_at && $invite->expires_at->isPast()) {
                return response()->json(['message' => 'Convite expirado.'], 410);
            }
            $inviteHousehold = GuestHousehold::withoutGlobalScopes()->find($invite->household_id);
            if ($inviteHousehold && $inviteHousehold->wedding_id !== $weddingId) {
                return response()->json(['message' => 'Convite não pertence a este evento.'], 422);
            }

            if ($invite->guest_id) {
                $guest = Guest::withoutGlobalScopes()->find($invite->guest_id);
            } elseif ($invite->household_id) {
                $household = GuestHousehold::withoutGlobalScopes()->find($invite->household_id);
                $guest = Guest::create([
                    'wedding_id' => $weddingId,
                    'household_id' => $household?->id,
                    'name' => $validated['guest']['name'],
                    'email' => $validated['guest']['email'] ?? null,
                    'phone' => $validated['guest']['phone'] ?? null,
                    'is_child' => $validated['guest']['is_child'] ?? false,
                ]);
            }

            if ($guest && $guest->wedding_id !== $weddingId) {
                return response()->json(['message' => 'Convidado não pertence a este evento.'], 422);
            }

            $invite->used_at = now();
            $invite->status = 'opened';
            $invite->save();
        }

        if (!$guest && $rsvpAccess === 'restricted') {
            $email = $validated['guest']['email'] ?? null;
            $phone = $validated['guest']['phone'] ?? null;

            if (!$email && !$phone) {
                return response()->json([
                    'message' => 'Informe e-mail ou telefone para validar o RSVP.',
                ], 422);
            }

            $guest = Guest::withoutGlobalScopes()
                ->where('wedding_id', $weddingId)
                ->where(function ($query) use ($email, $phone) {
                    if ($email) {
                        $query->orWhere('email', $email);
                    }
                    if ($phone) {
                        $query->orWhere('phone', $phone);
                    }
                })
                ->first();

            if (!$guest) {
                return response()->json([
                    'message' => 'RSVP restrito. Somente convidados cadastrados podem responder.',
                ], 403);
            }
        }

        if (!$guest) {
            $household = GuestHousehold::create([
                'wedding_id' => $weddingId,
                'name' => $validated['household_name'] ?? $validated['guest']['name'],
            ]);
            $guest = Guest::create([
                'wedding_id' => $weddingId,
                'household_id' => $household->id,
                'name' => $validated['guest']['name'],
                'email' => $validated['guest']['email'] ?? null,
                'phone' => $validated['guest']['phone'] ?? null,
                'is_child' => $validated['guest']['is_child'] ?? false,
            ]);
        }

        $rsvp = GuestRsvp::updateOrCreate(
            ['guest_id' => $guest->id, 'event_id' => $validated['event_id']],
            [
                'status' => $validated['status'],
                'responses' => $validated['responses'] ?? null,
                'responded_at' => now(),
            ]
        );

        $guest->status = $validated['status'];
        $guest->save();

        return response()->json(['data' => $rsvp]);
    }
}
