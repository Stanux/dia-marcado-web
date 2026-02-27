<?php

namespace App\Services;

use App\Contracts\WeddingSettingsServiceInterface;
use App\Models\GuestEvent;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Support\Carbon;

/**
 * Service for managing wedding settings.
 */
class WeddingSettingsService implements WeddingSettingsServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(Wedding $wedding, array $data): Wedding
    {
        $originalWeddingDate = $wedding->wedding_date?->copy();
        $originalWeddingTime = $this->normalizeWeddingTime($wedding->settings['wedding_time'] ?? null);

        // Prepare the main wedding fields
        $updateData = [];

        if (array_key_exists('wedding_date', $data)) {
            $updateData['wedding_date'] = $this->normalizeWeddingDate($data['wedding_date'] ?? null);
        }

        if (array_key_exists('venue_name', $data)) {
            $updateData['venue'] = $data['venue_name'];
        }

        if (array_key_exists('venue_city', $data)) {
            $updateData['city'] = $data['venue_city'];
        }

        if (array_key_exists('venue_state', $data)) {
            $updateData['state'] = $data['venue_state'];
        }

        // Prepare settings (stored in JSON column)
        $settings = $wedding->settings ?? [];

        if (array_key_exists('plan', $data)) {
            $settings['plan'] = $data['plan'];
        }

        if (array_key_exists('venue_address', $data)) {
            $settings['venue_address'] = $data['venue_address'];
        }

        if (array_key_exists('venue_neighborhood', $data)) {
            $settings['venue_neighborhood'] = $data['venue_neighborhood'];
        }

        if (array_key_exists('venue_phone', $data)) {
            $settings['venue_phone'] = $data['venue_phone'];
        }

        if (array_key_exists('rsvp_access', $data)) {
            $settings['rsvp_access'] = $data['rsvp_access'] ?? 'open';
        }

        if (array_key_exists('wedding_time', $data)) {
            $settings['wedding_time'] = $this->normalizeWeddingTime($data['wedding_time'] ?? null);
        }

        if (array_key_exists('partner_name_draft', $data)) {
            $settings['partner_name_draft'] = $this->normalizeText($data['partner_name_draft'] ?? null);
        }

        $updateData['settings'] = $settings;

        $wedding->update($updateData);

        $updatedWedding = $wedding->fresh();

        $this->syncDefaultGuestEventDateTime(
            $updatedWedding,
            $originalWeddingDate,
            $originalWeddingTime
        );

        return $updatedWedding;
    }

    /**
     * {@inheritdoc}
     */
    public function canEdit(User $user, Wedding $wedding): bool
    {
        return $user->weddings()
            ->where('wedding_id', $wedding->id)
            ->wherePivot('role', 'couple')
            ->exists();
    }

    private function syncDefaultGuestEventDateTime(
        Wedding $wedding,
        ?Carbon $oldWeddingDate,
        ?string $oldWeddingTime
    ): void {
        $event = GuestEvent::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('slug', 'casamento')
            ->first();

        if (! $event) {
            return;
        }

        $metadata = is_array($event->metadata ?? null) ? $event->metadata : [];
        $isAutoCreated = (bool) ($metadata['auto_created'] ?? false);
        $isOnboardingSource = (string) ($metadata['source'] ?? '') === 'onboarding';
        $syncEnabled = ($metadata['sync_with_wedding_settings'] ?? true) !== false;

        if (! $isAutoCreated || ! $isOnboardingSource || ! $syncEnabled) {
            return;
        }

        $oldExpectedEventAt = $this->buildEventAt($oldWeddingDate, $oldWeddingTime);
        $newWeddingDate = $wedding->wedding_date?->copy();
        $newWeddingTime = $this->normalizeWeddingTime($wedding->settings['wedding_time'] ?? null);
        $newExpectedEventAt = $this->buildEventAt($newWeddingDate, $newWeddingTime);
        $currentEventAt = $event->event_at?->copy();

        if (! $this->canSyncEventAt($currentEventAt, $oldExpectedEventAt)) {
            return;
        }

        if ($currentEventAt === null && $newExpectedEventAt === null) {
            return;
        }

        if ($currentEventAt !== null && $newExpectedEventAt !== null && $currentEventAt->equalTo($newExpectedEventAt)) {
            return;
        }

        $event->forceFill([
            'event_at' => $newExpectedEventAt,
            'metadata' => array_merge($metadata, [
                'sync_with_wedding_settings' => true,
            ]),
        ])->save();
    }

    private function canSyncEventAt(?Carbon $currentEventAt, ?Carbon $oldExpectedEventAt): bool
    {
        if ($currentEventAt === null) {
            return true;
        }

        if ($oldExpectedEventAt === null) {
            return false;
        }

        return $currentEventAt->equalTo($oldExpectedEventAt);
    }

    private function buildEventAt(?Carbon $date, ?string $time): ?Carbon
    {
        if ($date === null || $time === null) {
            return null;
        }

        return Carbon::parse(
            $date->format('Y-m-d') . ' ' . $time,
            config('app.timezone')
        );
    }

    private function normalizeWeddingTime(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})(?::\d{2})?$/', $value, $matches) !== 1) {
            return null;
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function normalizeWeddingDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '' || strtolower($normalized) === 'null' || $normalized === '0000-00-00') {
            return null;
        }

        if (preg_match('/^(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})$/', $normalized, $matches) === 1) {
            $year = (int) $matches['year'];
            $month = (int) $matches['month'];
            $day = (int) $matches['day'];

            return checkdate($month, $day, $year) ? $normalized : null;
        }

        try {
            return Carbon::parse($normalized)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
