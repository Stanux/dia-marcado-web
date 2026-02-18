<?php

namespace App\Services\Guests;

use App\Models\GuestInvite;
use App\Services\GuestInviteNotificationService;
use Illuminate\Support\Collection;

class InviteBulkActionService
{
    public function __construct(
        private readonly InviteManagementService $inviteManagementService,
        private readonly GuestInviteNotificationService $notificationService,
    ) {}

    /**
     * @param iterable<GuestInvite> $invites
     * @return array{
     *   total:int,
     *   reissued:int,
     *   sent:int,
     *   failed_to_send:int,
     *   failed:int
     * }
     */
    public function reissue(iterable $invites, array $data = [], ?string $actorId = null): array
    {
        $collection = $this->normalizeInvites($invites);

        $summary = [
            'total' => $collection->count(),
            'reissued' => 0,
            'sent' => 0,
            'failed_to_send' => 0,
            'failed' => 0,
        ];

        foreach ($collection as $invite) {
            try {
                $invite = $this->inviteManagementService->reissue(
                    invite: $invite,
                    data: $data,
                    actorId: $actorId,
                );

                $summary['reissued']++;

                $result = $this->notificationService->send($invite);
                if (($result['ok'] ?? false) === true) {
                    $summary['sent']++;
                } else {
                    $summary['failed_to_send']++;
                }
            } catch (\Throwable) {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * @param iterable<GuestInvite> $invites
     * @return array{
     *   total:int,
     *   revoked:int,
     *   already_revoked:int,
     *   failed:int
     * }
     */
    public function revoke(iterable $invites, ?string $reason = null, ?string $actorId = null): array
    {
        $collection = $this->normalizeInvites($invites);

        $summary = [
            'total' => $collection->count(),
            'revoked' => 0,
            'already_revoked' => 0,
            'failed' => 0,
        ];

        foreach ($collection as $invite) {
            if ($invite->status === 'revoked') {
                $summary['already_revoked']++;
                continue;
            }

            try {
                $this->inviteManagementService->revoke(
                    invite: $invite,
                    reason: $reason,
                    actorId: $actorId,
                );
                $summary['revoked']++;
            } catch (\Throwable) {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * @param iterable<GuestInvite> $invites
     * @return Collection<int, GuestInvite>
     */
    private function normalizeInvites(iterable $invites): Collection
    {
        if ($invites instanceof Collection) {
            return $invites->filter(fn (mixed $invite): bool => $invite instanceof GuestInvite)->values();
        }

        return collect($invites)
            ->filter(fn (mixed $invite): bool => $invite instanceof GuestInvite)
            ->values();
    }
}
