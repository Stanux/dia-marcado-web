<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\Wedding;

/**
 * Interface for managing wedding settings.
 * 
 * Handles updating wedding data including date, venue, and plan information.
 */
interface WeddingSettingsServiceInterface
{
    /**
     * Update wedding settings.
     * 
     * @param Wedding $wedding The wedding to update
     * @param array $data The data to update (wedding_date, venue fields, plan)
     * @return Wedding The updated wedding
     */
    public function update(Wedding $wedding, array $data): Wedding;

    /**
     * Check if a user can edit the wedding settings.
     * 
     * Only users with the "couple" role can edit wedding settings.
     *
     * @param User $user The user to check
     * @param Wedding $wedding The wedding to check access for
     * @return bool True if the user can edit, false otherwise
     */
    public function canEdit(User $user, Wedding $wedding): bool;
}
