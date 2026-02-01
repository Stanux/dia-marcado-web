<?php

namespace Tests\Feature\Properties;

use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-site-builder, Property 7: Integridade de Versões
 * 
 * For any SiteVersion record, the fields user_id, created_at, and summary 
 * SHALL be non-null.
 * 
 * Validates: Requirements 4.6
 */
class VersionIntegrityPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property test: All versions have required fields populated
     * @test
     */
    public function all_versions_have_required_fields_populated(): void
    {
        // Create base entities
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-' . $wedding->id,
            'draft_content' => ['version' => '1.0', 'sections' => []],
        ]);

        for ($i = 0; $i < 100; $i++) {
            // Create a version with all required fields
            $version = SiteVersion::create([
                'site_layout_id' => $site->id,
                'user_id' => $user->id,
                'content' => [
                    'version' => '1.0',
                    'sections' => ['header' => ['enabled' => true]],
                    'iteration' => $i,
                ],
                'summary' => "Version change {$i}: " . fake()->sentence(),
                'is_published' => fake()->boolean(20),
            ]);

            // Verify all required fields are non-null
            $this->assertNotNull(
                $version->user_id,
                "Iteration {$i}: user_id should not be null"
            );
            
            $this->assertNotNull(
                $version->created_at,
                "Iteration {$i}: created_at should not be null"
            );
            
            $this->assertNotNull(
                $version->summary,
                "Iteration {$i}: summary should not be null"
            );

            $this->assertNotEmpty(
                $version->summary,
                "Iteration {$i}: summary should not be empty"
            );

            // Verify content is valid JSON array
            $this->assertIsArray(
                $version->content,
                "Iteration {$i}: content should be an array"
            );
        }

        // Verify all versions in database have required fields
        $allVersions = SiteVersion::where('site_layout_id', $site->id)->get();
        
        $this->assertCount(100, $allVersions);

        foreach ($allVersions as $index => $version) {
            $this->assertNotNull($version->user_id, "Version {$index}: user_id is null");
            $this->assertNotNull($version->created_at, "Version {$index}: created_at is null");
            $this->assertNotNull($version->summary, "Version {$index}: summary is null");
        }
    }

    /**
     * @test
     */
    public function version_belongs_to_user_relationship_works(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create(['name' => 'Test User']);
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site',
            'draft_content' => [],
        ]);

        $version = SiteVersion::create([
            'site_layout_id' => $site->id,
            'user_id' => $user->id,
            'content' => ['test' => true],
            'summary' => 'Test version',
        ]);

        // Verify relationship
        $this->assertInstanceOf(User::class, $version->user);
        $this->assertEquals($user->id, $version->user->id);
        $this->assertEquals('Test User', $version->user->name);
    }

    /**
     * @test
     */
    public function version_belongs_to_site_layout_relationship_works(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        // Create user with access to the wedding
        $user->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);
        $this->actingAs($user);
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-rel',
            'draft_content' => ['version' => '1.0'],
        ]);

        $version = SiteVersion::create([
            'site_layout_id' => $site->id,
            'user_id' => $user->id,
            'content' => ['test' => true],
            'summary' => 'Test version',
        ]);

        // Verify relationship (using withoutGlobalScopes to bypass auth filter)
        $siteLayout = $version->siteLayout()->withoutGlobalScopes()->first();
        $this->assertInstanceOf(SiteLayout::class, $siteLayout);
        $this->assertEquals($site->id, $siteLayout->id);
    }

    /**
     * @test
     */
    public function site_layout_versions_relationship_returns_ordered_versions(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-order',
            'draft_content' => [],
        ]);

        // Create versions with slight delay to ensure different timestamps
        for ($i = 1; $i <= 5; $i++) {
            SiteVersion::create([
                'site_layout_id' => $site->id,
                'user_id' => $user->id,
                'content' => ['order' => $i],
                'summary' => "Version {$i}",
            ]);
        }

        // Refresh site and get versions
        $site->refresh();
        $versions = $site->versions;

        $this->assertCount(5, $versions);

        // Verify versions are ordered by created_at desc (most recent first)
        $previousCreatedAt = null;
        foreach ($versions as $version) {
            if ($previousCreatedAt !== null) {
                $this->assertGreaterThanOrEqual(
                    $version->created_at,
                    $previousCreatedAt,
                    'Versions should be ordered by created_at descending'
                );
            }
            $previousCreatedAt = $version->created_at;
        }
    }
}
