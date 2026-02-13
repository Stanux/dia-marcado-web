<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\GuestCheckin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestCheckinController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GuestCheckin::class);

        $validated = $request->validate([
            'guest_id' => 'required|uuid|exists:guests,id',
            'event_id' => 'nullable|uuid|exists:guest_events,id',
            'method' => 'nullable|string|max:20',
            'device_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $guest = Guest::findOrFail($validated['guest_id']);
        $weddingId = $request->user()->current_wedding_id;
        if ($guest->wedding_id !== $weddingId) {
            return response()->json(['message' => 'Convidado inválido para este casamento.'], 422);
        }

        if (!empty($validated['event_id'])) {
            $event = \App\Models\GuestEvent::find($validated['event_id']);
            if ($event && $event->wedding_id !== $weddingId) {
                return response()->json(['message' => 'Evento inválido para este casamento.'], 422);
            }
        }

        $checkin = GuestCheckin::create([
            ...$validated,
            'checked_in_at' => now(),
            'operator_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $checkin, 'guest' => $guest]);
    }
}
