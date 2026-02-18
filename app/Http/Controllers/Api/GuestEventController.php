<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestEvent;
use App\Services\Guests\GuestEventHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestEventController extends Controller
{
    public function __construct(
        private readonly GuestEventHistoryService $guestEventHistoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GuestEvent::class);
        $weddingId = $request->user()->current_wedding_id;

        $events = GuestEvent::where('wedding_id', $weddingId)
            ->orderBy('event_at')
            ->get();

        return response()->json(['data' => $events]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GuestEvent::class);
        $weddingId = $request->user()->current_wedding_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100',
            'event_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'rules' => 'nullable|array',
            'questions' => 'nullable|array',
        ]);

        $event = GuestEvent::create([
            ...$validated,
            'wedding_id' => $weddingId,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $event], 201);
    }

    public function show(GuestEvent $event): JsonResponse
    {
        $this->authorize('view', $event);

        return response()->json(['data' => $event]);
    }

    public function update(Request $request, GuestEvent $event): JsonResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:100',
            'event_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'rules' => 'nullable|array',
            'questions' => 'nullable|array',
        ]);

        $event->update($validated);

        return response()->json(['data' => $event]);
    }

    public function destroy(GuestEvent $event): JsonResponse
    {
        $this->authorize('delete', $event);
        $event->delete();

        return response()->json(['message' => 'Evento removido com sucesso.']);
    }

    public function history(Request $request, GuestEvent $event): JsonResponse
    {
        $this->authorize('view', $event);

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($validated['limit'] ?? 50);

        $events = $this->guestEventHistoryService->timelineForEvent($event, $limit)
            ->map(fn (array $row): array => [
                'occurred_at' => $row['occurred_at']?->toIso8601String(),
                'title' => $row['title'],
                'details' => $row['details'],
                'actor' => $row['actor'],
            ])
            ->values();

        return response()->json([
            'data' => [
                'event_id' => $event->id,
                'events' => $events,
            ],
        ]);
    }
}
