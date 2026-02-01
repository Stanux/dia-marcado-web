<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\Wedding;

/**
 * Interface for the onboarding service.
 * 
 * Handles the complete onboarding process including:
 * - Creating the wedding
 * - Creating the site
 * - Sending partner invites
 * - Marking onboarding as complete
 */
interface OnboardingServiceInterface
{
    /**
     * Complete the onboarding process for a user.
     * 
     * Creates the wedding, site, and sends partner invite if provided.
     * All operations are performed in a transaction.
     *
     * @param User $user The user completing onboarding
     * @param array $data The onboarding form data
     * @return Wedding The created wedding
     * @throws \Exception If any step fails
     */
    public function complete(User $user, array $data): Wedding;

    /**
     * Check if a user has completed the onboarding process.
     *
     * @param User $user The user to check
     * @return bool True if onboarding is complete
     */
    public function hasCompleted(User $user): bool;
}
