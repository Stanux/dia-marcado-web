<?php

namespace Tests\Feature\Site;

use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Contracts\Site\SiteVersionServiceInterface;
use App\Events\SitePublished;
use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Integration tests for the site editing and publishing flow.
 * 
 * @Requirements: 3.1, 3.2, 4.1, 23.1
 */
class EditPublishFlowTest extends TestCase
{
    use RefreshDatabase;

    private SiteBuilderServiceInterface $siteBuilderService;
    private SiteVersionServiceInterface $versionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteBuilderService = app(SiteBuilderServiceInterface::class);
        $this->versionService = app(SiteVersionServiceInterface::class);
    }

    /**
     * @test
     */
    public function updating_draft_creates_new_version(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $site = $this->siteBuilderService->create($wedding);
        $initialVersionCount = $site->versions()->count();

        // Act
        $newContent = $site->draft_content;
        $newContent['sections']['header']['title'] = 'Novo Título';
        
        $updatedSite = $this->siteBuilderService->updateDraft($site, $newContent, $couple);

        // Assert
        $this->assertEquals($initialVersionCount + 1, $updatedSite->versions()->count());
        $this->assertEquals('Novo Título', $updatedSite->draft_content['sections']['header']['title']);
    }

    /**
     * @test
     */
    public function multiple_updates_create_multiple_versions(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        $site = $this->siteBuilderService->create($wedding);

        // Act - Make 5 updates
        for ($i = 1; $i <= 5; $i++) {
            $content = $site->draft_content;
            $content['sections']['header']['title'] = "Título versão {$i}";
            $site = $this->siteBuilderService->updateDraft($site, $content, $couple);
        }

        // Assert
        $versions = $site->versions()->get();
        $this->assertGreaterThanOrEqual(5, $versions->count());
    }

    /**
     * @test
     */
    public function publishing_copies_draft_to_published(): void
    {
        // Arrange
        Event::fake([SitePublished::class]);
        
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        $site = $this->siteBuilderService->create($wedding);
        
        // Update draft
        $content = $site->draft_content;
        $content['sections']['header']['title'] = 'Título para Publicar';
        $content['meta']['title'] = 'Site de Casamento';
        $site = $this->siteBuilderService->updateDraft($site, $content, $couple);

        // Act
        $publishedSite = $this->siteBuilderService->publish($site, $couple);

        // Assert
        $this->assertTrue($publishedSite->is_published);
        $this->assertNotNull($publishedSite->published_at);
        $this->assertEquals(
            $publishedSite->draft_content['sections']['header']['title'],
            $publishedSite->published_content['sections']['header']['title']
        );
    }

    /**
     * @test
     */
    public function publishing_creates_published_version_snapshot(): void
    {
        // Arrange
        Event::fake([SitePublished::class]);
        
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        $site = $this->siteBuilderService->create($wedding);
        $content = $site->draft_content;
        $content['meta']['title'] = 'Site de Casamento';
        $site = $this->siteBuilderService->updateDraft($site, $content, $couple);

        // Act
        $publishedSite = $this->siteBuilderService->publish($site, $couple);

        // Assert
        $publishedVersion = $publishedSite->versions()
            ->where('is_published', true)
            ->latest()
            ->first();

        $this->assertNotNull($publishedVersion);
        $this->assertTrue($publishedVersion->is_published);
    }

    /**
     * @test
     */
    public function publishing_dispatches_site_published_event(): void
    {
        // Arrange
        Event::fake([SitePublished::class]);
        
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        $site = $this->siteBuilderService->create($wedding);
        $content = $site->draft_content;
        $content['meta']['title'] = 'Site de Casamento';
        $site = $this->siteBuilderService->updateDraft($site, $content, $couple);

        // Act
        $this->siteBuilderService->publish($site, $couple);

        // Assert
        Event::assertDispatched(SitePublished::class, function ($event) use ($site, $couple) {
            return $event->site->id === $site->id && $event->publisher->id === $couple->id;
        });
    }

    /**
     * @test
     */
    public function api_update_draft_endpoint_works(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $site = $this->siteBuilderService->create($wedding);

        Sanctum::actingAs($couple);

        $newContent = $site->draft_content;
        $newContent['sections']['header']['title'] = 'API Updated Title';

        // Act
        $response = $this->putJson("/api/sites/{$site->id}/draft", [
            'content' => $newContent,
            'summary' => 'Updated via API',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
        
        $site->refresh();
        $this->assertEquals('API Updated Title', $site->draft_content['sections']['header']['title']);
    }

    /**
     * @test
     */
    public function api_publish_endpoint_works(): void
    {
        // Arrange
        Event::fake([SitePublished::class]);
        
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $site = $this->siteBuilderService->create($wedding);
        
        // Add required meta title
        $content = $site->draft_content;
        $content['meta']['title'] = 'Site de Casamento';
        $site->draft_content = $content;
        $site->save();

        Sanctum::actingAs($couple);

        // Act
        $response = $this->postJson("/api/sites/{$site->id}/publish", [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
        
        $site->refresh();
        $this->assertTrue($site->is_published);
    }

    /**
     * @test
     */
    public function versions_endpoint_returns_version_history(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);
        $couple->current_wedding_id = $wedding->id;
        $couple->save();

        $site = $this->siteBuilderService->create($wedding);

        // Create some versions
        for ($i = 1; $i <= 3; $i++) {
            $content = $site->draft_content;
            $content['sections']['header']['title'] = "Version {$i}";
            $site = $this->siteBuilderService->updateDraft($site, $content, $couple);
        }

        Sanctum::actingAs($couple);

        // Act
        $response = $this->getJson("/api/sites/{$site->id}/versions", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'summary',
                    'is_published',
                    'created_at',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function restore_version_updates_draft_content(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $couple = User::factory()->create();
        $wedding->users()->attach($couple->id, ['role' => 'couple']);

        $site = $this->siteBuilderService->create($wedding);

        // Create version with specific content
        $originalContent = $site->draft_content;
        $originalContent['sections']['header']['title'] = 'Original Title';
        $site = $this->siteBuilderService->updateDraft($site, $originalContent, $couple);
        
        $versionToRestore = $site->versions()->latest()->first();

        // Make another change
        $newContent = $site->draft_content;
        $newContent['sections']['header']['title'] = 'Changed Title';
        $site = $this->siteBuilderService->updateDraft($site, $newContent, $couple);

        // Act
        $restoredSite = $this->versionService->restore($site, $versionToRestore);

        // Assert
        $this->assertEquals('Original Title', $restoredSite->draft_content['sections']['header']['title']);
    }

    /**
     * @test
     */
    public function organizer_cannot_publish_site(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $organizer = User::factory()->create();
        $wedding->users()->attach($organizer->id, [
            'role' => 'organizer',
            'permissions' => json_encode(['sites']),
        ]);
        $organizer->current_wedding_id = $wedding->id;
        $organizer->save();

        $site = SiteLayout::factory()->create([
            'wedding_id' => $wedding->id,
        ]);

        Sanctum::actingAs($organizer);

        // Act
        $response = $this->postJson("/api/sites/{$site->id}/publish", [], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }
}
