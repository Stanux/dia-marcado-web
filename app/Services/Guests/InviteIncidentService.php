<?php

namespace App\Services\Guests;

use App\Models\GuestInvite;
use App\Models\GuestMessage;
use App\Services\GuestInviteNotificationService;
use Illuminate\Support\Collection;

class InviteIncidentService
{
    public function __construct(
        private readonly GuestInviteNotificationService $notificationService,
        private readonly GuestAuditLogService $guestAuditLogService,
    ) {}

    /**
     * @return array{
     *   failed_messages_total:int,
     *   failed_invites_total:int,
     *   failed_channels:Collection<int, array{
     *      channel:string,
     *      failed_messages:int,
     *      impacted_invites:int,
     *      last_failure_at:\Illuminate\Support\Carbon|null
     *   }>,
     *   failed_queue:Collection<int, array{
     *      invite_id:string,
     *      household:string,
     *      guest:string,
     *      channel:string,
     *      invite_status:string,
     *      failed_at:\Illuminate\Support\Carbon|null,
     *      error:string,
     *      failed_attempts:int,
     *      can_retry:bool,
     *      retry_block_reason:?string
     *   }>
     * }
     */
    public function forWedding(
        string $weddingId,
        string $range = '30d',
        int $queueLimit = 20,
        int $sampleLimit = 500,
    ): array {
        $rangeStart = $this->resolveRangeStart($range);

        $failedMessagesQuery = GuestMessage::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->where('status', 'failed');

        if ($rangeStart) {
            $failedMessagesQuery->where('created_at', '>=', $rangeStart);
        }

        $failedMessagesTotal = (int) (clone $failedMessagesQuery)->count();

        $failedMessages = (clone $failedMessagesQuery)
            ->with([
                'logs' => fn ($query) => $query->orderByDesc('occurred_at'),
            ])
            ->orderByDesc('created_at')
            ->limit(max(1, $sampleLimit))
            ->get();

        $failedMessagesWithInvite = $failedMessages
            ->map(function (GuestMessage $message): ?array {
                $inviteId = $this->extractInviteId($message);
                if (!$inviteId) {
                    return null;
                }

                return [
                    'invite_id' => $inviteId,
                    'message' => $message,
                ];
            })
            ->filter()
            ->values();

        $inviteIds = $failedMessagesWithInvite
            ->pluck('invite_id')
            ->filter()
            ->unique()
            ->values();

        $failedInvitesTotal = $inviteIds->count();

        $invites = GuestInvite::query()
            ->with(['household:id,name', 'guest:id,name'])
            ->whereIn('id', $inviteIds)
            ->get()
            ->keyBy('id');

        $attemptsByInvite = $failedMessagesWithInvite
            ->groupBy('invite_id')
            ->map(fn (Collection $items): int => $items->count());

        $failedChannels = $failedMessagesWithInvite
            ->groupBy(fn (array $item): string => (string) ($item['message']->channel ?: 'unknown'))
            ->map(function (Collection $items, string $channel): array {
                $inviteCount = $items
                    ->pluck('invite_id')
                    ->filter()
                    ->unique()
                    ->count();

                $lastFailureAt = $items
                    ->pluck('message.created_at')
                    ->filter()
                    ->sortDesc()
                    ->first();

                return [
                    'channel' => $channel,
                    'failed_messages' => $items->count(),
                    'impacted_invites' => $inviteCount,
                    'last_failure_at' => $lastFailureAt,
                ];
            })
            ->sortByDesc('failed_messages')
            ->values();

        $failedQueue = $failedMessagesWithInvite
            ->unique('invite_id')
            ->take(max(1, $queueLimit))
            ->map(function (array $item) use ($invites, $attemptsByInvite): array {
                /** @var GuestMessage $message */
                $message = $item['message'];
                $inviteId = $item['invite_id'];
                /** @var GuestInvite|null $invite */
                $invite = $invites->get($inviteId);

                $lastLog = $message->logs->first();
                $error = $lastLog?->metadata['error'] ?? 'Falha no envio';
                $blockReason = $this->resolveRetryBlockReason($invite);

                return [
                    'invite_id' => $inviteId,
                    'household' => $invite?->household?->name ?? 'Núcleo removido',
                    'guest' => $invite?->guest?->name ?? 'Núcleo',
                    'channel' => (string) ($message->channel ?: ($invite?->channel ?: 'unknown')),
                    'invite_status' => (string) ($invite?->status ?: 'unknown'),
                    'failed_at' => $message->created_at,
                    'error' => (string) $error,
                    'failed_attempts' => (int) ($attemptsByInvite->get($inviteId, 1)),
                    'can_retry' => $blockReason === null,
                    'retry_block_reason' => $blockReason,
                ];
            })
            ->values();

        return [
            'failed_messages_total' => $failedMessagesTotal,
            'failed_invites_total' => $failedInvitesTotal,
            'failed_channels' => $failedChannels,
            'failed_queue' => $failedQueue,
        ];
    }

