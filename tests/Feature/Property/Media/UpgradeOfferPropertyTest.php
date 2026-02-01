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
 * Property-based tests for upgrade offer functionality.
 * 
 * @feature media-management
 * @property 13: Oferta de Upgrade para Plano Básico em 100%
 * 
 * **Validates: Requirements 5.4**
 */
class UpgradeOfferPropertyTest extends TestCase
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
    private function createWeddingWithLayout(string $planSlug): array
    {
        $wedding = Wedding::factory()->create();
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
     * Property 13: Oferta de Upgrade para Plano Básico em 100%
     * 
     * For any wedding on basic plan where QuotaUsage.isAtLimit() returns true,
     * QuotaCheckResult must include a non-null upgradeMessage.
     * 
     * This test verifies that basic plan users at their file limit receive
     * an upgrade message when attempting to upload.
     * 
     * **Validates: Requirements 5.4**
     */
    #[Test]
    public function basic_plan_at_file_limit_receives_upgrade_message(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random plan limits for this iteration
            $maxFiles = mt_rand(5, 50);
            $maxStorageBytes = 524288000; // 500MB - large enough to not hit storage limit
            
            // Update the standard basic plan with random limits for this iteration
            PlanLimit::updateOrCreate(
                ['plan_slug' => PlanLimit::PLAN_BASIC],
                ['max_files' => $maxFiles, 'max_storage_bytes' => $maxStorageBytes]
            );
            
            // Create a wedding on the standard basic plan
            $data = $this->createWeddingWithLayout(PlanLimit::PLAN_BASIC);
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Fill the wedding to exactly the file limit
            $fileSize = mt_rand(1000, 100000);
            for ($i = 0; $i < $maxFiles; $i++) {
                $this->createMedia($wedding, $siteLayout, $fileSize);
            }
            
            // Clear cache to ensure fresh calculation
            $this->service->clearCache($wedding);
            
            // Verify that QuotaUsage.isAtLimit() returns true
            $usage = $this->service->getUsage($wedding);
            $this->assertTrue(
                $usage->isAtLimit(),
                "Iteration {$iteration}: QuotaUsage.isAtLimit() should return true when at file limit"
            );
            
            // Attempt to upload one more file
            $newFileSize = mt_rand(1000, 100000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be false
            $this->assertFalse(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be false when at file limit"
            );
            
            // Assert: upgradeMessage should NOT be null for basic plan users at limit
            $this->assertNotNull(
                $result->upgradeMessage,
                "Iteration {$iteration}: upgradeMessage should not be null for basic plan users at file limit"
            );
            
            // Assert: upgradeMessage should be a non-empty string
            $this->assertNotEmpty(
                $result->upgradeMessage,
                "Iteration {$iteration}: upgradeMessage should be a non-empty string"
            );
        }
    }

    /**
     * Property 13: Oferta de Upgrade para Plano Básico em 100%
     * 
     * For any wedding on basic plan where QuotaUsage.isAtLimit() returns true
     * due to storage limit, QuotaCheckResult must include a non-null upgradeMessage.
     * 
     * **Validates: Requirements 5.4**
     */
    #[Test]
    public function basic_plan_at_storage_limit_receives_upgrade_message(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random plan limits for this iteration
            $maxFiles = 1000; // High file limit to not hit file limit
            $maxStorageBytes = mt_rand(1000000, 10000000); // 1MB to 10MB for faster testing
            
            // Update the standard basic plan with random limits for this iteration
            PlanLimit::updateOrCreate(
                ['plan_slug' => PlanLimit::PLAN_BASIC],
                ['max_files' => $maxFiles, 'max_storage_bytes' => $maxStorageBytes]
            );
            
            // Create a wedding on the standard basic plan
            $data = $this->createWeddingWithLayout(PlanLimit::PLAN_BASIC);
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Fill the wedding to exactly the storage limit
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
            
            // Verify that QuotaUsage.isAtLimit() returns true
            $usage = $this->service->getUsage($wedding);
            $this->assertTrue(
                $usage->isAtLimit(),
                "Iteration {$iteration}: QuotaUsage.isAtLimit() should return true when at storage limit"
            );
            
            // Attempt to upload one more file
            $newFileSize = mt_rand(1000, 100000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be false
            $this->assertFalse(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be false when at storage limit"
            );
            
            // Assert: upgradeMessage should NOT be null for basic plan users at limit
            $this->assertNotNull(
                $result->upgradeMessage,
                "Iteration {$iteration}: upgradeMessage should not be null for basic plan users at storage limit"
            );
            
            // Assert: upgradeMessage should be a non-empty string
            $this->assertNotEmpty(
                $result->upgradeMessage,
                "Iteration {$iteration}: upgradeMessage should be a non-empty string"
            );
        }
    }

    /**
     * Property 13: Oferta de Upgrade para Plano Básico em 100% (Negative case)
     * 
     * For any wedding on premium plan where QuotaUsage.isAtLimit() returns true,
     * QuotaCheckResult should NOT include an upgradeMessage (since they're already
     * on the highest plan).
     * 
     * **Validates: Requirements 5.4**
     */
    #[Test]
    public function premium_plan_at_limit_does_not_receive_upgrade_message(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random plan limits for this iteration
            $maxFiles = mt_rand(5, 50);
            $maxStorageBytes = 524288000; // 500MB
            
            // Create a custom premium plan limit for this test
            $planSlug = PlanLimit::PLAN_PREMIUM;
            PlanLimit::updateOrCreate(
                ['plan_slug' => $planSlug],
                ['max_files' => $maxFiles, 'max_storage_bytes' => $maxStorageBytes]
            );
            
            // Create a wedding on premium plan
            $data = $this->createWeddingWithLayout($planSlug);
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Fill the wedding to exactly the file limit
            $fileSize = mt_rand(1000, 100000);
            for ($i = 0; $i < $maxFiles; $i++) {
                $this->createMedia($wedding, $siteLayout, $fileSize);
            }
            
            // Clear cache to ensure fresh calculation
            $this->service->clearCache($wedding);
            
            // Verify that QuotaUsage.isAtLimit() returns true
            $usage = $this->service->getUsage($wedding);
            $this->assertTrue(
                $usage->isAtLimit(),
                "Iteration {$iteration}: QuotaUsage.isAtLimit() should return true when at file limit"
            );
            
            // Attempt to upload one more file
            $newFileSize = mt_rand(1000, 100000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be false
            $this->assertFalse(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be false when at file limit"
            );
            
            // Assert: upgradeMessage should be null for premium plan users
            $this->assertNull(
                $result->upgradeMessage,
                "Iteration {$iteration}: upgradeMessage should be null for premium plan users at limit"
            );
        }
    }

    /**
     * Property 13: Oferta de Upgrade para Plano Básico em 100%
     * 
     * Verifies that the upgrade message contains meaningful text suggesting
     * an upgrade to premium plan.
     * 
     * **Validates: Requirements 5.4**
     */
    #[Test]
    public function upgrade_message_contains_meaningful_upgrade_suggestion(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Create a basic plan with small limits
            $maxFiles = mt_rand(5, 20);
            
            // Update the standard basic plan with random limits for this iteration
            PlanLimit::updateOrCreate(
                ['plan_slug' => PlanLimit::PLAN_BASIC],
                ['max_files' => $maxFiles, 'max_storage_bytes' => 524288000]
            );
            
            // Create a wedding on the standard basic plan
            $data = $this->createWeddingWithLayout(PlanLimit::PLAN_BASIC);
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Fill to limit
            for ($i = 0; $i < $maxFiles; $i++) {
                $this->createMedia($wedding, $siteLayout, mt_rand(1000, 10000));
            }
            
            $this->service->clearCache($wedding);
            
            $result = $this->service->canUpload($wedding, 1000, 1);
            
            // Assert: upgradeMessage should contain meaningful text (at least 10 characters)
            $this->assertGreaterThanOrEqual(
                10,
                strlen($result->upgradeMessage ?? ''),
                "Iteration {$iteration}: upgradeMessage should be meaningful (at least 10 characters)"
            );
            
            // Assert: upgradeMessage should mention upgrade or premium
            $messageContainsUpgradeKeyword = 
                stripos($result->upgradeMessage ?? '', 'upgrade') !== false ||
                stripos($result->upgradeMessage ?? '', 'premium') !== false;
            
            $this->assertTrue(
                $messageContainsUpgradeKeyword,
                "Iteration {$iteration}: upgradeMessage should mention 'upgrade' or 'premium'"
            );
        }
    }

    /**
     * Property 13: Oferta de Upgrade para Plano Básico em 100%
     * 
     * Verifies that basic plan users NOT at limit do NOT receive upgrade message.
     * This is the inverse property to ensure upgrade messages are only shown at limit.
     * 
     * **Validates: Requirements 5.4**
     */
    #[Test]
    public function basic_plan_below_limit_does_not_receive_upgrade_message(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random plan limits
            $maxFiles = mt_rand(50, 100);
            $maxStorageBytes = mt_rand(50000000, 100000000); // 50MB to 100MB
            
            // Update the standard basic plan with random limits for this iteration
            PlanLimit::updateOrCreate(
                ['plan_slug' => PlanLimit::PLAN_BASIC],
                ['max_files' => $maxFiles, 'max_storage_bytes' => $maxStorageBytes]
            );
            
            // Create a wedding on the standard basic plan
            $data = $this->createWeddingWithLayout(PlanLimit::PLAN_BASIC);
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Fill to a random percentage below limit (0% to 80%)
            $fillPercentage = mt_rand(0, 80) / 100;
            $filesToCreate = (int) ($maxFiles * $fillPercentage);
            $avgFileSize = $filesToCreate > 0 
                ? (int) (($maxStorageBytes * $fillPercentage) / $filesToCreate)
                : 0;
            
            for ($i = 0; $i < $filesToCreate; $i++) {
                $this->createMedia($wedding, $siteLayout, $avgFileSize);
            }
            
            $this->service->clearCache($wedding);
            
            // Verify that QuotaUsage.isAtLimit() returns false
            $usage = $this->service->getUsage($wedding);
            $this->assertFalse(
                $usage->isAtLimit(),
                "Iteration {$iteration}: QuotaUsage.isAtLimit() should return false when below limit"
            );
            
            // Attempt to upload a small file (should be allowed)
            $newFileSize = mt_rand(1000, 10000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be true when below limits
            $this->assertTrue(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be true when below limits"
            );
            
            // Assert: upgradeMessage should be null when upload is allowed
            $this->assertNull(
                $result->upgradeMessage,
                "Iteration {$iteration}: upgradeMessage should be null when upload is allowed"
            );
        }
    }

    /**
     * Property 13: Oferta de Upgrade para Plano Básico em 100%
     * 
     * Verifies that the standard basic plan (not custom test plans) also
     * receives upgrade messages when at limit.
     * 
     * **Validates: Requirements 5.4**
     */
    #[Test]
    public function standard_basic_plan_at_limit_receives_upgrade_message(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Use the standard basic plan
            $planSlug = PlanLimit::PLAN_BASIC;
            $planLimit = PlanLimit::findBySlug($planSlug);
            
            // Create a wedding on standard basic plan
            $data = $this->createWeddingWithLayout($planSlug);
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Fill the wedding to exactly the file limit
            $fileSize = mt_rand(1000, 10000);
            for ($i = 0; $i < $planLimit->max_files; $i++) {
                $this->createMedia($wedding, $siteLayout, $fileSize);
            }
            
            // Clear cache to ensure fresh calculation
            $this->service->clearCache($wedding);
            
            // Verify that QuotaUsage.isAtLimit() returns true
            $usage = $this->service->getUsage($wedding);
            $this->assertTrue(
                $usage->isAtLimit(),
                "Iteration {$iteration}: QuotaUsage.isAtLimit() should return true when at file limit"
            );
            
            // Attempt to upload one more file
            $newFileSize = mt_rand(1000, 10000);
            $result = $this->service->canUpload($wedding, $newFileSize, 1);
            
            // Assert: canUpload should be false
            $this->assertFalse(
                $result->canUpload,
                "Iteration {$iteration}: canUpload should be false when at file limit"
            );
            
            // Assert: upgradeMessage should NOT be null for basic plan users at limit
            $this->assertNotNull(
                $result->upgradeMessage,
                "Iteration {$iteration}: upgradeMessage should not be null for standard basic plan users at limit"
            );
        }
    }
}
