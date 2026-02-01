<?php

namespace Tests\Feature\Property\Media;

use App\Contracts\Media\QuotaTrackingServiceInterface;
use App\Models\PlanLimit;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\Wedding;
use App\Services\Media\QuotaTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for QuotaTrackingService quota calculation.
 * 
 * @feature media-management
 * @property 11: Cálculo Correto de Uso de Cota
 * 
 * Validates: Requirements 5.1, 5.5, 5.6
 */
class QuotaCalculationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private QuotaTrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(QuotaTrackingService::class);
        
        // Ensure plan limits exist
        PlanLimit::firstOrCreate(
            ['plan_slug' => PlanLimit::PLAN_BASIC],
            ['max_files' => 100, 'max_storage_bytes' => 524288000]
        );
        PlanLimit::firstOrCreate(
            ['plan_slug' => PlanLimit::PLAN_PREMIUM],
            ['max_files' => 1000, 'max_storage_bytes' => 5368709120]
        );
    }

    /**
     * Create a wedding with an associated SiteLayout.
     * 
     * @return array{wedding: Wedding, siteLayout: SiteLayout}
     */
    private function createWeddingWithLayout(): array
    {
        $wedding = Wedding::factory()->create();
        $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
        
        return ['wedding' => $wedding, 'siteLayout' => $siteLayout];
    }

    /**
     * Create a SiteMedia record for a wedding.
     * 
     * @param Wedding $wedding The wedding to associate the media with
     * @param SiteLayout $siteLayout The site layout to associate the media with
     * @param int $size The file size in bytes
     * @param string $status The media status
     * @return SiteMedia
     */
    private function createMedia(Wedding $wedding, SiteLayout $siteLayout, int $size, string $status): SiteMedia
    {
        return SiteMedia::create([
            'wedding_id' => $wedding->id,
            'site_layout_id' => $siteLayout->id,
            'size' => $size,
            'status' => $status,
            'original_name' => 'test_' . uniqid() . '.jpg',
            'path' => 'media/' . uniqid() . '.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);
    }

    /**
     * Property 11: Cálculo Correto de Uso de Cota
     * 
     * For any wedding, QuotaUsage.currentFiles must equal the count of SiteMedia
     * with status "completed" for that wedding, and QuotaUsage.currentStorageBytes
     * must equal the sum of the size fields of those records.
     * 
     * **Validates: Requirements 5.1, 5.5, 5.6**
     * 
     * @test
     * @feature media-management
     * @property 11: Cálculo Correto de Uso de Cota
     */
    public function quota_usage_matches_actual_media_count_and_size(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random list of file sizes (0 to 10 files per iteration)
            $fileSizes = $this->generateRandomFileSizes();
            
            // Create a fresh wedding with site layout for each iteration
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create SiteMedia records with the generated sizes
            foreach ($fileSizes as $size) {
                $this->createMedia($wedding, $siteLayout, $size, SiteMedia::STATUS_COMPLETED);
            }
            
            // Clear cache to ensure fresh calculation
            $this->service->clearCache($wedding);
            
            // Get the quota usage
            $usage = $this->service->getUsage($wedding);
            
            // Assert: currentFiles equals count of file sizes
            $this->assertEquals(
                count($fileSizes),
                $usage->currentFiles,
                "Iteration {$iteration}: currentFiles should equal " . count($fileSizes) . " but got {$usage->currentFiles}"
            );
            
            // Assert: currentStorageBytes equals sum of file sizes
            $this->assertEquals(
                array_sum($fileSizes),
                $usage->currentStorageBytes,
                "Iteration {$iteration}: currentStorageBytes should equal " . array_sum($fileSizes) . " but got {$usage->currentStorageBytes}"
            );
        }
    }

    /**
     * Property 11: Cálculo Correto de Uso de Cota (Only completed status)
     * 
     * Verifies that only media with status "completed" is counted in quota usage.
     * Media with other statuses (pending, processing, failed) should not be counted.
     * 
     * **Validates: Requirements 5.1, 5.5, 5.6**
     * 
     * @test
     * @feature media-management
     * @property 11: Cálculo Correto de Uso de Cota
     */
    public function quota_usage_only_counts_completed_media(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random file sizes for each status
            $completedSizes = $this->generateRandomFileSizes();
            $pendingSizes = $this->generateRandomFileSizes();
            $processingSizes = $this->generateRandomFileSizes();
            $failedSizes = $this->generateRandomFileSizes();
            
            // Create a fresh wedding with site layout
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create completed media
            foreach ($completedSizes as $size) {
                $this->createMedia($wedding, $siteLayout, $size, SiteMedia::STATUS_COMPLETED);
            }
            
            // Create pending media (should NOT be counted)
            foreach ($pendingSizes as $size) {
                $this->createMedia($wedding, $siteLayout, $size, SiteMedia::STATUS_PENDING);
            }
            
            // Create processing media (should NOT be counted)
            foreach ($processingSizes as $size) {
                $this->createMedia($wedding, $siteLayout, $size, SiteMedia::STATUS_PROCESSING);
            }
            
            // Create failed media (should NOT be counted)
            foreach ($failedSizes as $size) {
                $this->createMedia($wedding, $siteLayout, $size, SiteMedia::STATUS_FAILED);
            }
            
            // Clear cache to ensure fresh calculation
            $this->service->clearCache($wedding);
            
            // Get the quota usage
            $usage = $this->service->getUsage($wedding);
            
            // Assert: only completed media is counted
            $this->assertEquals(
                count($completedSizes),
                $usage->currentFiles,
                "Iteration {$iteration}: currentFiles should only count completed media. Expected " . count($completedSizes) . " but got {$usage->currentFiles}"
            );
            
            $this->assertEquals(
                array_sum($completedSizes),
                $usage->currentStorageBytes,
                "Iteration {$iteration}: currentStorageBytes should only sum completed media. Expected " . array_sum($completedSizes) . " but got {$usage->currentStorageBytes}"
            );
        }
    }

    /**
     * Property 11: Cálculo Correto de Uso de Cota (Wedding isolation)
     * 
     * Verifies that quota usage is calculated per wedding and does not include
     * media from other weddings.
     * 
     * **Validates: Requirements 5.1, 5.5, 5.6**
     * 
     * @test
     * @feature media-management
     * @property 11: Cálculo Correto de Uso de Cota
     */
    public function quota_usage_is_isolated_per_wedding(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random file sizes for two different weddings
            $wedding1Sizes = $this->generateRandomFileSizes();
            $wedding2Sizes = $this->generateRandomFileSizes();
            
            // Create two weddings with site layouts
            $data1 = $this->createWeddingWithLayout();
            $wedding1 = $data1['wedding'];
            $siteLayout1 = $data1['siteLayout'];
            
            $data2 = $this->createWeddingWithLayout();
            $wedding2 = $data2['wedding'];
            $siteLayout2 = $data2['siteLayout'];
            
            // Create media for wedding 1
            foreach ($wedding1Sizes as $size) {
                $this->createMedia($wedding1, $siteLayout1, $size, SiteMedia::STATUS_COMPLETED);
            }
            
            // Create media for wedding 2
            foreach ($wedding2Sizes as $size) {
                $this->createMedia($wedding2, $siteLayout2, $size, SiteMedia::STATUS_COMPLETED);
            }
            
            // Clear cache for both weddings
            $this->service->clearCache($wedding1);
            $this->service->clearCache($wedding2);
            
            // Get quota usage for wedding 1
            $usage1 = $this->service->getUsage($wedding1);
            
            // Get quota usage for wedding 2
            $usage2 = $this->service->getUsage($wedding2);
            
            // Assert: wedding 1 usage only includes wedding 1 media
            $this->assertEquals(
                count($wedding1Sizes),
                $usage1->currentFiles,
                "Iteration {$iteration}: Wedding 1 currentFiles should be " . count($wedding1Sizes) . " but got {$usage1->currentFiles}"
            );
            
            $this->assertEquals(
                array_sum($wedding1Sizes),
                $usage1->currentStorageBytes,
                "Iteration {$iteration}: Wedding 1 currentStorageBytes should be " . array_sum($wedding1Sizes) . " but got {$usage1->currentStorageBytes}"
            );
            
            // Assert: wedding 2 usage only includes wedding 2 media
            $this->assertEquals(
                count($wedding2Sizes),
                $usage2->currentFiles,
                "Iteration {$iteration}: Wedding 2 currentFiles should be " . count($wedding2Sizes) . " but got {$usage2->currentFiles}"
            );
            
            $this->assertEquals(
                array_sum($wedding2Sizes),
                $usage2->currentStorageBytes,
                "Iteration {$iteration}: Wedding 2 currentStorageBytes should be " . array_sum($wedding2Sizes) . " but got {$usage2->currentStorageBytes}"
            );
        }
    }

    /**
     * Property 11: Cálculo Correto de Uso de Cota (Empty wedding)
     * 
     * Verifies that a wedding with no media returns zero for both counts.
     * 
     * **Validates: Requirements 5.1, 5.5, 5.6**
     * 
     * @test
     * @feature media-management
     * @property 11: Cálculo Correto de Uso de Cota
     */
    public function quota_usage_returns_zero_for_empty_wedding(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Create a wedding with no media (but with site layout)
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            
            // Clear cache
            $this->service->clearCache($wedding);
            
            // Get quota usage
            $usage = $this->service->getUsage($wedding);
            
            // Assert: both counts are zero
            $this->assertEquals(
                0,
                $usage->currentFiles,
                "Iteration {$iteration}: Empty wedding should have 0 currentFiles but got {$usage->currentFiles}"
            );
            
            $this->assertEquals(
                0,
                $usage->currentStorageBytes,
                "Iteration {$iteration}: Empty wedding should have 0 currentStorageBytes but got {$usage->currentStorageBytes}"
            );
        }
    }

    /**
     * Generate a random list of file sizes.
     * 
     * @return array<int> Array of file sizes in bytes (1KB to 10MB each)
     */
    private function generateRandomFileSizes(): array
    {
        // Generate 0 to 10 files per iteration
        $fileCount = mt_rand(0, 10);
        $sizes = [];
        
        for ($i = 0; $i < $fileCount; $i++) {
            // File sizes between 1KB (1000 bytes) and 10MB (10,000,000 bytes)
            $sizes[] = mt_rand(1000, 10000000);
        }
        
        return $sizes;
    }
}
