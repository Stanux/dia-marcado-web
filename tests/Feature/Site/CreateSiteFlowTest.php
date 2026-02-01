<?php

namespace Tests\Feature\Site;

use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteBuilderService;
use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Integration tests for the complete site creation flow.
 * 
 * @Requirements: 2.3, 5.1
 */
class CreateSiteFlowTest extends TestCase
{
    use RefreshDatabase;

    private SiteBuilderServiceInterface $siteBuilderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteBuilderService = app(SiteBuilderServiceInterface::class);
    }

    /**
     * @test
     */
    public function couple_can_create_site_for_their_wedding(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create([
            'title' => 'Casamento João e Maria',
        ]);
        
        $couple = User::factory()->create(['name' => 'João Silva']);
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        // Act
        $site = $this->siteBuilderService->create($wedding);

        // Assert
        $this->assertNotNull($site);
        $this->assertEquals($wedding->id, $site->wedding_id);
        $this->assertNotEmpty($site->slug);
        $this->assertNotEmpty($site->draft_content);
        $this->assertNull($site->published_content);
        $this->assertFalse($site->is_published);
    }

    /**
     * @test
     */
    public function created_site_has_default_content_structure(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        // Act
        $site = $this->siteBuilderService->create($wedding);

        // Assert
        $content = $site->draft_content;
        
        $this->assertArrayHasKey('sections', $content);
        $this->assertArrayHasKey('header', $content['sections']);
        $this->assertArrayHasKey('hero', $content['sections']);
        $this->assertArrayHasKey('saveTheDate', $content['sections']);
        $this->assertArrayHasKey('giftRegistry', $content['sections']);
        $this->assertArrayHasKey('rsvp', $content['sections']);
        $this->assertArrayHasKey('photoGallery', $content['sections']);
        $this->assertArrayHasKey('footer', $content['sections']);
        $this->assertArrayHasKey('meta', $content);
        $this->assertArrayHasKey('theme', $content);
    }

    /**
     * @test
     */
    public function slug_is_generated_from_couple_names(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        
        $person1 = User::factory()->create(['name' => 'João Silva']);
        $person2 = User::factory()->create(['name' => 'Maria Santos']);
        
        $wedding->users()->attach($person1->id, ['role' => 'couple']);
        $wedding->users()->attach($person2->id, ['role' => 'couple']);

        // Act
        $site = $this->siteBuilderService->create($wedding);

        // Assert
        $this->assertNotEmpty($site->slug);
        // Slug should contain normalized names
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $site->slug);
        // Should contain at least one name
        $this->assertTrue(
            str_contains($site->slug, 'joao') || str_contains($site->slug, 'maria'),
            "Slug should contain at least one couple member name"
        );
    }

    /**
     * @test
     */
    public function cannot_create_second_site_for_same_wedding(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        // Create first site
        $site1 = $this->siteBuilderService->create($wedding);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->siteBuilderService->create($wedding);
    }

    /**
     * @test
     */
    public function api_endpoint_creates_site_for_authenticated_couple(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create(['name' => 'Test User']);
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        Sanctum::actingAs($couple);

        // Act
        $response = $this->postJson('/api/sites', [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'slug',
                'is_published',
                'draft_content',
            ],
        ]);

        $this->assertDatabaseHas('site_layouts', [
            'wedding_id' => $wedding->id,
        ]);
    }

    /**
     * @test
     */
    public function guest_cannot_create_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $guest = User::factory()->create();
        $wedding->users()->attach($guest->id, ['role' => 'guest']);
        $guest->current_wedding_id = $wedding->id;
        $guest->save();

        Sanctum::actingAs($guest);

        // Act
        $response = $this->postJson('/api/sites', [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function organizer_without_permission_cannot_create_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $organizer = User::factory()->create();
        $wedding->users()->attach($organizer->id, [
            'role' => 'organizer',
            'permissions' => json_encode(['tasks']), // No 'sites' permission
        ]);
        $organizer->current_wedding_id = $wedding->id;
        $organizer->save();

        Sanctum::actingAs($organizer);

        // Act
        $response = $this->postJson('/api/sites', [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function admin_can_create_site_for_any_wedding(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $admin = User::factory()->admin()->create();
        $admin->current_wedding_id = $wedding->id;
        $admin->save();

        Sanctum::actingAs($admin);

        // Act
        $response = $this->postJson('/api/sites', [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(201);
    }
}
