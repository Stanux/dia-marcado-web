<?php

namespace Tests\Unit\DTOs;

use App\DTOs\QuotaUsage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QuotaUsage DTO.
 * 
 * Tests quota limit detection and near-limit alerts.
 * 
 * Validates: Requirements 5.1, 5.3, 5.5
 */
class QuotaUsageTest extends TestCase
{
    #[Test]
    public function it_creates_quota_usage_with_all_properties(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 50,
            maxFiles: 100,
            currentStorageBytes: 250000000,
            maxStorageBytes: 500000000,
            filesPercentage: 50.0,
            storagePercentage: 50.0,
        );

        $this->assertEquals(50, $usage->currentFiles);
        $this->assertEquals(100, $usage->maxFiles);
        $this->assertEquals(250000000, $usage->currentStorageBytes);
        $this->assertEquals(500000000, $usage->maxStorageBytes);
        $this->assertEquals(50.0, $usage->filesPercentage);
        $this->assertEquals(50.0, $usage->storagePercentage);
    }

    #[Test]
    public function is_at_limit_returns_true_when_files_at_100_percent(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 100,
            maxFiles: 100,
            currentStorageBytes: 250000000,
            maxStorageBytes: 500000000,
            filesPercentage: 100.0,
            storagePercentage: 50.0,
        );

        $this->assertTrue($usage->isAtLimit());
    }

    #[Test]
    public function is_at_limit_returns_true_when_storage_at_100_percent(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 50,
            maxFiles: 100,
            currentStorageBytes: 500000000,
            maxStorageBytes: 500000000,
            filesPercentage: 50.0,
            storagePercentage: 100.0,
        );

        $this->assertTrue($usage->isAtLimit());
    }

    #[Test]
    public function is_at_limit_returns_true_when_both_at_100_percent(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 100,
            maxFiles: 100,
            currentStorageBytes: 500000000,
            maxStorageBytes: 500000000,
            filesPercentage: 100.0,
            storagePercentage: 100.0,
        );

        $this->assertTrue($usage->isAtLimit());
    }

    #[Test]
    public function is_at_limit_returns_false_when_below_100_percent(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 99,
            maxFiles: 100,
            currentStorageBytes: 490000000,
            maxStorageBytes: 500000000,
            filesPercentage: 99.0,
            storagePercentage: 98.0,
        );

        $this->assertFalse($usage->isAtLimit());
    }

    #[Test]
    public function is_at_limit_returns_true_when_over_100_percent(): void
    {
        // Edge case: percentage could exceed 100 if limits changed after uploads
        $usage = new QuotaUsage(
            currentFiles: 110,
            maxFiles: 100,
            currentStorageBytes: 600000000,
            maxStorageBytes: 500000000,
            filesPercentage: 110.0,
            storagePercentage: 120.0,
        );

        $this->assertTrue($usage->isAtLimit());
    }

    #[Test]
    public function is_near_limit_returns_true_when_files_at_80_percent(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 80,
            maxFiles: 100,
            currentStorageBytes: 200000000,
            maxStorageBytes: 500000000,
            filesPercentage: 80.0,
            storagePercentage: 40.0,
        );

        $this->assertTrue($usage->isNearLimit());
    }

    #[Test]
    public function is_near_limit_returns_true_when_storage_at_80_percent(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 40,
            maxFiles: 100,
            currentStorageBytes: 400000000,
            maxStorageBytes: 500000000,
            filesPercentage: 40.0,
            storagePercentage: 80.0,
        );

        $this->assertTrue($usage->isNearLimit());
    }

    #[Test]
    public function is_near_limit_returns_false_when_below_80_percent(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 79,
            maxFiles: 100,
            currentStorageBytes: 395000000,
            maxStorageBytes: 500000000,
            filesPercentage: 79.0,
            storagePercentage: 79.0,
        );

        $this->assertFalse($usage->isNearLimit());
    }

    #[Test]
    public function is_near_limit_uses_custom_threshold(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 70,
            maxFiles: 100,
            currentStorageBytes: 350000000,
            maxStorageBytes: 500000000,
            filesPercentage: 70.0,
            storagePercentage: 70.0,
        );

        // At 70%, should be near limit with 0.7 threshold
        $this->assertTrue($usage->isNearLimit(0.7));
        
        // At 70%, should NOT be near limit with 0.8 threshold
        $this->assertFalse($usage->isNearLimit(0.8));
    }

    #[Test]
    public function is_near_limit_with_90_percent_threshold(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 90,
            maxFiles: 100,
            currentStorageBytes: 450000000,
            maxStorageBytes: 500000000,
            filesPercentage: 90.0,
            storagePercentage: 90.0,
        );

        $this->assertTrue($usage->isNearLimit(0.9));
        $this->assertFalse($usage->isNearLimit(0.95));
    }

    #[Test]
    public function is_near_limit_returns_true_when_at_100_percent(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 100,
            maxFiles: 100,
            currentStorageBytes: 500000000,
            maxStorageBytes: 500000000,
            filesPercentage: 100.0,
            storagePercentage: 100.0,
        );

        // At 100%, should always be near limit for any threshold <= 1.0
        $this->assertTrue($usage->isNearLimit(0.8));
        $this->assertTrue($usage->isNearLimit(0.9));
        $this->assertTrue($usage->isNearLimit(1.0));
    }

    #[Test]
    public function is_near_limit_with_zero_threshold(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 1,
            maxFiles: 100,
            currentStorageBytes: 1000,
            maxStorageBytes: 500000000,
            filesPercentage: 1.0,
            storagePercentage: 0.0002,
        );

        // With 0 threshold, any usage should be "near limit"
        $this->assertTrue($usage->isNearLimit(0.0));
    }

    #[Test]
    public function is_near_limit_with_empty_usage(): void
    {
        $usage = new QuotaUsage(
            currentFiles: 0,
            maxFiles: 100,
            currentStorageBytes: 0,
            maxStorageBytes: 500000000,
            filesPercentage: 0.0,
            storagePercentage: 0.0,
        );

        $this->assertFalse($usage->isNearLimit(0.8));
        $this->assertTrue($usage->isNearLimit(0.0));
    }
}
