<?php

namespace Tests\Feature\Site;

use App\Models\Guest;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\SiteLayout;
use App\Models\Wedding;
use App\Services\Site\AccessTokenService;
use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Integration tests for public site access.
 * 
 * @Requirements: 5.5, 5.7, 6.2, 6.5
 */
class PublicAccessTest extends TestCase
{
    use RefreshDatabase;

    private AccessTokenService $accessTokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessTokenService = new AccessTokenService();
    }

    /**
     * @test
     */
    public function published_site_is_accessible_by_slug(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create([
            'title' => 'Casamento Teste',
            'wedding_date' => now()->addMonths(3),
        ]);

        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Site de Casamento';
        $content['sections']['header']['title'] = 'Bem-vindos';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'casamento-teste',
            'draft_content' => $content,
            'published_content' => $content,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Act
        $response = $this->get("/site/{$site->slug}");

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Site de Casamento');
    }

    /**
     * @test
     */
    public function unpublished_site_returns_404(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'unpublished-site',
            'draft_content' => SiteContentSchema::getDefaultContent(),
            'is_published' => false,
        ]);

        // Act
        $response = $this->get("/site/{$site->slug}");

        // Assert
        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function unpublished_site_with_valid_invite_token_is_accessible_in_rsvp_mode(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create(['title' => 'Casamento Convite']);

        $draftContent = SiteContentSchema::getDefaultContent();
        $draftContent['sections']['rsvp']['enabled'] = true;
        $draftContent['sections']['rsvp']['title'] = 'RSVP Convite';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'invite-rsvp-site',
            'draft_content' => $draftContent,
            'published_content' => null,
            'is_published' => false,
        ]);

        $household = GuestHousehold::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Família Teste',
        ]);

        $guest = Guest::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado Teste',
            'email' => 'convidado@example.com',
        ]);

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        // Act
        $response = $this->get("/site/{$site->slug}?token={$invite->token}");

        // Assert
        $response->assertStatus(200);
        $response->assertSee('RSVP Convite');
        $response->assertInertia(fn ($page) =>
            $page->component('Public/Site')
                ->where('inviteTokenState', 'valid')
        );
    }

    /**
     * @test
     */
    public function unpublished_site_with_invalid_invite_token_still_returns_404(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'invite-rsvp-invalid',
            'draft_content' => SiteContentSchema::getDefaultContent(),
            'is_published' => false,
        ]);

        // Act
        $response = $this->get("/site/{$site->slug}?token=token-invalido");

        // Assert
        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function unpublished_site_with_exhausted_invite_token_is_accessible_and_flags_limit_reached(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();

        $draftContent = SiteContentSchema::getDefaultContent();
        $draftContent['sections']['rsvp']['enabled'] = true;

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'invite-rsvp-limit',
            'draft_content' => $draftContent,
            'is_published' => false,
        ]);

        $household = GuestHousehold::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Família Limite',
        ]);

        $guest = Guest::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado Limite',
            'email' => 'convidado-limite@example.com',
        ]);

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'opened',
            'uses_count' => 1,
            'max_uses' => 1,
            'used_at' => now(),
        ]);

        // Act
        $response = $this->get("/site/{$site->slug}?token={$invite->token}");

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Public/Site')
                ->where('inviteTokenState', 'limit_reached')
        );
    }

    /**
     * @test
     */
    public function nonexistent_slug_returns_404(): void
    {
        // Act
        $response = $this->get('/site/nonexistent-slug');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function password_protected_site_shows_password_form(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();

        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Site Protegido';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'protected-site',
            'draft_content' => $content,
            'published_content' => $content,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Set password
        $this->accessTokenService->setToken($site, 'secret123');

        // Act
        $response = $this->get("/site/{$site->slug}");

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Site Protegido');
        $response->assertSee('senha'); // Password form should be shown
    }

    /**
     * @test
     */
    public function correct_password_grants_access(): void
    {
        // Arrange
        Cache::flush();
        
        $wedding = Wedding::factory()->create();

        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Site Protegido';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'protected-site-correct',
            'draft_content' => $content,
            'published_content' => $content,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $password = 'secret123';
        $this->accessTokenService->setToken($site, $password);

        // Act
        $response = $this->post("/site/{$site->slug}/auth", [
            'password' => $password,
        ]);

        // Assert
        $response->assertRedirect("/site/{$site->slug}");
        
        // Follow redirect and verify access
        $response = $this->get("/site/{$site->slug}");
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function wrong_password_shows_error(): void
    {
        // Arrange
        Cache::flush();
        
        $wedding = Wedding::factory()->create();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'protected-site-wrong',
            'draft_content' => SiteContentSchema::getDefaultContent(),
            'published_content' => SiteContentSchema::getDefaultContent(),
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->accessTokenService->setToken($site, 'correct-password');

        // Act
        $response = $this->post("/site/{$site->slug}/auth", [
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     */
    public function rate_limiting_blocks_after_max_attempts(): void
    {
        // Arrange
        Cache::flush();
        
        $wedding = Wedding::factory()->create();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'rate-limited-site',
            'draft_content' => SiteContentSchema::getDefaultContent(),
            'published_content' => SiteContentSchema::getDefaultContent(),
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->accessTokenService->setToken($site, 'correct-password');

        // Act - Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post("/site/{$site->slug}/auth", [
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post("/site/{$site->slug}/auth", [
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('password'));
    }

    /**
     * @test
     */
    public function calendar_download_works_for_published_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create([
            'title' => 'Casamento João e Maria',
            'wedding_date' => now()->addMonths(3),
            'venue' => 'Igreja Matriz',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'calendar-test-site',
            'draft_content' => SiteContentSchema::getDefaultContent(),
            'published_content' => SiteContentSchema::getDefaultContent(),
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Act
        $response = $this->get("/site/{$site->slug}/calendar");

        // Assert
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
    }

    /**
     * @test
     */
    public function calendar_download_requires_authentication_for_protected_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'protected-calendar-site',
            'draft_content' => SiteContentSchema::getDefaultContent(),
            'published_content' => SiteContentSchema::getDefaultContent(),
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->accessTokenService->setToken($site, 'secret123');

        // Act - Try to download without authentication
        $response = $this->get("/site/{$site->slug}/calendar");

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function public_site_without_password_is_accessible(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();

        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Site Público';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'public-site-no-password',
            'draft_content' => $content,
            'published_content' => $content,
            'is_published' => true,
            'published_at' => now(),
            'access_token' => null,
        ]);

        // Act
        $response = $this->get("/site/{$site->slug}");

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Site Público');
    }

    /**
     * @test
     */
    public function site_content_is_rendered_with_placeholders_replaced(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create([
            'title' => 'Casamento',
            'wedding_date' => now()->addMonths(3),
            'venue' => 'Igreja Matriz',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        // Add couple members
        $person1 = \App\Models\User::factory()->create(['name' => 'João Silva']);
        $person2 = \App\Models\User::factory()->create(['name' => 'Maria Santos']);
        $wedding->users()->attach($person1->id, ['role' => 'couple']);
        $wedding->users()->attach($person2->id, ['role' => 'couple']);

        $content = SiteContentSchema::getDefaultContent();
        $content['meta']['title'] = 'Casamento de {noivos}';
        $content['sections']['header']['title'] = '{noivo} & {noiva}';

        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'placeholder-test-site',
            'draft_content' => $content,
            'published_content' => $content,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Act
        $response = $this->get("/site/{$site->slug}");

        // Assert
        $response->assertStatus(200);
        // Placeholders should be replaced with actual names
        $response->assertDontSee('{noivo}');
        $response->assertDontSee('{noiva}');
        $response->assertDontSee('{noivos}');
    }
}
