<?php

namespace Tests\Unit\Services\Media;

use App\Contracts\Media\QuotaTrackingServiceInterface;
use App\DTOs\QuotaCheckResult;
use App\DTOs\QuotaUsage;
use App\Models\PlanLimit;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\Wedding;
use App\Services\Media\QuotaTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for QuotaTrackingService.
 * 
 * Tests quota calculation, upload permission checks, and caching behavior.
 * 
 * Validates: Requirements 5.1, 5.5, 5.6, 4.3, 4.4, 5.4
 */
class QuotaTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuotaTrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new QuotaTrackingService();
        
        // Ensure plan limits exist
        $this->createPlanLimits();
    }

    /**
     * Create default plan limits for testing.
     */
    private function createPlanLimits(): void
    {
        PlanLimit::updateOrCreate(
            ['plan_slug' => PlanLimit::PLAN_BASIC],
            ['max_files' => 100, 'max_storage_bytes' => 524288000] // 500MB
        );
        
        PlanLimit::updateOrCreate(
            ['plan_slug' => PlanLimit::PLAN_PREMIUM],
            ['max_files' => 1000, 'max_storage_bytes' => 5368709120] // 5GB
        );
    }

    /**
     * Create a wedding with optional plan slug and associated SiteLayout.
     * Returns an array with wedding and siteLayout.
     */
    private function createWeddingWithLayout(?string $planSlug = null): array
    {
        $settings = [];
        if ($planSlug) {
            $settings['plan_slug'] = $planSlug;
        }
        
        $wedding = Wedding::factory()->create(['settings' => $settings]);
        
        // Create associated SiteLayout (required for SiteMedia)
        $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
        
        return ['wedding' => $wedding, 'siteLayout' => $siteLayout];
    }

    /**
     * Create a wedding with optional plan slug (convenience method).
     */
    private function createWedding(?string $planSlug = null): Wedding
    {
        return $this->createWeddingWithLayout($planSlug)['wedding'];
    }

    /**
     * Create a completed SiteMedia for a wedding.
     */
    private function createCompletedMedia(Wedding $wedding, int $size = 1000000): SiteMedia
    {
        // Get or create SiteLayout for this wedding
        $siteLayout = SiteLayout::where('wedding_id', $wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
        }
        
        return SiteMedia::create([
            'wedding_id' => $wedding->id,
            'site_layout_id' => $siteLayout->id,
            'original_name' => 'test-' . uniqid() . '.jpg',
            'path' => 'media/' . uniqid() . '.jpg',
            'disk' => 'public',
            'size' => $size,
            'mime_type' => 'image/jpeg',
            'status' => SiteMedia::STATUS_COMPLETED,
        ]);
    }

    /**
     * Create a pending SiteMedia for a wedding.
     */
    private function createPendingMedia(Wedding $wedding, int $size = 1000000): SiteMedia
    {
        // Get or create SiteLayout for this wedding
        $siteLayout = SiteLayout::where('wedding_id', $wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
        }
        
        return SiteMedia::create([
            'wedding_id' => $wedding->id,
            'site_layout_id' => $siteLayout->id,
            'original_name' => 'test-' . uniqid() . '.jpg',
            'path' => 'media/' . uniqid() . '.jpg',
            'disk' => 'public',
            'size' => $size,
            'mime_type' => 'image/jpeg',
            'status' => SiteMedia::STATUS_PENDING,
        ]);
    }

    /**
     * Get or create a SiteLayout for a wedding.
     */
    private function getOrCreateSiteLayout(Wedding $wedding): SiteLayout
    {
        $siteLayout = SiteLayout::where('wedding_id', $wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
        }
        return $siteLayout;
    }

    #[Test]
    public function it_implements_quota_tracking_service_interface(): void
    {
        $this->assertInstanceOf(QuotaTrackingServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_usage_returns_quota_usage_dto(): void
    {
        $wedding = $this->createWedding();
        
        $usage = $this->service->getUsage($wedding);
        
        $this->assertInstanceOf(QuotaUsage::class, $usage);
    }

    #[Test]
    public function get_usage_returns_zero_for_wedding_with_no_media(): void
    {
        $wedding = $this->createWedding();
        
        $usage = $this->service->getUsage($wedding);
        
        $this->assertEquals(0, $usage->currentFiles);
        $this->assertEquals(0, $usage->currentStorageBytes);
        $this->assertEquals(0.0, $usage->filesPercentage);
        $this->assertEquals(0.0, $usage->storagePercentage);
    }

    #[Test]
    public function get_usage_counts_only_completed_media(): void
    {
        $wedding = $this->createWedding();
        
        // Create 3 completed media files
        $this->createCompletedMedia($wedding, 1000000);
        $this->createCompletedMedia($wedding, 2000000);
        $this->createCompletedMedia($wedding, 3000000);
        
        // Create 2 pending media files (should not be counted)
        $this->createPendingMedia($wedding, 5000000);
        $this->createPendingMedia($wedding, 5000000);
        
        // Clear cache to get fresh data
        $this->service->clearCache($wedding);
        
        $usage = $this->service->getUsage($wedding);
        
        $this->assertEquals(3, $usage->currentFiles);
        $this->assertEquals(6000000, $usage->currentStorageBytes); // 1M + 2M + 3M
    }

    #[Test]
    public function get_usage_calculates_correct_percentages(): void
    {
        $wedding = $this->createWedding();
        
        // Create 50 files (50% of 100 limit)
        for ($i = 0; $i < 50; $i++) {
            $this->createCompletedMedia($wedding, 5242880); // ~5MB each = 250MB total (47.68% of 500MB)
        }
        
        $this->service->clearCache($wedding);
        $usage = $this->service->getUsage($wedding);
        
        $this->assertEquals(50, $usage->currentFiles);
        $this->assertEquals(50.0, $usage->filesPercentage);
        $this->assertEqualsWithDelta(50.0, $usage->storagePercentage, 1.0); // Allow small delta for rounding
    }

    #[Test]
    public function get_usage_returns_correct_max_values_from_plan(): void
    {
        $wedding = $this->createWedding(PlanLimit::PLAN_BASIC);
        
        $usage = $this->service->getUsage($wedding);
        
        $this->assertEquals(100, $usage->maxFiles);
        $this->assertEquals(524288000, $usage->maxStorageBytes);
    }

    #[Test]
    public function get_usage_returns_premium_limits_for_premium_plan(): void
    {
        $wedding = $this->createWedding(PlanLimit::PLAN_PREMIUM);
        
        $usage = $this->service->getUsage($wedding);
        
        $this->assertEquals(1000, $usage->maxFiles);
        $this->assertEquals(5368709120, $usage->maxStorageBytes);
    }

    #[Test]
    public function can_upload_returns_true_when_within_limits(): void
    {
        $wedding = $this->createWedding();
        
        $result = $this->service->canUpload($wedding, 1000000, 1);
        
        $this->assertInstanceOf(QuotaCheckResult::class, $result);
        $this->assertTrue($result->canUpload);
        $this->assertNull($result->reason);
    }

    #[Test]
    public function can_upload_returns_false_when_file_count_exceeded(): void
    {
        $wedding = $this->createWedding();
        
        // Create 100 files (at limit)
        for ($i = 0; $i < 100; $i++) {
            $this->createCompletedMedia($wedding, 1000);
        }
        
        $this->service->clearCache($wedding);
        
        $result = $this->service->canUpload($wedding, 1000, 1);
        
        $this->assertFalse($result->canUpload);
        $this->assertNotNull($result->reason);
        $this->assertStringContainsString('arquivos', $result->reason);
    }

    #[Test]
    public function can_upload_returns_false_when_storage_exceeded(): void
    {
        $wedding = $this->createWedding();
        
        // Create files totaling 500MB (at limit)
        for ($i = 0; $i < 50; $i++) {
            $this->createCompletedMedia($wedding, 10485760); // 10MB each = 500MB total
        }
        
        $this->service->clearCache($wedding);
        
        // Try to upload 1 more byte
        $result = $this->service->canUpload($wedding, 1, 1);
        
        $this->assertFalse($result->canUpload);
        $this->assertNotNull($result->reason);
        $this->assertStringContainsString('armazenamento', $result->reason);
    }

    #[Test]
    public function can_upload_includes_upgrade_message_for_basic_plan_at_file_limit(): void
    {
        $wedding = $this->createWedding(PlanLimit::PLAN_BASIC);
        
        // Create 100 files (at limit)
        for ($i = 0; $i < 100; $i++) {
            $this->createCompletedMedia($wedding, 1000);
        }
        
        $this->service->clearCache($wedding);
        
        $result = $this->service->canUpload($wedding, 1000, 1);
        
        $this->assertFalse($result->canUpload);
        $this->assertNotNull($result->upgradeMessage);
        $this->assertStringContainsString('premium', strtolower($result->upgradeMessage));
    }

    #[Test]
    public function can_upload_includes_upgrade_message_for_basic_plan_at_storage_limit(): void
    {
        $wedding = $this->createWedding(PlanLimit::PLAN_BASIC);
        
        // Create files totaling 500MB (at limit)
        for ($i = 0; $i < 50; $i++) {
            $this->createCompletedMedia($wedding, 10485760); // 10MB each
        }
        
        $this->service->clearCache($wedding);
        
        $result = $this->service->canUpload($wedding, 1, 1);
        
        $this->assertFalse($result->canUpload);
        $this->assertNotNull($result->upgradeMessage);
        $this->assertStringContainsString('premium', strtolower($result->upgradeMessage));
    }

    #[Test]
    public function can_upload_does_not_include_upgrade_message_for_premium_plan(): void
    {
        $wedding = $this->createWedding(PlanLimit::PLAN_PREMIUM);
        
        // Create 1000 files (at premium limit)
        for ($i = 0; $i < 1000; $i++) {
            $this->createCompletedMedia($wedding, 1000);
        }
        
        $this->service->clearCache($wedding);
        
        $result = $this->service->canUpload($wedding, 1000, 1);
        
        $this->assertFalse($result->canUpload);
        $this->assertNull($result->upgradeMessage);
    }

    #[Test]
    public function can_upload_checks_multiple_files(): void
    {
        $wedding = $this->createWedding();
        
        // Create 95 files
        for ($i = 0; $i < 95; $i++) {
            $this->createCompletedMedia($wedding, 1000);
        }
        
        $this->service->clearCache($wedding);
        
        // Can upload 5 more files
        $result = $this->service->canUpload($wedding, 5000, 5);
        $this->assertTrue($result->canUpload);
        
        // Cannot upload 6 more files
        $result = $this->service->canUpload($wedding, 6000, 6);
        $this->assertFalse($result->canUpload);
    }

    #[Test]
    public function get_plan_limits_returns_plan_limit_model(): void
    {
        $wedding = $this->createWedding();
        
        $limits = $this->service->getPlanLimits($wedding);
        
        $this->assertInstanceOf(PlanLimit::class, $limits);
    }

    #[Test]
    public function get_plan_limits_returns_basic_plan_by_default(): void
    {
        $wedding = $this->createWedding(); // No plan set
        
        $limits = $this->service->getPlanLimits($wedding);
        
        $this->assertEquals(PlanLimit::PLAN_BASIC, $limits->plan_slug);
    }

    #[Test]
    public function get_plan_limits_returns_correct_plan_from_settings(): void
    {
        $wedding = $this->createWedding(PlanLimit::PLAN_PREMIUM);
        
        $limits = $this->service->getPlanLimits($wedding);
        
        $this->assertEquals(PlanLimit::PLAN_PREMIUM, $limits->plan_slug);
    }

    #[Test]
    public function get_usage_percentage_returns_array_with_files_and_storage(): void
    {
        $wedding = $this->createWedding();
        
        $percentages = $this->service->getUsagePercentage($wedding);
        
        $this->assertIsArray($percentages);
        $this->assertArrayHasKey('files', $percentages);
        $this->assertArrayHasKey('storage', $percentages);
    }

    #[Test]
    public function get_usage_percentage_returns_correct_values(): void
    {
        $wedding = $this->createWedding();
        
        // Create 25 files (25% of 100)
        for ($i = 0; $i < 25; $i++) {
            $this->createCompletedMedia($wedding, 5242880); // ~5MB each
        }
        
        $this->service->clearCache($wedding);
        
        $percentages = $this->service->getUsagePercentage($wedding);
        
        $this->assertEquals(25.0, $percentages['files']);
        $this->assertEqualsWithDelta(25.0, $percentages['storage'], 1.0);
    }

    #[Test]
    public function clear_cache_invalidates_cached_usage(): void
    {
        $wedding = $this->createWedding();
        
        // Get initial usage (cached)
        $usage1 = $this->service->getUsage($wedding);
        $this->assertEquals(0, $usage1->currentFiles);
        
        // Add a file
        $this->createCompletedMedia($wedding, 1000000);
        
        // Without clearing cache, should still return 0
        $usage2 = $this->service->getUsage($wedding);
        $this->assertEquals(0, $usage2->currentFiles);
        
        // Clear cache
        $this->service->clearCache($wedding);
        
        // Now should return 1
        $usage3 = $this->service->getUsage($wedding);
        $this->assertEquals(1, $usage3->currentFiles);
    }

    #[Test]
    public function usage_is_cached_with_correct_key(): void
    {
        $wedding = $this->createWedding();
        
        // Get usage to populate cache
        $this->service->getUsage($wedding);
        
        // Verify cache key exists
        $cacheKey = 'quota:' . $wedding->id;
        $this->assertTrue(Cache::has($cacheKey));
    }

    #[Test]
    public function get_usage_does_not_count_media_from_other_weddings(): void
    {
        $wedding1 = $this->createWedding();
        $wedding2 = $this->createWedding();
        
        // Create media for wedding1
        $this->createCompletedMedia($wedding1, 1000000);
        $this->createCompletedMedia($wedding1, 2000000);
        
        // Create media for wedding2
        $this->createCompletedMedia($wedding2, 5000000);
        
        $this->service->clearCache($wedding1);
        $this->service->clearCache($wedding2);
        
        $usage1 = $this->service->getUsage($wedding1);
        $usage2 = $this->service->getUsage($wedding2);
        
        $this->assertEquals(2, $usage1->currentFiles);
        $this->assertEquals(3000000, $usage1->currentStorageBytes);
        
        $this->assertEquals(1, $usage2->currentFiles);
        $this->assertEquals(5000000, $usage2->currentStorageBytes);
    }

    #[Test]
    public function get_usage_does_not_count_failed_media(): void
    {
        $wedding = $this->createWedding();
        $siteLayout = $this->getOrCreateSiteLayout($wedding);
        
        // Create completed media
        $this->createCompletedMedia($wedding, 1000000);
        
        // Create failed media
        SiteMedia::create([
            'wedding_id' => $wedding->id,
            'site_layout_id' => $siteLayout->id,
            'original_name' => 'failed.jpg',
            'path' => 'media/failed.jpg',
            'disk' => 'public',
            'size' => 5000000,
            'mime_type' => 'image/jpeg',
            'status' => SiteMedia::STATUS_FAILED,
        ]);
        
        $this->service->clearCache($wedding);
        
        $usage = $this->service->getUsage($wedding);
        
        $this->assertEquals(1, $usage->currentFiles);
        $this->assertEquals(1000000, $usage->currentStorageBytes);
    }

    #[Test]
    public function get_usage_does_not_count_processing_media(): void
    {
        $wedding = $this->createWedding();
        $siteLayout = $this->getOrCreateSiteLayout($wedding);
        
        // Create completed media
        $this->createCompletedMedia($wedding, 1000000);
        
        // Create processing media
        SiteMedia::create([
            'wedding_id' => $wedding->id,
            'site_layout_id' => $siteLayout->id,
            'original_name' => 'processing.jpg',
            'path' => 'media/processing.jpg',
            'disk' => 'public',
            'size' => 5000000,
            'mime_type' => 'image/jpeg',
            'status' => SiteMedia::STATUS_PROCESSING,
        ]);
        
        $this->service->clearCache($wedding);
        
        $usage = $this->service->getUsage($wedding);
        
        $this->assertEquals(1, $usage->currentFiles);
        $this->assertEquals(1000000, $usage->currentStorageBytes);
    }
}
