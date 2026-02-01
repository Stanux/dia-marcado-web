<?php

namespace App\Services;

use App\Contracts\WeddingSettingsServiceInterface;
use App\Models\User;
use App\Models\Wedding;

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
        // Prepare the main wedding fields
        $updateData = [];

        if (array_key_exists('wedding_date', $data)) {
            $updateData['wedding_date'] = $data['wedding_date'];
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

        $updateData['settings'] = $settings;

        $wedding->update($updateData);

        return $wedding->fresh();
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
}