    /**
     * @return array{
     *   ok:bool,
     *   type:string,
     *   message:string
     * }
     */
    public function retryInviteById(string $weddingId, string $inviteId, ?string $actorId = null): array
    {
        $invite = GuestInvite::query()
            ->whereKey($inviteId)
            ->whereHas('household', fn ($query) => $query->withoutGlobalScopes()->where('wedding_id', $weddingId))
            ->first();

        if (!$invite) {
            return [
                'ok' => false,
                'type' => 'not_found',
                'message' => 'Convite não encontrado para este casamento.',
            ];
        }

        return $this->retryInvite($invite, $actorId);
    }

    /**
     * @return array{
     *   total:int,
     *   sent:int,
     *   failed:int,
     *   blocked:int,
     *   not_found:int
     * }
     */
    public function retryFailedByChannel(
        string $weddingId,
        string $channel,
        string $range = '30d',
        int $limit = 20,
        ?string $actorId = null,
    ): array {
        $incidentData = $this->forWedding($weddingId, $range, max(1, $limit * 3), 1000);

        $queue = collect($incidentData['failed_queue'])
            ->filter(fn (array $item): bool => ($item['channel'] ?? null) === $channel)
            ->take(max(1, $limit))
            ->values();

        $summary = [
            'total' => $queue->count(),
            'sent' => 0,
            'failed' => 0,
            'blocked' => 0,
            'not_found' => 0,
        ];

        foreach ($queue as $item) {
            $result = $this->retryInviteById(
                weddingId: $weddingId,
                inviteId: (string) $item['invite_id'],
                actorId: $actorId,
            );

            if ($result['ok']) {
                $summary['sent']++;
                continue;
            }

            if ($result['type'] === 'blocked') {
                $summary['blocked']++;
                continue;
            }

            if ($result['type'] === 'not_found') {
                $summary['not_found']++;
                continue;
            }

            $summary['failed']++;
        }

        return $summary;
    }

    /**
     * @return array{
     *   ok:bool,
     *   type:string,
     *   message:string
     * }
     */
    private function retryInvite(GuestInvite $invite, ?string $actorId = null): array
    {
        $blockReason = $this->resolveRetryBlockReason($invite);
        if ($blockReason) {
            return [
                'ok' => false,
                'type' => 'blocked',
                'message' => $blockReason,
            ];
        }

        $result = $this->notificationService->send($invite);
        $weddingId = $invite->household?->wedding_id;

        if (($result['ok'] ?? false) === true) {
            if ($weddingId) {
                $this->guestAuditLogService->record(
                    weddingId: (string) $weddingId,
                    action: 'guest.invite.retry_sent',
                    context: [
                        'invite_id' => $invite->id,
                        'channel' => $invite->channel,
                    ],
                    actorId: $actorId,
                );
            }

            return [
                'ok' => true,
                'type' => 'sent',
                'message' => 'Convite reenviado com sucesso.',
            ];
        }

        if ($weddingId) {
            $this->guestAuditLogService->record(
                weddingId: (string) $weddingId,
                action: 'guest.invite.retry_failed',
                context: [
                    'invite_id' => $invite->id,
                    'channel' => $invite->channel,
                    'error' => (string) ($result['message'] ?? 'Falha no reenvio'),
                ],
                actorId: $actorId,
            );
        }

        return [
            'ok' => false,
            'type' => 'failed',
            'message' => (string) ($result['message'] ?? 'Falha ao reenviar convite.'),
        ];
    }

    private function extractInviteId(GuestMessage $message): ?string
    {
        $inviteId = $message->payload['invite_id'] ?? null;

        return is_string($inviteId) && $inviteId !== '' ? $inviteId : null;
    }

    private function resolveRetryBlockReason(?GuestInvite $invite): ?string
    {
        if (!$invite) {
            return 'Convite não encontrado.';
        }

        if ($invite->status === 'revoked' || $invite->revoked_at !== null) {
            return 'Convite revogado.';
        }

        if ($invite->expires_at && $invite->expires_at->isPast()) {
            return 'Convite expirado.';
        }

        if ($invite->max_uses !== null && (int) ($invite->uses_count ?? 0) >= (int) $invite->max_uses) {
            return 'Convite sem usos disponíveis.';
        }

        return null;
    }

    private function resolveRangeStart(string $range): ?\Illuminate\Support\Carbon
    {
        return match ($range) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            'all' => null,
            default => now()->subDays(30),
        };
    }
}
