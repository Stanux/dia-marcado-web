<?php

namespace Tests\Feature\Property\Media;

use App\Models\PlanLimit;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\Wedding;
use App\Services\Media\QuotaTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for QuotaTrackingService quota verification.
 * 
 * @feature media-management
 * @property 9: Verificação de Cota Bloqueia Uploads
 * 
 * **Validates: Requirements 4.3, 4.4**
 */
class QuotaVerificationPropertyTest extends TestCase
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
            ['max_files' => 100, 'max_storage_bytes' => 524288000] // 500MB
        );
        PlanLimit::firstOrCreate(
            ['plan_slug' => PlanLimit::PLAN_PREMIUM],
            ['max_files' => 1000, 'max_storage_bytes' => 5368709120] // 5GB
        );
    }

    /**
     * Create a wedding with an associated SiteLayout.
     * 
     * @param string $planSlug The plan slug for the wedding
     * @return array{wedding: Wedding, siteLayout: SiteLayout}
     */
    private function createWeddingWithLayout(string $planSlug = PlanLimit::PLAN_BASIC): array
    {
        $wedding = Wedding::factory()->create();
        // Set the plan slug in settings
        $wedding->setSetting('plan_slug', $planSlug);
        $wedding->save();
        
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
    private function createMedia(Wedding $wedding, SiteLayout $siteLayout, int $size, string $status = SiteMedia::STATUS_COMPLETED): SiteMedia
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
     * Property 9: Verificação de Cota Bloqueia Uploads (File Limit)
     * 
     * For any wedding that has reached max_files of their plan,
     * upload attempts must return QuotaCheckResult with canUpload=false
     * and an explanatory reason.
     * 
     * **Validates: Requirements 4.3**
     */
    #[Test]
    public function quota_check_blocks_uploads_when_file_limit_reached(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random plan limits for this iteration
            $maxFiles = mt_rand(5, 50); // Small limits for faster testing
            $maxStorageBytes = 524288000; // 500MB - large enough to not hit storage limit
            
            // Create a custom plan limit for this test
            $planSlug = 'test_plan_' . $iteration;
            PlanLimit::updateOrCreate(
                ['plan_slug' => $planSlug],
                ['max_files' => $maxFiles, 'max_storage_bytes' => $maxStorageBytes]
            );
            
            // Create a wedding with this plan
            $wedding = Wedding::factory()->create();
            $wedding->setSetting('plan_slug', $planSlug);
            $wedding->save();
            
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
            
            // Fill the wedding to exactly the file limit
            $fileSize = mt_rand(1000, 100000); // Small files to avoid storage limit
            for ($i = 0; $i < $maxFiles; $i++) {
                $this->createMedia($wedding, $siteLayout, $fileSize);
            }
            
            // Clear cache to ensure fresh calculation
            $this->service->clearCache($wedding);
            
            // Attempt to upload one more file
            $newFileSize = mt_rand(1000, 100000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be false
            $this->assertFalse(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be false when file limit ({$maxFiles}) is reached"
            );
            
            // Assert: reason should not be null
            $this->assertNotNull(
                $result->reason,
                "Iteration {$iteration}: reason should not be null when upload is blocked"
            );
            
            // Assert: reason should be a non-empty string
            $this->assertNotEmpty(
                $result->reason,
                "Iteration {$iteration}: reason should be a non-empty string explaining why upload is blocked"
            );
        }
    }

    /**
     * Property 9: Verificação de Cota Bloqueia Uploads (Storage Limit)
     * 
     * For any wedding that has reached max_storage_bytes of their plan,
     * upload attempts must return QuotaCheckResult with canUpload=false
     * and an explanatory reason.
     * 
     * **Validates: Requirements 4.4**
     */
    #[Test]
    public function quota_check_blocks_uploads_when_storage_limit_reached(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random plan limits for this iteration
            $maxFiles = 1000; // High file limit to not hit file limit
            $maxStorageBytes = mt_rand(1000000, 10000000); // 1MB to 10MB for faster testing
            
            // Create a custom plan limit for this test
            $planSlug = 'test_storage_plan_' . $iteration;
            PlanLimit::updateOrCreate(
                ['plan_slug' => $planSlug],
                ['max_files' => $maxFiles, 'max_storage_bytes' => $maxStorageBytes]
            );
            
            // Create a wedding with this plan
            $wedding = Wedding::factory()->create();
            $wedding->setSetting('plan_slug', $planSlug);
            $wedding->save();
            
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
            
            // Fill the wedding to exactly the storage limit
            // Create files that sum up to exactly maxStorageBytes
            $remainingBytes = $maxStorageBytes;
            $fileCount = 0;
            while ($remainingBytes > 0 && $fileCount < $maxFiles) {
                $fileSize = min($remainingBytes, mt_rand(100000, 500000));
                $this->createMedia($wedding, $siteLayout, $fileSize);
                $remainingBytes -= $fileSize;
                $fileCount++;
            }
            
            // Clear cache to ensure fresh calculation
            $this->service->clearCache($wedding);
            
            // Attempt to upload one more file (any size > 0 should be blocked)
            $newFileSize = mt_rand(1000, 100000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be false
            $this->assertFalse(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be false when storage limit ({$maxStorageBytes} bytes) is reached"
            );
            
            // Assert: reason should not be null
            $this->assertNotNull(
                $result->reason,
                "Iteration {$iteration}: reason should not be null when upload is blocked due to storage limit"
            );
            
            // Assert: reason should be a non-empty string
            $this->assertNotEmpty(
                $result->reason,
                "Iteration {$iteration}: reason should be a non-empty string explaining why upload is blocked"
            );
        }
    }

    /**
     * Property 9: Verificação de Cota Bloqueia Uploads (Both Limits)
     * 
     * For any wedding that has reached both max_files and max_storage_bytes,
     * upload attempts must return QuotaCheckResult with canUpload=false
     * and an explanatory reason.
     * 
     * **Validates: Requirements 4.3, 4.4**
     */
    #[Test]
    public function quota_check_blocks_uploads_when_both_limits_reached(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random plan limits for this iteration
            $maxFiles = mt_rand(5, 20);
            // Calculate storage to be exactly filled when file limit is reached
            $avgFileSize = mt_rand(50000, 200000);
            $maxStorageBytes = $maxFiles * $avgFileSize;
            
            // Create a custom plan limit for this test
            $planSlug = 'test_both_plan_' . $iteration;
            PlanLimit::updateOrCreate(
                ['plan_slug' => $planSlug],
                ['max_files' => $maxFiles, 'max_storage_bytes' => $maxStorageBytes]
            );
            
            // Create a wedding with this plan
            $wedding = Wedding::factory()->create();
            $wedding->setSetting('plan_slug', $planSlug);
            $wedding->save();
            
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
            
            // Fill the wedding to exactly both limits
            for ($i = 0; $i < $maxFiles; $i++) {
                $this->createMedia($wedding, $siteLayout, $avgFileSize);
            }
            
            // Clear cache to ensure fresh calculation
            $this->service->clearCache($wedding);
            
            // Attempt to upload one more file
            $newFileSize = mt_rand(1000, 100000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be false
            $this->assertFalse(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be false when both limits are reached"
            );
            
            // Assert: reason should not be null
            $this->assertNotNull(
                $result->reason,
                "Iteration {$iteration}: reason should not be null when upload is blocked"
            );
        }
    }

    /**
     * Property 9: Verificação de Cota Bloqueia Uploads (Reason is explanatory)
     * 
     * Verifies that the reason field contains meaningful text when upload is blocked.
     * 
     * **Validates: Requirements 4.3, 4.4**
     */
    #[Test]
    public function quota_check_provides_explanatory_reason_when_blocked(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Create a wedding at file limit
            $maxFiles = mt_rand(5, 20);
            $planSlug = 'test_reason_plan_' . $iteration;
            PlanLimit::updateOrCreate(
                ['plan_slug' => $planSlug],
                ['max_files' => $maxFiles, 'max_storage_bytes' => 524288000]
            );
            
            $wedding = Wedding::factory()->create();
            $wedding->setSetting('plan_slug', $planSlug);
            $wedding->save();
            
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
            
            // Fill to limit
            for ($i = 0; $i < $maxFiles; $i++) {
                $this->createMedia($wedding, $siteLayout, mt_rand(1000, 10000));
            }
            
            $this->service->clearCache($wedding);
            
            $result = $this->service->canUpload($wedding, 1000, 1);
            
            // Assert: reason should contain meaningful text (at least 10 characters)
            $this->assertGreaterThanOrEqual(
                10,
                strlen($result->reason ?? ''),
                "Iteration {$iteration}: reason should be explanatory (at least 10 characters)"
            );
        }
    }

    /**
     * Property 9: Verificação de Cota Bloqueia Uploads (Allows upload below limit)
     * 
     * Verifies that uploads are allowed when wedding is below both limits.
     * This is the inverse property to ensure the blocking logic is correct.
     * 
     * **Validates: Requirements 4.3, 4.4**
     */
    #[Test]
    public function quota_check_allows_uploads_when_below_limits(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random plan limits
            $maxFiles = mt_rand(50, 100);
            $maxStorageBytes = mt_rand(50000000, 100000000); // 50MB to 100MB
            
            $planSlug = 'test_allow_plan_' . $iteration;
            PlanLimit::updateOrCreate(
                ['plan_slug' => $planSlug],
                ['max_files' => $maxFiles, 'max_storage_bytes' => $maxStorageBytes]
            );
            
            $wedding = Wedding::factory()->create();
            $wedding->setSetting('plan_slug', $planSlug);
            $wedding->save();
            
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
            
            // Fill to a random percentage below limit (0% to 80%)
            $fillPercentage = mt_rand(0, 80) / 100;
            $filesToCreate = (int) ($maxFiles * $fillPercentage);
            $avgFileSize = (int) (($maxStorageBytes * $fillPercentage) / max(1, $filesToCreate));
            
            for ($i = 0; $i < $filesToCreate; $i++) {
                $this->createMedia($wedding, $siteLayout, $avgFileSize);
            }
            
            $this->service->clearCache($wedding);
            
            // Attempt to upload a small file (should be allowed)
            $newFileSize = mt_rand(1000, 10000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be true when below limits
            $this->assertTrue(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be true when below limits (filled {$fillPercentage}%)"
            );
            
            // Assert: reason should be null when upload is allowed
            $this->assertNull(
                $result->reason,
                "Iteration {$iteration}: reason should be null when upload is allowed"
            );
        }
    }

    /**
     * Property 9: Verificação de Cota Bloqueia Uploads (Multiple files)
     * 
     * Verifies that uploading multiple files at once is blocked when it would exceed limits.
     * 
     * **Validates: Requirements 4.3, 4.4**
     */
    #[Test]
    public function quota_check_blocks_multiple_file_uploads_exceeding_limit(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $maxFiles = mt_rand(10, 30);
            $planSlug = 'test_multi_plan_' . $iteration;
            PlanLimit::updateOrCreate(
                ['plan_slug' => $planSlug],
                ['max_files' => $maxFiles, 'max_storage_bytes' => 524288000]
            );
            
            $wedding = Wedding::factory()->create();
            $wedding->setSetting('plan_slug', $planSlug);
            $wedding->save();
            
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
            
            // Fill to near limit (leave room for 1-3 files)
            $roomLeft = mt_rand(1, 3);
            $filesToCreate = $maxFiles - $roomLeft;
            
            for ($i = 0; $i < $filesToCreate; $i++) {
                $this->createMedia($wedding, $siteLayout, mt_rand(1000, 10000));
            }
            
            $this->service->clearCache($wedding);
            
            // Attempt to upload more files than room left
            $filesToUpload = $roomLeft + mt_rand(1, 5);
            $result = $this->service->canUpload($wedding, 1000, $filesToUpload);
            
            // Assert: canUpload should be false when uploading more files than room left
            $this->assertFalse(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be false when uploading {$filesToUpload} files with only {$roomLeft} slots left"
            );
            
            $this->assertNotNull(
                $result->reason,
                "Iteration {$iteration}: reason should not be null when multiple file upload is blocked"
            );
        }
    }
}
