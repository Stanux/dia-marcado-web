<?php

namespace App\Filament\Resources\WeddingInviteResource\Pages;

use App\Filament\Resources\WeddingInviteResource;
use App\Models\WeddingEvent;
use App\Models\WeddingInvite;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class CreateWeddingInvite extends CreateRecord
{
    protected static string $resource = WeddingInviteResource::class;

    public function getHeading(): string
    {
        return '';
    }

    #[On('topbar-wedding-invite-create-another')]
    public function createAnotherFromTopbar(): void
    {
        $this->create(another: true);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['token'] = $this->generateUniqueToken();

        if (($data['invite_type'] ?? 'individual') === 'global') {
            $data['primary_contact_id'] = null;
        }

        $event = WeddingEvent::withoutGlobalScopes()->find($data['event_id'] ?? null);

        $data['expires_at'] = $data['expires_at'] ?? now()->addDays(7);
        $this->ensureExpirationNotAfterEvent($event, $data['expires_at']);

        if ($event?->isClosed()) {
            $data['confirmation_code'] = $this->generateUniqueConfirmationCode();
            $data['adult_quota'] = 0;
            $data['child_quota'] = 0;
        } else {
            $data['confirmation_code'] = null;
        }

        return $data;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (WeddingInvite::withoutGlobalScopes()->where('token', $token)->exists());

        return $token;
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
