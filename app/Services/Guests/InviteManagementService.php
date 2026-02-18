<?php

namespace App\Services\Guests;

use App\Models\Guest;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use Illuminate\Support\Facades\Schema;

class InviteManagementService
{
    private ?bool $hasMaxUsesColumn = null;
    private ?bool $hasUsesCountColumn = null;
    private ?bool $hasRevokedAtColumn = null;
    private ?bool $hasRevokedReasonColumn = null;

    public function __construct(
        private readonly GuestAuditLogService $guestAuditLogService,
    ) {}

    public function createForHousehold(GuestHousehold $household, array $data, ?string $actorId = null): GuestInvite
    {
        $guestId = $data['guest_id'] ?? null;
        $guest = null;

        if ($guestId) {
            $guest = Guest::withoutGlobalScopes()->find($guestId);

            if (!$guest || (string) $guest->wedding_id !== (string) $household->wedding_id) {
                throw new \InvalidArgumentException('Convidado inválido para este casamento.');
            }
        }

        $inviteData = [
            'household_id' => $household->id,
            'guest_id' => $guest?->id,
            'created_by' => $actorId,
            'token' => GuestInvite::generateToken(),
            'channel' => $data['channel'] ?? 'email',
            'status' => 'sent',
            'expires_at' => $this->resolveExpiresAt($data['expires_in_days'] ?? null),
        ];

        if ($this->hasMaxUsesColumn()) {
            $inviteData['max_uses'] = $this->resolveNullableInt($data['max_uses'] ?? null);
        }

        $invite = GuestInvite::create($inviteData);

        $this->guestAuditLogService->record(
            weddingId: (string) $household->wedding_id,
            action: 'guest.invite.created',
            context: [
                'invite_id' => $invite->id,
                'household_id' => $invite->household_id,
                'guest_id' => $invite->guest_id,
                'channel' => $invite->channel,
                'max_uses' => $invite->max_uses,
                'expires_at' => $invite->expires_at?->toIso8601String(),
            ],
            actorId: $actorId,
        );

        return $invite;
    }

    public function reissue(GuestInvite $invite, array $data = [], ?string $actorId = null): GuestInvite
    {
        $invite->token = GuestInvite::generateToken();
        $invite->status = 'sent';
        $invite->used_at = null;

        if ($this->hasUsesCountColumn()) {
            $invite->uses_count = 0;
        }

        if ($this->hasMaxUsesColumn()) {
            $invite->max_uses = $this->resolveNullableInt($data['max_uses'] ?? null);
        }

        if ($this->hasRevokedAtColumn()) {
            $invite->revoked_at = null;
        }

        if ($this->hasRevokedReasonColumn()) {
            $invite->revoked_reason = null;
        }

        $invite->expires_at = $this->resolveExpiresAt($data['expires_in_days'] ?? null);
        $invite->save();

        if ($invite->household?->wedding_id) {
            $this->guestAuditLogService->record(
                weddingId: (string) $invite->household->wedding_id,
                action: 'guest.invite.reissued',
                context: [
                    'invite_id' => $invite->id,
                    'household_id' => $invite->household_id,
                    'guest_id' => $invite->guest_id,
                    'channel' => $invite->channel,
                    'max_uses' => $invite->max_uses,
                    'expires_at' => $invite->expires_at?->toIso8601String(),
                ],
                actorId: $actorId,
            );
        }

        return $invite;
    }

    public function revoke(GuestInvite $invite, ?string $reason = null, ?string $actorId = null): GuestInvite
    {
        if (!$this->hasRevokedAtColumn()) {
            throw new \InvalidArgumentException('Revogação de convite indisponível nesta versão.');
        }

        $invite->status = 'revoked';
        $invite->revoked_at = now();

        if ($this->hasRevokedReasonColumn()) {
            $invite->revoked_reason = $reason;
        }

        $invite->save();

        if ($invite->household?->wedding_id) {
            $this->guestAuditLogService->record(
                weddingId: (string) $invite->household->wedding_id,
                action: 'guest.invite.revoked',
                context: [
                    'invite_id' => $invite->id,
                    'household_id' => $invite->household_id,
                    'guest_id' => $invite->guest_id,
                    'reason' => $reason,
                ],
                actorId: $actorId,
            );
        }

        return $invite;
    }

    private function resolveExpiresAt(mixed $value): ?\Illuminate\Support\Carbon
    {
        $days = $this->resolveNullableInt($value);

        return $days ? now()->addDays($days) : null;
    }

    private function resolveNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function hasMaxUsesColumn(): bool
    {
        if ($this->hasMaxUsesColumn === null) {
            $this->hasMaxUsesColumn = Schema::hasColumn('guest_invites', 'max_uses');
        }

        return $this->hasMaxUsesColumn;
    }

    private function hasUsesCountColumn(): bool
    {
        if ($this->hasUsesCountColumn === null) {
            $this->hasUsesCountColumn = Schema::hasColumn('guest_invites', 'uses_count');
        }

        return $this->hasUsesCountColumn;
    }

    private function hasRevokedAtColumn(): bool
    {
        if ($this->hasRevokedAtColumn === null) {
            $this->hasRevokedAtColumn = Schema::hasColumn('guest_invites', 'revoked_at');
        }

        return $this->hasRevokedAtColumn;
    }

    private function hasRevokedReasonColumn(): bool
    {
        if ($this->hasRevokedReasonColumn === null) {
            $this->hasRevokedReasonColumn = Schema::hasColumn('guest_invites', 'revoked_reason');
        }

        return $this->hasRevokedReasonColumn;
    }
}
