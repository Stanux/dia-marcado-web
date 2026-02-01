<?php

namespace App\Listeners;

use App\Filament\Pages\Onboarding;
use Illuminate\Auth\Events\Logout;

/**
 * Listener to clear onboarding session data when user logs out.
 * 
 * This ensures that if a user logs out without completing onboarding,
 * they will start fresh when they log back in.
 */
class ClearOnboardingSession
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        Onboarding::clearSession();
    }
}
