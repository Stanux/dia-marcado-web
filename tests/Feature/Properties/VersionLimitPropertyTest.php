<?php

namespace Tests\Feature\Properties;

use App\Contracts\Site\SiteVersionServiceInterface;
use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Models\SystemConfig;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Site\SiteVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-site-builder, Property 6: Limite de Versões (FIFO)
 * 
 * For any SiteLayout, the count of associated SiteVersions SHALL never exceed 
 * the configured max_versions limit. When the limit is reached and a new version 
 * is created, the oldest version SHALL be removed.
 * 
 * Validates: Requirements 4.2, 4.3
 */
class VersionLimitPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SiteVersionServiceInterface $versionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->versionService = new SiteVersionService();
    }

    /**
     * Property test: Version count never exceeds max_versions limit
     * @test
     */
    public function version_count_never_exceeds_max_versions_limit(): void
    {
        // Set a small max_versions for testing
        $maxVersions = 10;
        SystemConfig::set('site.max_versions', $maxVersions);

        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-limit-' . $wedding->id,
            'draft_content' => ['version' => '1.0', 'sections' => []],
        ]);

        // Create N + 10 versions (where N = max_versions)
        $totalVersionsToCreate = $maxVersions + 10;
        
        for ($i = 0; $i < $totalVersionsToCreate; $i++) {
            $this->versionService->createVersion(
                $site,
                [
                    'version' => '1.0',
                    'sections' => ['iteration' => $i],
                    'data' => fake()->sentence(),
                ],
                $user,
                "Version {$i}: " . fake()->sentence()
            );
        }

        // Verify count does not exceed max_versions
        $versionCount = SiteVersion::where('site_layout_id', $site->id)->count();
        
        $this->assertLessThanOrEqual(
            $maxVersions,
            $versionCount,
            "Version count ({$versionCount}) should not exceed max_versions ({$maxVersions})"
        );
    }

    /**
     * Property test: Oldest versions are removed first (FIFO)
     * @test
     */
    public function oldest_versions_are_removed_first_fifo(): void
    {
        $maxVersions = 5;
        SystemConfig::set('site.max_versions', $maxVersions);

        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-fifo-' . $wedding->id,
            'draft_content' => ['version' => '1.0'],
        ]);

        // Create versions with identifiable content
        $createdVersionIds = [];
        for ($i = 0; $i < $maxVersions + 5; $i++) {
            $version = $this->versionService->createVersion(
                $site,
                ['order' => $i, 'marker' => "version-{$i}"],
                $user,
                "Version {$i}"
            );
            $createdVersionIds[] = $version->id;
        }

        // Get remaining versions
        $remainingVersions = SiteVersion::where('site_layout_id', $site->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Verify the oldest versions were removed
        $this->assertCount($maxVersions, $remainingVersions);

        // The remaining versions should be the most recent ones
        foreach ($remainingVersions as $version) {
            $order = $version->content['order'];
            // The oldest 5 versions (0-4) should have been removed
            // Only versions 5-9 should remain
            $this->assertGreaterThanOrEqual(
                5,
                $order,
                "Version with order {$order} should have been pruned (FIFO)"
            );
        }
    }

    /**
     * Property test: Published versions are never deleted during pruning
     * @test
     */
    public function published_versions_are_never_deleted_during_pruning(): void
    {
        $maxVersions = 5;
        SystemConfig::set('site.max_versions', $maxVersions);

        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-published-' . $wedding->id,
            'draft_content' => ['version' => '1.0'],
        ]);

        // Create some published versions first (these should never be deleted)
        $publishedVersionIds = [];
        for ($i = 0; $i < 3; $i++) {
            $version = SiteVersion::create([
                'site_layout_id' => $site->id,
                'user_id' => $user->id,
                'content' => ['published' => true, 'order' => $i],
                'summary' => "Published version {$i}",
                'is_published' => true,
            ]);
            $publishedVersionIds[] = $version->id;
        }

        // Create many non-published versions to trigger pruning
        for ($i = 0; $i < $maxVersions + 10; $i++) {
            $this->versionService->createVersion(
                $site,
                ['published' => false, 'order' => $i + 100],
                $user,
                "Draft version {$i}"
            );
        }

        // Verify all published versions still exist
        foreach ($publishedVersionIds as $publishedId) {
            $this->assertDatabaseHas('site_versions', [
                'id' => $publishedId,
                'is_published' => true,
            ]);
        }

        // Verify published versions count
        $publishedCount = SiteVersion::where('site_layout_id', $site->id)
            ->where('is_published', true)
            ->count();
        
        $this->assertEquals(3, $publishedCount, 'All published versions should be preserved');
    }

    /**
     * Property test: Multiple sites maintain independent version limits
     * @test
     */
    public function multiple_sites_maintain_independent_version_limits(): void
    {
        $maxVersions = 5;
        SystemConfig::set('site.max_versions', $maxVersions);

        $user = User::factory()->create();
        
        // Create multiple weddings with sites
        $sites = [];
        for ($w = 0; $w < 3; $w++) {
            $wedding = Wedding::factory()->create();
            $sites[] = SiteLayout::withoutGlobalScopes()->create([
                'wedding_id' => $wedding->id,
                'slug' => "test-site-multi-{$w}",
                'draft_content' => ['version' => '1.0'],
            ]);
        }

        // Add versions to each site
        foreach ($sites as $siteIndex => $site) {
            for ($i = 0; $i < $maxVersions + 5; $i++) {
                $this->versionService->createVersion(
                    $site,
                    ['site' => $siteIndex, 'version' => $i],
                    $user,
                    "Site {$siteIndex} Version {$i}"
                );
            }
        }

        // Verify each site has at most max_versions
        foreach ($sites as $siteIndex => $site) {
            $versionCount = SiteVersion::where('site_layout_id', $site->id)->count();
            
            $this->assertLessThanOrEqual(
                $maxVersions,
                $versionCount,
                "Site {$siteIndex} version count ({$versionCount}) should not exceed max_versions ({$maxVersions})"
            );
        }
    }

    /**
     * Property test: pruneOldVersions returns correct count of deleted versions
     * @test
     */
    public function prune_old_versions_returns_correct_deleted_count(): void
    {
        $maxVersions = 5;
        SystemConfig::set('site.max_versions', $maxVersions);

        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        
        $site = SiteLayout::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'slug' => 'test-site-prune-count',
            'draft_content' => ['version' => '1.0'],
        ]);

        // Create versions directly without pruning
        $versionsToCreate = $maxVersions + 7;
        for ($i = 0; $i < $versionsToCreate; $i++) {
            SiteVersion::create([
                'site_layout_id' => $site->id,
                'user_id' => $user->id,
                'content' => ['order' => $i],
                'summary' => "Version {$i}",
                'is_published' => false,
            ]);
        }

        // Verify we have more than max_versions
        $beforeCount = SiteVersion::where('site_layout_id', $site->id)->count();
        $this->assertEquals($versionsToCreate, $beforeCount);

        // Prune and check return value
        $deletedCount = $this->versionService->pruneOldVersions($site);
        
        $expectedDeleted = $versionsToCreate - $maxVersions;
        $this->assertEquals(
            $expectedDeleted,
            $deletedCount,
            "pruneOldVersions should return {$expectedDeleted}, got {$deletedCount}"
        );

        // Verify final count
        $afterCount = SiteVersion::where('site_layout_id', $site->id)->count();
        $this->assertEquals($maxVersions, $afterCount);
    }
}
