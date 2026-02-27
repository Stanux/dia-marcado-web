<?php

namespace Tests\Feature\Properties;

use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteContentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-site-builder, Property 4: Round-trip de Publicação
 * 
 * For any SiteLayout, after calling publish(), the published_content SHALL be 
 * equal to the draft_content that existed immediately before the publish operation.
 * 
 * Validates: Requirements 3.2
 */
class PublishRoundTripPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SiteBuilderServiceInterface $builderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builderService = $this->app->make(SiteBuilderServiceInterface::class);
    }

    /**
     * Property test: Publish sets published_content equal to draft_content
     * @test
     */
    public function publish_sets_published_content_equal_to_draft_content(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $user = User::factory()->create();
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            // Generate random valid content
            $draftContent = $this->generateRandomValidContent();
            
            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'test-publish-' . $wedding->id,
                'draft_content' => $draftContent,
                'published_content' => null,
                'is_published' => false,
            ]);

            // Store draft content before publish (normalized, as publish normalizes payload)
            $draftBeforePublish = SiteContentSchema::normalize((array) $site->draft_content);

            // Publish the site
            $publishedSite = $this->builderService->publish($site, $user);

            // Verify published_content equals draft_content before publish
            $this->assertEquals(
                $draftBeforePublish,
                $publishedSite->published_content,
                "Iteration {$i}: published_content should equal draft_content after publish"
            );

            // Verify is_published flag is set
            $this->assertTrue(
                $publishedSite->is_published,
                "Iteration {$i}: is_published should be true after publish"
            );

            // Verify published_at is set
            $this->assertNotNull(
                $publishedSite->published_at,
                "Iteration {$i}: published_at should be set after publish"
            );
        }
    }

    /**
     * Property test: Publish creates a published version snapshot
     * @test
     */
    public function publish_creates_published_version_snapshot(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $wedding = Wedding::factory()->create();
            $user = User::factory()->create();
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            $draftContent = $this->generateRandomValidContent();
            
            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'test-publish-version-' . $wedding->id,
                'draft_content' => $draftContent,
            ]);

            // Count published versions before
            $publishedVersionsBefore = SiteVersion::where('site_layout_id', $site->id)
                ->where('is_published', true)
                ->count();

            // Publish the site
            $this->builderService->publish($site, $user);

            // Count published versions after
            $publishedVersionsAfter = SiteVersion::where('site_layout_id', $site->id)
                ->where('is_published', true)
                ->count();

            // Verify a new published version was created
            $this->assertEquals(
                $publishedVersionsBefore + 1,
                $publishedVersionsAfter,
                "Iteration {$i}: Publish should create a new published version"
            );

            // Verify the published version content matches normalized draft
            $latestPublishedVersion = SiteVersion::where('site_layout_id', $site->id)
                ->where('is_published', true)
                ->orderByDesc('created_at')
                ->first();

            $this->assertEquals(
                SiteContentSchema::normalize($draftContent),
                $latestPublishedVersion->content,
                "Iteration {$i}: Published version content should match draft"
            );
        }
    }

    /**
     * Property test: Multiple publishes maintain content integrity
     * @test
     */
    public function multiple_publishes_maintain_content_integrity(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-multi-publish',
            'draft_content' => $this->generateRandomValidContent(),
        ]);

        // Publish multiple times with different content
        for ($i = 0; $i < 20; $i++) {
            // Update draft with new random content
            $newDraftContent = $this->generateRandomValidContent();
            $site->draft_content = $newDraftContent;
            $site->save();
            $site->refresh();

            // Store draft before publish (normalized, as publish normalizes payload)
            $draftBeforePublish = SiteContentSchema::normalize((array) $site->draft_content);

            // Publish
            $publishedSite = $this->builderService->publish($site, $user);

            // Verify round-trip
            $this->assertEquals(
                $draftBeforePublish,
                $publishedSite->published_content,
                "Iteration {$i}: published_content should equal draft_content"
            );

            $site->refresh();
        }
    }

    /**
     * Property test: Publish preserves complex nested content
     * @test
     */
    public function publish_preserves_complex_nested_content(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $wedding = Wedding::factory()->create();
            $user = User::factory()->create();
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            // Generate complex nested content
            $complexContent = $this->generateComplexNestedContent();
            
            $site = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => 'test-complex-publish-' . $wedding->id,
                'draft_content' => $complexContent,
            ]);

            // Store draft before publish (normalized, as publish normalizes payload)
            $draftBeforePublish = SiteContentSchema::normalize((array) $site->draft_content);

            // Publish
            $publishedSite = $this->builderService->publish($site, $user);

            // Verify all nested structures are preserved
            $this->assertEquals(
                $draftBeforePublish,
                $publishedSite->published_content,
                "Iteration {$i}: Complex nested content should be preserved after publish"
            );

            // Verify specific nested values
            $this->assertEquals(
                $draftBeforePublish['sections']['header']['style']['backgroundColor'] ?? null,
                $publishedSite->published_content['sections']['header']['style']['backgroundColor'] ?? null,
                "Iteration {$i}: Nested style values should be preserved"
            );
        }
    }

    /**
     * Generate random valid content that passes schema validation.
     */
    private function generateRandomValidContent(): array
    {
        $defaultContent = SiteContentSchema::getDefaultContent();
        
        // Randomize some values while keeping structure valid
        $defaultContent['sections']['header']['title'] = fake()->sentence();
        $defaultContent['sections']['header']['subtitle'] = fake()->sentence();
        $defaultContent['sections']['header']['enabled'] = fake()->boolean(80);
        $defaultContent['sections']['header']['style']['backgroundColor'] = fake()->hexColor();
        
        $defaultContent['sections']['hero']['title'] = fake()->sentence();
        $defaultContent['sections']['hero']['subtitle'] = fake()->paragraph();
        $defaultContent['sections']['hero']['enabled'] = fake()->boolean(80);
        $defaultContent['sections']['hero']['media']['url'] = fake()->imageUrl();
        $defaultContent['sections']['hero']['media']['alt'] = fake()->sentence(4);

        if (!$defaultContent['sections']['header']['enabled'] && !$defaultContent['sections']['hero']['enabled']) {
            $defaultContent['sections']['header']['enabled'] = true;
        }
        
        $defaultContent['sections']['saveTheDate']['description'] = fake()->paragraph();
        $defaultContent['sections']['saveTheDate']['enabled'] = fake()->boolean(80);
        
        $defaultContent['sections']['giftRegistry']['title'] = fake()->sentence();
        $defaultContent['sections']['giftRegistry']['enabled'] = fake()->boolean(30);
        
        $defaultContent['sections']['rsvp']['title'] = fake()->sentence();
        $defaultContent['sections']['rsvp']['enabled'] = false;
        
        // Mantém desabilitado para o foco deste teste ser round-trip de conteúdo.
        $defaultContent['sections']['photoGallery']['enabled'] = false;
        
        $defaultContent['sections']['footer']['copyrightText'] = fake()->company();
        $defaultContent['sections']['footer']['enabled'] = fake()->boolean(90);
        
        $defaultContent['meta']['title'] = fake()->sentence();
        $defaultContent['meta']['description'] = fake()->paragraph();
        
        $defaultContent['theme']['primaryColor'] = fake()->hexColor();
        $defaultContent['theme']['secondaryColor'] = fake()->hexColor();
        
        return $defaultContent;
    }

    /**
     * Generate complex nested content for thorough testing.
     */
    private function generateComplexNestedContent(): array
    {
        $content = SiteContentSchema::getDefaultContent();
        
        // Add complex navigation
        $content['sections']['header']['navigation'] = array_map(function () {
            $type = fake()->randomElement(['anchor', 'url']);

            return [
                'label' => fake()->word(),
                'target' => $type === 'anchor'
                    ? fake()->randomElement(['#section1', '#section2', '#save-the-date'])
                    : fake()->url(),
                'type' => $type,
            ];
        }, range(1, fake()->numberBetween(2, 6)));
        
        // Add complex action button
        $content['sections']['header']['actionButton'] = [
            'label' => fake()->words(2, true),
            'target' => fake()->randomElement(['#rsvp', fake()->url()]),
            'style' => fake()->randomElement(['primary', 'secondary', 'ghost']),
            'icon' => fake()->randomElement([null, 'heart', 'calendar', 'mail']),
        ];
        
        // Add complex hero media
        $content['sections']['hero']['media'] = [
            'type' => fake()->randomElement(['image', 'video', 'gallery']),
            'url' => fake()->imageUrl(),
            'fallback' => fake()->imageUrl(),
            'alt' => fake()->sentence(4),
            'autoplay' => fake()->boolean(),
            'loop' => fake()->boolean(),
        ];
        
        // Add complex style overlay
        $content['sections']['hero']['style']['overlay'] = [
            'color' => fake()->hexColor(),
            'opacity' => fake()->randomFloat(2, 0, 1),
        ];
        
        // Add complex photo gallery
        $content['sections']['photoGallery']['albums'] = [
            'before' => [
                'title' => fake()->sentence(),
                'photos' => array_map(fn() => [
                    'url' => fake()->imageUrl(),
                    'caption' => fake()->sentence(),
                    'date' => fake()->date(),
                ], range(1, fake()->numberBetween(1, 5))),
            ],
            'after' => [
                'title' => fake()->sentence(),
                'photos' => array_map(fn() => [
                    'url' => fake()->imageUrl(),
                    'caption' => fake()->sentence(),
                    'date' => fake()->date(),
                ], range(1, fake()->numberBetween(1, 5))),
            ],
        ];
        
        // Add social links
        $content['sections']['footer']['socialLinks'] = array_map(fn() => [
            'platform' => fake()->randomElement(['instagram', 'facebook', 'twitter', 'pinterest']),
            'url' => fake()->url(),
            'icon' => fake()->word(),
        ], range(1, fake()->numberBetween(1, 4)));
        
        return $content;
    }
}
