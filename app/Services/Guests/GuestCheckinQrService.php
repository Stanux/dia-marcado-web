<?php

namespace App\Services\Guests;

use App\Models\Guest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class GuestCheckinQrService
{
    private const PREFIX = 'dmc-checkin:';

    /**
     * @return array{token:string,qr_content:string}
     */
    public function forGuest(Guest $guest): array
    {
        $payload = [
            'v' => 1,
            'wedding_id' => (string) $guest->wedding_id,
            'guest_id' => (string) $guest->id,
            'issued_at' => now()->toIso8601String(),
        ];

        $token = Crypt::encryptString((string) json_encode($payload, JSON_THROW_ON_ERROR));

        return [
            'token' => $token,
            'qr_content' => self::PREFIX . $token,
        ];
    }

    public function resolveGuestId(string $rawCode, string $weddingId): string
    {
        $token = $this->extractToken($rawCode);

        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            throw new \InvalidArgumentException('Codigo QR invalido.');
        }

        $guestId = (string) ($payload['guest_id'] ?? '');
        $payloadWeddingId = (string) ($payload['wedding_id'] ?? '');

        if ($guestId === '' || $payloadWeddingId === '') {
            throw new \InvalidArgumentException('Codigo QR invalido.');
        }

        if ($payloadWeddingId !== $weddingId) {
            throw new \InvalidArgumentException('Codigo QR nao pertence a este casamento.');
        }

        return $guestId;
    }

    private function extractToken(string $rawCode): string
    {
        $normalized = trim($rawCode);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Codigo QR invalido.');
        }

        if (str_starts_with($normalized, self::PREFIX)) {
            $normalized = (string) substr($normalized, strlen(self::PREFIX));
        }

        if ($normalized === '') {
            throw new \InvalidArgumentException('Codigo QR invalido.');
        }

        return $normalized;
    }
}
