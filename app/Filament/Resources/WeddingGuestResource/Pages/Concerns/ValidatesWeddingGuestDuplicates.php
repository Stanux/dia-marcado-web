<?php

namespace App\Filament\Resources\WeddingGuestResource\Pages\Concerns;

use App\Models\WeddingGuest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ValidatesWeddingGuestDuplicates
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    protected function validateGuestDuplicateInWedding(array $data, ?string $ignoreGuestId = null): void
    {
        $weddingId = (string) ($data['wedding_id'] ?? auth()->user()?->current_wedding_id ?? session('filament_wedding_id') ?? '');
        if ($weddingId === '') {
            return;
        }

        $normalizedName = $this->normalizeGuestName((string) ($data['name'] ?? ''));
        if ($normalizedName === '') {
            return;
        }

        $normalizedEmail = $this->normalizeGuestEmail($data['email'] ?? null);
        $normalizedPhone = $this->normalizeGuestPhone($data['phone'] ?? null);

        $duplicate = WeddingGuest::query()
            ->where('wedding_id', $weddingId)
            ->when($ignoreGuestId, fn ($query) => $query->whereKeyNot($ignoreGuestId))
            ->get(['id', 'name', 'email', 'phone', 'primary_contact_id'])
            ->first(function (WeddingGuest $guest) use ($normalizedName, $normalizedEmail, $normalizedPhone): bool {
                $guestName = $this->normalizeGuestName((string) $guest->name);
                if ($guestName !== $normalizedName) {
                    return false;
                }

                if ($normalizedEmail === null && $normalizedPhone === null) {
                    return true;
                }

                $emailMatches = $normalizedEmail !== null
                    && $this->normalizeGuestEmail($guest->email) === $normalizedEmail;
                $phoneMatches = $normalizedPhone !== null
                    && $this->normalizeGuestPhone($guest->phone) === $normalizedPhone;

                return $emailMatches || $phoneMatches;
            });

        if (!$duplicate) {
            return;
        }

        if (blank($data['primary_contact_id'] ?? null) && $duplicate->primary_contact_id !== null) {
            throw ValidationException::withMessages([
                'name' => 'O contato definido como Principal já está cadastrado como convidado.',
            ]);
        }

        throw ValidationException::withMessages([
            'name' => 'Este convidado já está cadastrado na lista de convidados.',
        ]);
    }

    private function normalizeGuestName(string $value): string
    {
        return (string) Str::of($value)
            ->trim()
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/', ' ');
    }

    private function normalizeGuestEmail(mixed $value): ?string
    {
        $email = trim((string) $value);

        if ($email === '') {
            return null;
        }

        return Str::lower($email);
    }

    private function normalizeGuestPhone(mixed $value): ?string
    {
        $phone = trim((string) $value);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null || $digits === '') {
            return null;
        }

        return $digits;
    }
}

