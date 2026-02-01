<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\RegistrationResponse as RegistrationResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Custom registration response that redirects new users to onboarding.
 * 
 * The default Filament RegistrationResponse uses redirect()->intended(),
 * which can redirect to the login page if that was the last intended URL.
 * This custom response ensures new users always go to onboarding.
 */
class RegistrationResponse implements RegistrationResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        // Clear any intended URL to prevent redirect loops
        session()->forget('url.intended');
        
        // New users should always go to onboarding
        return redirect()->route('filament.admin.pages.onboarding');
    }
}
