<?php

namespace App\Services\Guests;

use App\Models\Guest;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;

class InviteValidationService
{
    public function resolveForWedding(?string $token, string $weddingId): ?GuestInvite
    {
        if (!$token) {
            return null;
        }

        $invite = GuestInvite::findByToken($token);
        if (!$invite) {
            throw new RsvpSubmissionException('Convite inválido.', 404);
        }

        $this->assertBelongsToWedding($invite, $weddingId);
        $this->assertUsable($invite);

        return $invite;
    }

    public function lockAndValidateForWedding(GuestInvite $invite, string $weddingId): GuestInvite
    {
        $lockedInvite = GuestInvite::query()
            ->whereKey($invite->id)
            ->lockForUpdate()
            ->first();

        if (!$lockedInvite) {
            throw new RsvpSubmissionException('Convite inválido.', 404);
        }

        $this->assertBelongsToWedding($lockedInvite, $weddingId);
        $this->assertUsable($lockedInvite);

        return $lockedInvite;
    }

    private function assertBelongsToWedding(GuestInvite $invite, string $weddingId): void
    {
        if ($invite->household_id) {
            $inviteHousehold = GuestHousehold::withoutGlobalScopes()->find($invite->household_id);

            if (!$inviteHousehold) {
                throw new RsvpSubmissionException('Convite inválido.', 404);
            }

            if ((string) $inviteHousehold->wedding_id !== $weddingId) {
                throw new RsvpSubmissionException('Convite não pertence a este evento.', 422);
            }

            return;
        }

        if ($invite->guest_id) {
            $inviteGuest = Guest::withoutGlobalScopes()->find($invite->guest_id);

            if (!$inviteGuest) {
                throw new RsvpSubmissionException('Convite inválido.', 404);
            }

            if ((string) $inviteGuest->wedding_id !== $weddingId) {
                throw new RsvpSubmissionException('Convite não pertence a este evento.', 422);
            }
        }
    }

    private function assertUsable(GuestInvite $invite): void
    {
        if ($invite->revoked_at !== null) {
            throw new RsvpSubmissionException('Convite revogado.', 410);
        }

        if ($invite->expires_at && $invite->expires_at->isPast()) {
            throw new RsvpSubmissionException('Convite expirado.', 410);
        }

        if ($invite->max_uses !== null && $invite->uses_count >= $invite->max_uses) {
            throw new RsvpSubmissionException('Convite já atingiu o limite de uso.', 409);
        }
    }
}
