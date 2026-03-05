<?php

namespace App\Filament\Resources\WeddingInviteResource\Pages;

use App\Filament\Resources\WeddingInviteResource;
use App\Models\WeddingEvent;
use App\Models\WeddingEventRsvp;
use App\Models\WeddingGuest;
use App\Models\WeddingInvite;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class EditWeddingInvite extends EditRecord
{
    protected static string $resource = WeddingInviteResource::class;

    /** @var array<string, string> */
    public array $guestRsvpStatusDrafts = [];

    public function getHeading(): string
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getGuestRsvpStatusDrafts(): array
    {
        return $this->guestRsvpStatusDrafts;
    }

    public function getCurrentInviteId(): ?string
    {
        return $this->record?->getKey() ? (string) $this->record->getKey() : null;
    }

    public function setGuestRsvpStatusDraft(string $guestId, string $status): void
    {
        if (!in_array($status, [
            WeddingEventRsvp::STATUS_PENDING,
            WeddingEventRsvp::STATUS_CONFIRMED,
            WeddingEventRsvp::STATUS_DECLINED,
        ], true)) {
            return;
        }

        if (!$this->isGuestInCurrentInviteScope($guestId)) {
            Notification::make()
                ->title('Convidado fora do escopo deste convite.')
                ->danger()
                ->send();

            return;
        }

        $this->guestRsvpStatusDrafts[(string) $guestId] = $status;
    }

    protected function afterSave(): void
    {
        $this->persistGuestStatusDrafts();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['invite_type'] ?? 'individual') === 'global') {
            $data['primary_contact_id'] = null;
        }

        $event = WeddingEvent::withoutGlobalScopes()->find($data['event_id'] ?? null);
        $this->ensureExpirationNotAfterEvent($event, $data['expires_at'] ?? null);

        if (empty($this->record->confirmation_code)) {
            $data['confirmation_code'] = $this->generateUniqueConfirmationCode();
        }

        if ($event?->isClosed()) {
            $data['adult_quota'] = 0;
            $data['child_quota'] = 0;
        }

        return $data;
    }

    #[On('topbar-wedding-invite-delete')]
    public function openDeleteModalFromTopbar(): void
    {
        abort_unless(static::getResource()::canDelete($this->getRecord()), 403);

        $this->mountAction('delete');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        parent::configureDeleteAction($action);

        $action->successRedirectUrl(fn (): string => $this->getResource()::getUrl('index'));
    }

    protected function deleteAction(): DeleteAction
    {
        return Actions\DeleteAction::make();
    }

    private function generateUniqueConfirmationCode(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {
            $code = '';

            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (WeddingInvite::withoutGlobalScopes()->where('confirmation_code', $code)->exists());

        return $code;
    }

    private function ensureExpirationNotAfterEvent(?WeddingEvent $event, mixed $expiresAt): void
    {
        if (! $event?->event_date || blank($expiresAt)) {
            return;
        }

        $eventDate = $event->event_date->format('Y-m-d');
        $eventTime = filled($event->event_time) ? substr((string) $event->event_time, 0, 8) : '23:59:59';
        $eventLimit = Carbon::parse("{$eventDate} {$eventTime}");
        $expiration = Carbon::parse((string) $expiresAt);

        if ($expiration->greaterThan($eventLimit)) {
            throw ValidationException::withMessages([
                'expires_at' => 'A expiração não pode ser maior que a data do evento.',
            ]);
        }
    }

    private function persistGuestStatusDrafts(): void
    {
        if ($this->guestRsvpStatusDrafts === []) {
            return;
        }

        $scopedGuestIds = $this->resolveInviteScopedGuestIds();

        if ($scopedGuestIds === []) {
            return;
        }

        $currentStatuses = WeddingEventRsvp::withoutGlobalScopes()
            ->where('wedding_id', $this->record->wedding_id)
            ->where('event_id', $this->record->event_id)
            ->whereIn('guest_id', $scopedGuestIds)
            ->pluck('status', 'guest_id')
            ->map(fn ($status): string => in_array($status, [
                WeddingEventRsvp::STATUS_PENDING,
                WeddingEventRsvp::STATUS_CONFIRMED,
                WeddingEventRsvp::STATUS_DECLINED,
            ], true) ? (string) $status : WeddingEventRsvp::STATUS_PENDING)
            ->all();

        foreach ($this->guestRsvpStatusDrafts as $guestId => $status) {
            $guestId = (string) $guestId;
            $status = (string) $status;

            if (
                !in_array($guestId, $scopedGuestIds, true)
                || !in_array($status, [
                    WeddingEventRsvp::STATUS_PENDING,
                    WeddingEventRsvp::STATUS_CONFIRMED,
                    WeddingEventRsvp::STATUS_DECLINED,
                ], true)
            ) {
                continue;
            }

            $currentStatus = $currentStatuses[$guestId] ?? WeddingEventRsvp::STATUS_PENDING;

            if ($status === $currentStatus) {
                continue;
            }

            WeddingEventRsvp::withoutGlobalScopes()->updateOrCreate(
                [
                    'event_id' => $this->record->event_id,
                    'guest_id' => $guestId,
                ],
                [
                    'wedding_id' => $this->record->wedding_id,
                    'invite_id' => $this->record->id,
                    'status' => $status,
                    'responded_at' => $status === WeddingEventRsvp::STATUS_PENDING ? null : now(),
                    'response_channel' => 'admin_panel',
                ],
            );
        }

        $this->guestRsvpStatusDrafts = [];
        $this->record->refresh();
    }

    private function isGuestInCurrentInviteScope(string $guestId): bool
    {
        return in_array($guestId, $this->resolveInviteScopedGuestIds(), true);
    }

    /**
     * @return array<int, string>
     */
    private function resolveInviteScopedGuestIds(): array
    {
        if ((string) $this->record->invite_type === 'global') {
            return WeddingEventRsvp::withoutGlobalScopes()
                ->where('wedding_id', $this->record->wedding_id)
                ->where('event_id', $this->record->event_id)
                ->where('invite_id', $this->record->id)
                ->pluck('guest_id')
                ->filter()
                ->map(fn ($id): string => (string) $id)
                ->unique()
                ->values()
                ->all();
        }

        if (blank($this->record->primary_contact_id)) {
            return [];
        }

        return WeddingGuest::withoutGlobalScopes()
            ->where('wedding_id', $this->record->wedding_id)
            ->where(function ($query): void {
                $query->whereKey($this->record->primary_contact_id)
                    ->orWhere('primary_contact_id', $this->record->primary_contact_id);
            })
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }
}
