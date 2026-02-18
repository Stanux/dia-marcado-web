<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\GuestCheckin;
use App\Services\Guests\GuestCheckinService;
use App\Services\Guests\GuestCheckinQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestCheckinController extends Controller
{
    public function __construct(
        private readonly GuestCheckinService $guestCheckinService,
        private readonly GuestCheckinQrService $guestCheckinQrService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GuestCheckin::class);

        $validated = $request->validate([
            'event_id' => 'nullable|uuid|exists:guest_events,id',
            'method' => ['nullable', 'string', 'max:20', Rule::in(['qr', 'manual'])],
            'search' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        try {
            $result = $this->guestCheckinService->listForWedding(
                weddingId: (string) $request->user()->current_wedding_id,
                filters: $validated,
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $result['items'],
            'summary' => $result['summary'],
        ]);
    }

    public function qrPayload(Request $request, Guest $guest): JsonResponse
    {
        $this->authorize('view', $guest);

        $weddingId = (string) $request->user()->current_wedding_id;
        if ((string) $guest->wedding_id !== $weddingId) {
            return response()->json(['message' => 'Convidado invalido para este casamento.'], 422);
        }

        $payload = $this->guestCheckinQrService->forGuest($guest);

        return response()->json([
            'data' => [
                'guest_id' => $guest->id,
                'guest_name' => $guest->name,
                'token' => $payload['token'],
                'qr_content' => $payload['qr_content'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GuestCheckin::class);

        $validated = $request->validate([
            'guest_id' => 'required|uuid|exists:guests,id',
            'event_id' => 'nullable|uuid|exists:guest_events,id',
            'method' => ['nullable', 'string', 'max:20', Rule::in(['qr', 'manual'])],
            'device_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->guestCheckinService->record(
                weddingId: (string) $request->user()->current_wedding_id,
                guestId: (string) $validated['guest_id'],
                eventId: $validated['event_id'] ?? null,
                method: (string) ($validated['method'] ?? 'qr'),
                deviceId: $validated['device_id'] ?? null,
                notes: $validated['notes'] ?? null,
                operatorId: $request->user()->id,
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $result['checkin'],
            'guest' => $result['guest'],
            'event' => $result['event'],
            'meta' => [
                'created' => $result['created'],
                'duplicate' => $result['duplicate'],
                'message' => $result['duplicate']
                    ? 'Check-in ja registrado para este convidado.'
                    : 'Check-in registrado com sucesso.',
            ],
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        $this->authorize('create', GuestCheckin::class);

        $validated = $request->validate([
            'qr_code' => 'required|string|max:5000',
            'event_id' => 'nullable|uuid|exists:guest_events,id',
            'device_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $weddingId = (string) $request->user()->current_wedding_id;

        try {
            $guestId = $this->guestCheckinQrService->resolveGuestId(
                rawCode: (string) $validated['qr_code'],
                weddingId: $weddingId,
            );

            $result = $this->guestCheckinService->record(
                weddingId: $weddingId,
                guestId: $guestId,
                eventId: $validated['event_id'] ?? null,
                method: 'qr',
                deviceId: $validated['device_id'] ?? null,
                notes: $validated['notes'] ?? null,
                operatorId: $request->user()->id,
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $result['checkin'],
            'guest' => $result['guest'],
            'event' => $result['event'],
            'meta' => [
                'created' => $result['created'],
                'duplicate' => $result['duplicate'],
                'message' => $result['duplicate']
                    ? 'Check-in ja registrado para este convidado.'
                    : 'Check-in por QR registrado com sucesso.',
            ],
        ]);
    }
}
