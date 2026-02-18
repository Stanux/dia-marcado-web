<?php

namespace App\Services\Guests;

use App\Models\Guest;
use App\Models\GuestEvent;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\GuestRsvp;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RsvpSubmissionService
{
    private ?bool $hasNormalizedGuestColumns = null;

    public function __construct(
        private readonly InviteValidationService $inviteValidationService,
        private readonly GuestAuditLogService $guestAuditLogService,
        private readonly GuestEventQuestionValidationService $guestEventQuestionValidationService,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws RsvpSubmissionException
     */
    public function submitAuthenticated(array $validated, ?string $updatedBy = null): GuestRsvp
    {
        $status = GuestRsvp::normalizeStatus($validated['status']) ?? GuestRsvp::STATUS_NO_RESPONSE;

        $event = GuestEvent::findOrFail($validated['event_id']);
        $guest = Guest::findOrFail($validated['guest_id']);
        $responses = $this->guestEventQuestionValidationService->validateForEvent(
            event: $event,
            responses: $validated['responses'] ?? [],
        );

        if ($guest->wedding_id !== $event->wedding_id) {
            throw new RsvpSubmissionException('Convidado não pertence a este evento.', 422);
        }

        return DB::transaction(function () use ($validated, $status, $updatedBy, $guest, $responses): GuestRsvp {
            $lockedGuest = Guest::query()
                ->whereKey($guest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $rsvp = GuestRsvp::updateOrCreate(
                ['guest_id' => $validated['guest_id'], 'event_id' => $validated['event_id']],
                [
                    'status' => $status,
                    'responses' => $responses !== [] ? $responses : null,
                    'responded_at' => now(),
                    'updated_by' => $updatedBy,
                ],
            );

            $lockedGuest->refreshOverallRsvpStatus();
            $this->guestAuditLogService->record(
                weddingId: (string) $lockedGuest->wedding_id,
                action: 'guest.rsvp.authenticated_submitted',
                context: [
                    'guest_id' => $lockedGuest->id,
                    'event_id' => $validated['event_id'],
                    'status' => $status,
                    'has_responses' => !empty($responses),
                ],
                actorId: $updatedBy,
            );

            return $rsvp;
        });
    }

    /**
     * @throws ModelNotFoundException
     * @throws RsvpSubmissionException
     */
    public function submitPublic(array $validated): GuestRsvp
    {
        $status = GuestRsvp::normalizeStatus($validated['status']) ?? GuestRsvp::STATUS_NO_RESPONSE;

        $event = GuestEvent::withoutGlobalScopes()->findOrFail($validated['event_id']);
        if (!$event->is_active) {
            throw new RsvpSubmissionException('Evento indisponível para RSVP.', 422);
        }
        $responses = $this->guestEventQuestionValidationService->validateForEvent(
            event: $event,
            responses: $validated['responses'] ?? [],
        );

        $weddingId = (string) $event->wedding_id;
        $rsvpAccess = $event->wedding?->settings['rsvp_access'] ?? 'open';

        $invite = $this->inviteValidationService->resolveForWedding(
            $validated['token'] ?? null,
            $weddingId,
        );
        $accessMode = $invite ? 'token' : ($rsvpAccess === 'restricted' ? 'restricted' : 'open');

        $guest = null;
        $createMode = null;

        if ($invite?->guest_id) {
            $guest = Guest::withoutGlobalScopes()->find($invite->guest_id);

            if (!$guest) {
                throw new RsvpSubmissionException('Convite inválido.', 404);
            }

            if ($guest->wedding_id !== $weddingId) {
                throw new RsvpSubmissionException('Convidado não pertence a este evento.', 422);
            }
        } elseif ($invite?->household_id) {
            $createMode = 'invite_household';
        }

        if (!$guest && !$createMode && $rsvpAccess === 'restricted') {
            $guest = $this->resolveRestrictedGuest($validated, $weddingId);
        }

        if (!$guest && !$createMode) {
            $createMode = 'open';
        }

        return DB::transaction(function () use ($invite, $guest, $createMode, $validated, $weddingId, $status, $accessMode, $responses): GuestRsvp {
            $lockedInvite = null;
            if ($invite) {
                $lockedInvite = $this->inviteValidationService->lockAndValidateForWedding($invite, $weddingId);
            }

            $workingGuest = $guest;

            if (!$workingGuest && $createMode === 'invite_household') {
                $household = GuestHousehold::withoutGlobalScopes()->find($lockedInvite?->household_id);
                if (!$household) {
                    throw new RsvpSubmissionException('Convite inválido.', 404);
                }

                $workingGuest = $this->createGuest(
                    weddingId: $weddingId,
                    householdId: $household->id,
                    guestData: $validated['guest'],
                );
            }

            if (!$workingGuest && $createMode === 'open') {
                $household = GuestHousehold::create([
                    'wedding_id' => $weddingId,
                    'name' => $validated['household_name'] ?? $validated['guest']['name'],
                ]);

                $workingGuest = $this->createGuest(
                    weddingId: $weddingId,
                    householdId: $household->id,
                    guestData: $validated['guest'],
                );
            }

            if (!$workingGuest) {
                throw new RsvpSubmissionException('Convidado não pertence a este evento.', 422);
            }

            $lockedGuest = Guest::withoutGlobalScopes()
                ->whereKey($workingGuest->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedGuest) {
                throw new RsvpSubmissionException('Convidado não pertence a este evento.', 422);
            }

            $rsvp = GuestRsvp::updateOrCreate(
                ['guest_id' => $lockedGuest->id, 'event_id' => $validated['event_id']],
                [
                    'status' => $status,
                    'responses' => $responses !== [] ? $responses : null,
                    'responded_at' => now(),
                ],
            );

            $lockedGuest->refreshOverallRsvpStatus();

            if ($lockedInvite) {
                $lockedInvite->markUsed();

                $this->guestAuditLogService->record(
                    weddingId: $weddingId,
                    action: 'guest.invite.used',
                    context: [
                        'invite_id' => $lockedInvite->id,
                        'guest_id' => $lockedGuest->id,
                        'event_id' => $validated['event_id'],
                        'uses_count' => $lockedInvite->uses_count,
                        'max_uses' => $lockedInvite->max_uses,
                    ],
                );
            }

            $this->guestAuditLogService->record(
                weddingId: $weddingId,
                action: 'guest.rsvp.public_submitted',
                context: [
                    'guest_id' => $lockedGuest->id,
                    'event_id' => $validated['event_id'],
                    'status' => $status,
                    'access_mode' => $accessMode,
                    'invite_id' => $lockedInvite?->id,
                    'has_responses' => !empty($responses),
                ],
            );

            return $rsvp;
        });
    }

    /**
     * @throws RsvpSubmissionException
     */
    private function resolveRestrictedGuest(array $validated, string $weddingId): Guest
    {
        $rawEmail = $validated['guest']['email'] ?? null;
        $rawPhone = $validated['guest']['phone'] ?? null;
        $email = Guest::normalizeEmail($rawEmail);
        $phone = Guest::normalizePhone($rawPhone);

        if (!$email && !$phone) {
            throw new RsvpSubmissionException('Informe e-mail ou telefone para validar o RSVP.', 422);
        }

        $guest = Guest::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->where(function ($query) use ($email, $phone, $rawEmail, $rawPhone): void {
                if ($email) {
                    if ($this->hasNormalizedGuestColumns()) {
                        $query->orWhere('normalized_email', $email);
                    }

                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }

                if ($phone) {
                    if ($this->hasNormalizedGuestColumns()) {
                        $query->orWhere('normalized_phone', $phone);
                    } elseif ($rawPhone) {
                        $query->orWhere('phone', $rawPhone);
                    }
                }
            })
            ->first();

        if (!$guest) {
            throw new RsvpSubmissionException(
                'RSVP restrito. Somente convidados cadastrados podem responder.',
                403,
            );
        }

        return $guest;
    }

    private function createGuest(string $weddingId, ?string $householdId, array $guestData): Guest
    {
        return Guest::create([
            'wedding_id' => $weddingId,
            'household_id' => $householdId,
            'name' => $guestData['name'],
            'email' => $guestData['email'] ?? null,
            'phone' => $guestData['phone'] ?? null,
            'is_child' => $guestData['is_child'] ?? false,
        ]);
    }

    private function hasNormalizedGuestColumns(): bool
    {
        if ($this->hasNormalizedGuestColumns !== true) {
            $this->hasNormalizedGuestColumns = Schema::hasColumn('guests', 'normalized_email')
                && Schema::hasColumn('guests', 'normalized_phone');
        }

        return $this->hasNormalizedGuestColumns;
    }
}
