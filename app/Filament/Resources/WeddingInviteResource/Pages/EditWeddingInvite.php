<?php

namespace App\Filament\Resources\WeddingInviteResource\Pages;

use App\Filament\Resources\WeddingInviteResource;
use App\Models\WeddingEvent;
use App\Models\WeddingInvite;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class EditWeddingInvite extends EditRecord
{
    protected static string $resource = WeddingInviteResource::class;

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
}
