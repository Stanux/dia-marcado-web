<?php

namespace Tests\Feature\Auth;

use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_creates_new_user_and_redirects_to_onboarding(): void
    {
        $this->enableGoogleOAuth();

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->mockGoogleUser([
                'id' => 'google-new-user',
                'email' => 'novo.usuario@example.com',
                'name' => 'Novo Usuario',
            ]));

        $response = $this->get(route('auth.google.callback'));

        $user = User::query()->where('email', 'novo.usuario@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('couple', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('user_social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-new-user',
            'provider_email' => 'novo.usuario@example.com',
        ]);

        $response->assertRedirect(route('filament.admin.pages.onboarding'));
    }

    public function test_callback_links_existing_user_by_email_and_redirects_dashboard(): void
    {
        $this->enableGoogleOAuth();

        $user = User::factory()
            ->couple()
            ->onboardingCompleted()
            ->create([
                'email' => 'casal.existente@example.com',
                'email_verified_at' => null,
            ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->mockGoogleUser([
                'id' => 'google-existing-user',
                'email' => 'casal.existente@example.com',
                'name' => 'Casal Existente',
            ]));

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('user_social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-existing-user',
            'provider_email' => 'casal.existente@example.com',
        ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        $response->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_callback_accepts_pending_invite_when_email_matches(): void
    {
        $this->enableGoogleOAuth();

        $inviter = User::factory()->couple()->create();
        $wedding = Wedding::factory()->create();
        $wedding->users()->attach($inviter->id, [
            'role' => 'couple',
            'permissions' => [],
        ]);

        $invite = PartnerInvite::factory()
            ->forWedding($wedding, $inviter)
            ->create([
                'token' => 'invite-google-token',
                'email' => 'parceiro.google@example.com',
                'name' => 'Parceiro Google',
                'status' => 'pending',
                'existing_user_id' => null,
                'previous_wedding_id' => null,
                'expires_at' => now()->addDays(3),
            ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($this->mockGoogleUser([
                'id' => 'google-invite-user',
                'email' => 'parceiro.google@example.com',
                'name' => 'Parceiro Google',
            ]));

        $response = $this->withSession([
            'auth_google_invite_token' => $invite->token,
        ])->get(route('auth.google.callback'));

        $invite->refresh();
        $this->assertSame('accepted', $invite->status);

        $user = User::query()->where('email', 'parceiro.google@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasCompletedOnboarding());
        $this->assertDatabaseHas('wedding_user', [
            'user_id' => $user->id,
            'wedding_id' => $wedding->id,
            'role' => 'couple',
        ]);

        $response->assertSessionHas('filament_wedding_id', $wedding->id);
        $response->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    private function enableGoogleOAuth(): void
    {
        config()->set('services.google.enabled', true);
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');
    }

    /**
     * @param array{id?: string, email?: string, name?: string, avatar?: string} $overrides
     */
    private function mockGoogleUser(array $overrides = []): SocialiteUserContract
    {
        $data = array_merge([
            'id' => 'google-user-id',
            'email' => 'usuario@example.com',
            'name' => 'Usuario Google',
            'avatar' => 'https://example.test/avatar.png',
        ], $overrides);

        $socialUser = Mockery::mock(SocialiteUserContract::class);
        $socialUser->shouldReceive('getId')->andReturn($data['id']);
        $socialUser->shouldReceive('getEmail')->andReturn($data['email']);
        $socialUser->shouldReceive('getName')->andReturn($data['name']);
        $socialUser->shouldReceive('getAvatar')->andReturn($data['avatar']);

        return $socialUser;
    }
}
