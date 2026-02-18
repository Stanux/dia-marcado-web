<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Services\GuestInviteNotificationService;
use App\Services\Guests\InviteBulkActionService;
use App\Services\Guests\InviteManagementService;
use App\Services\Guests\InviteObservabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestInviteController extends Controller
{
    public function __construct(
        private readonly GuestInviteNotificationService $notificationService,
        private readonly InviteManagementService $inviteManagementService,
        private readonly InviteBulkActionService $inviteBulkActionService,
        private readonly InviteObservabilityService $inviteObservabilityService,
    ) {}

    public function store(Request $request, GuestHousehold $household): JsonResponse
    {
        $this->authorize('create', GuestInvite::class);
        $weddingId = $request->user()->current_wedding_id;
        if ($household->wedding_id !== $weddingId) {
            return response()->json(['message' => 'Household inválido para este casamento.'], 422);
        }

        $validated = $request->validate([
            'guest_id' => 'nullable|uuid|exists:guests,id',
            'channel' => ['nullable', 'string', 'max:20', Rule::in(['email', 'whatsapp', 'sms'])],
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'max_uses' => 'nullable|integer|min:1|max:1000',
        ]);

        if (!empty($validated['guest_id'])) {
            $guest = \App\Models\Guest::find($validated['guest_id']);
            if ($guest && $guest->wedding_id !== $weddingId) {
                return response()->json(['message' => 'Convidado inválido para este casamento.'], 422);
            }
        }

        try {
            $invite = $this->inviteManagementService->createForHousehold(
                household: $household,
                data: $validated,
                actorId: $request->user()->id,
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $this->notificationService->send($invite);

        return response()->json(['data' => $invite], 201);
    }

    public function reissue(Request $request, GuestInvite $invite): JsonResponse
    {
        $this->authorize('update', $invite);

        $validated = $request->validate([
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'max_uses' => 'nullable|integer|min:1|max:1000',
        ]);

        try {
            $invite = $this->inviteManagementService->reissue(
                invite: $invite,
                data: $validated,
                actorId: $request->user()->id,
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $this->notificationService->send($invite);

        return response()->json(['data' => $invite]);
    }

    public function revoke(Request $request, GuestInvite $invite): JsonResponse
    {
        $this->authorize('update', $invite);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $invite = $this->inviteManagementService->revoke(
                invite: $invite,
                reason: $validated['reason'] ?? null,
                actorId: $request->user()->id,
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $invite]);
    }

    public function bulkReissue(Request $request): JsonResponse
    {
        $this->authorize('create', GuestInvite::class);

        $validated = $request->validate([
            'invite_ids' => ['required', 'array', 'min:1', 'max:200'],
            'invite_ids.*' => ['required', 'uuid', 'distinct', 'exists:guest_invites,id'],
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'max_uses' => 'nullable|integer|min:1|max:1000',
        ]);

        $invites = $this->resolveBulkInvites($request, $validated['invite_ids']);

        if ($invites->count() !== count($validated['invite_ids'])) {
            return response()->json([
                'message' => 'Alguns convites não foram encontrados ou não pertencem ao contexto permitido.',
            ], 422);
        }

        foreach ($invites as $invite) {
            $this->authorize('update', $invite);
        }

        $summary = $this->inviteBulkActionService->reissue(
            invites: $invites,
            data: $validated,
            actorId: $request->user()->id,
        );

        return response()->json(['data' => $summary]);
    }

    public function bulkRevoke(Request $request): JsonResponse
    {
        $this->authorize('create', GuestInvite::class);

        $validated = $request->validate([
            'invite_ids' => ['required', 'array', 'min:1', 'max:200'],
            'invite_ids.*' => ['required', 'uuid', 'distinct', 'exists:guest_invites,id'],
            'reason' => 'nullable|string|max:500',
        ]);

        $invites = $this->resolveBulkInvites($request, $validated['invite_ids']);

        if ($invites->count() !== count($validated['invite_ids'])) {
            return response()->json([
                'message' => 'Alguns convites não foram encontrados ou não pertencem ao contexto permitido.',
            ], 422);
        }

        foreach ($invites as $invite) {
            $this->authorize('update', $invite);
        }

        $summary = $this->inviteBulkActionService->revoke(
            invites: $invites,
            reason: $validated['reason'] ?? null,
            actorId: $request->user()->id,
        );

        return response()->json(['data' => $summary]);
    }

    public function timeline(Request $request, GuestInvite $invite): JsonResponse
    {
        $this->authorize('update', $invite);

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($validated['limit'] ?? 50);

        $events = $this->inviteObservabilityService->timelineForInvite($invite, $limit)
            ->map(fn (array $event): array => [
                'occurred_at' => $event['occurred_at']?->toIso8601String(),
                'source' => $event['source'],
                'title' => $event['title'],
                'details' => $event['details'],
            ])
            ->values();

        return response()->json([
            'data' => [
                'invite_id' => $invite->id,
                'events' => $events,
            ],
        ]);
    }

    /**
     * @param array<int, string> $inviteIds
     */
    private function resolveBulkInvites(Request $request, array $inviteIds): EloquentCollection
    {
        $query = GuestInvite::query()
            ->whereIn('id', $inviteIds)
            ->with(['household']);

        if (!$request->user()->isAdmin()) {
            $weddingId = $request->user()->current_wedding_id;
            $query->whereHas('household', function (Builder $builder) use ($weddingId): void {
                $builder->withoutGlobalScopes()->where('wedding_id', $weddingId);
            });
        }

        return $query->get();
    }
}
