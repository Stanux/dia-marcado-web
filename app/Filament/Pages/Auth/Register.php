<?php

namespace App\Filament\Pages\Auth;

use App\Services\UserRegistrationService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Events\Auth\Registered;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Validation\ValidationException;

/**
 * Custom registration page that always creates users with role "couple".
 * Uses UserRegistrationService to ensure consistent registration behavior.
 * 
 * After successful registration, users are automatically logged in and
 * redirected to the onboarding page.
 */
class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-panels::pages/auth/register.form.name.label'))
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                TextInput::make('email')
                    ->label(__('filament-panels::pages/auth/register.form.email.label'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique($this->getUserModel()),
                TextInput::make('password')
                    ->label(__('filament-panels::pages/auth/register.form.password.label'))
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->revealable(filament()->arePasswordsRevealable())
                    ->same('passwordConfirmation'),
                TextInput::make('passwordConfirmation')
                    ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
                    ->password()
                    ->required()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->dehydrated(false),
            ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        try {
            // Create user with couple role
            $registrationService = app(UserRegistrationService::class);
            $user = $registrationService->registerCouple([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            // Dispatch Filament's Registered event
            event(new Registered($user));

            // Login the user
            Filament::auth()->login($user, remember: true);

            // Regenerate session for security
            session()->regenerate();

            // Clear intended URL to prevent redirect to login
            session()->forget('url.intended');

            // Explicitly save the session to ensure authentication persists
            session()->save();

            // Use JavaScript redirect with delay to ensure session cookie is set
            $this->js('setTimeout(() => { window.location.href = "' . route('filament.admin.pages.onboarding') . '"; }, 100);');

            return null;
        } catch (ValidationException $e) {
            throw $e;
        }
    }
}
