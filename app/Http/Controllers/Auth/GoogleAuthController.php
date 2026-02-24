<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\PartnerInviteServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\UserSocialAccount;
use App\Services\UserRegistrationService;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private const INVITE_SESSION_KEY = 'auth_google_invite_token';

    public function __construct(
        private readonly UserRegistrationService $registrationService,
        private readonly PartnerInviteServiceInterface $partnerInviteService,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->isGoogleEnabled()) {
            abort(404);
        }

        $inviteToken = trim((string) $request->query('invite', ''));

        if ($inviteToken !== '') {
            $invite = PartnerInvite::query()
                ->where('token', $inviteToken)
                ->pending()
                ->first();

            if (! $invite || $invite->isExpired()) {
                return redirect()->route('convite.show', ['token' => $inviteToken]);
            }

            session([self::INVITE_SESSION_KEY => $inviteToken]);
        } else {
            session()->forget(self::INVITE_SESSION_KEY);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isGoogleEnabled()) {
            abort(404);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Não foi possível autenticar com o Google.');
        }

        $googleEmail = trim((string) $googleUser->getEmail());
        if ($googleEmail === '') {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Sua conta Google não retornou um e-mail válido.');
        }

        $email = Str::lower($googleEmail);
        $name = trim((string) ($googleUser->getName() ?: Str::before($email, '@')));
        $providerUserId = trim((string) $googleUser->getId());
        $avatar = $googleUser->getAvatar();

        if ($providerUserId === '') {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Não foi possível identificar sua conta Google.');
        }

        [$user, $createdNow] = DB::transaction(function () use ($email, $name, $providerUserId, $avatar): array {
            $linkedAccount = UserSocialAccount::query()
                ->with('user')
                ->where('provider', 'google')
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($linkedAccount) {
                $linkedAccount->update([
                    'provider_email' => $email,
                    'avatar' => $avatar,
                ]);

                $user = $linkedAccount->user;

                if (! $user->email_verified_at) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                return [$user, false];
            }

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            $createdNow = false;

            if (! $user) {
                $user = $this->registrationService->registerCoupleFromSocial([
                    'name' => $name,
                    'email' => $email,
                ]);
                $createdNow = true;
            } elseif (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            UserSocialAccount::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'google',
                ],
                [
                    'provider_user_id' => $providerUserId,
                    'provider_email' => $email,
                    'avatar' => $avatar,
                ],
            );

            return [$user, $createdNow];
        });

        Filament::auth()->login($user, remember: true);
        $request->session()->regenerate();

        $inviteToken = session()->pull(self::INVITE_SESSION_KEY);

        if (is_string($inviteToken) && $inviteToken !== '') {
            $invite = PartnerInvite::query()
                ->where('token', $inviteToken)
                ->pending()
                ->first();

            if (! $invite || $invite->isExpired()) {
                return redirect()->route('convite.show', ['token' => $inviteToken]);
            }

            if (! hash_equals(Str::lower($invite->email), Str::lower($user->email))) {
                return redirect()->route('convite.show', ['token' => $inviteToken])
                    ->with('error', 'Este convite pertence a outro e-mail.');
            }

            $this->partnerInviteService->acceptInvite($invite, $user);

            session(['filament_wedding_id' => $invite->wedding_id]);

            return redirect()->route('filament.admin.pages.dashboard')
                ->with('success', 'Convite aceito com sucesso.');
        }

        if ($createdNow || ! $user->hasCompletedOnboarding()) {
            return redirect()->route('filament.admin.pages.onboarding');
        }

        return redirect()->route('filament.admin.pages.dashboard');
    }

    private function isGoogleEnabled(): bool
    {
        return (bool) config('services.google.enabled', false)
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
