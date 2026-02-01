<?php

namespace Tests\Feature\Property\Media;

use App\DTOs\QuotaUsage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for QuotaUsage DTO.
 * 
 * @feature media-management
 * @property 12: Alerta de Cota em 80%
 * 
 * Validates: Requirements 5.3
 */
class QuotaUsagePropertyTest extends TestCase
{
    /**
     * Property 12: Alerta de Cota em 80%
     * 
     * For any QuotaUsage where filesPercentage >= 80% or storagePercentage >= 80%,
     * the method isNearLimit(0.8) must return true.
     * 
     * **Validates: Requirements 5.3**
     * 
     * @test
     * @feature media-management
     * @property 12: Alerta de Cota em 80%
     */
    public function near_limit_alert_triggers_at_80_percent_for_files(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random percentage between 80% and 100% (inclusive)
            $filesPercentage = $this->generatePercentageAtOrAboveThreshold(80.0);
            $storagePercentage = $this->generateRandomPercentage(0.0, 79.99);
            
            $usage = $this->createQuotaUsage($filesPercentage, $storagePercentage);
            
            $this->assertTrue(
                $usage->isNearLimit(0.8),
                "Iteration {$i}: isNearLimit(0.8) should return true when filesPercentage={$filesPercentage}% (>= 80%)"
            );
        }
    }

    /**
     * Property 12: Alerta de Cota em 80%
     * 
     * For any QuotaUsage where storagePercentage >= 80%,
     * the method isNearLimit(0.8) must return true.
     * 
     * **Validates: Requirements 5.3**
     * 
     * @test
     * @feature media-management
     * @property 12: Alerta de Cota em 80%
     */
    public function near_limit_alert_triggers_at_80_percent_for_storage(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random percentage between 80% and 100% (inclusive)
            $filesPercentage = $this->generateRandomPercentage(0.0, 79.99);
            $storagePercentage = $this->generatePercentageAtOrAboveThreshold(80.0);
            
            $usage = $this->createQuotaUsage($filesPercentage, $storagePercentage);
            
            $this->assertTrue(
                $usage->isNearLimit(0.8),
                "Iteration {$i}: isNearLimit(0.8) should return true when storagePercentage={$storagePercentage}% (>= 80%)"
            );
        }
    }

    /**
     * Property 12: Alerta de Cota em 80%
     * 
     * For any QuotaUsage where both filesPercentage >= 80% and storagePercentage >= 80%,
     * the method isNearLimit(0.8) must return true.
     * 
     * **Validates: Requirements 5.3**
     * 
     * @test
     * @feature media-management
     * @property 12: Alerta de Cota em 80%
     */
    public function near_limit_alert_triggers_when_both_at_80_percent(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random percentages both at or above 80%
            $filesPercentage = $this->generatePercentageAtOrAboveThreshold(80.0);
            $storagePercentage = $this->generatePercentageAtOrAboveThreshold(80.0);
            
            $usage = $this->createQuotaUsage($filesPercentage, $storagePercentage);
            
            $this->assertTrue(
                $usage->isNearLimit(0.8),
                "Iteration {$i}: isNearLimit(0.8) should return true when filesPercentage={$filesPercentage}% and storagePercentage={$storagePercentage}% (both >= 80%)"
            );
        }
    }

    /**
     * Property 12: Alerta de Cota em 80% (Negative case)
     * 
     * For any QuotaUsage where both filesPercentage < 80% and storagePercentage < 80%,
     * the method isNearLimit(0.8) must return false.
     * 
     * **Validates: Requirements 5.3**
     * 
     * @test
     * @feature media-management
     * @property 12: Alerta de Cota em 80%
     */
    public function near_limit_alert_does_not_trigger_below_80_percent(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate random percentages both below 80%
            $filesPercentage = $this->generateRandomPercentage(0.0, 79.99);
            $storagePercentage = $this->generateRandomPercentage(0.0, 79.99);
            
            $usage = $this->createQuotaUsage($filesPercentage, $storagePercentage);
            
            $this->assertFalse(
                $usage->isNearLimit(0.8),
                "Iteration {$i}: isNearLimit(0.8) should return false when filesPercentage={$filesPercentage}% and storagePercentage={$storagePercentage}% (both < 80%)"
            );
        }
    }

    /**
     * Property 12: Alerta de Cota em 80% (Boundary test)
     * 
     * Tests the exact boundary at 80% to ensure the >= comparison is correct.
     * 
     * **Validates: Requirements 5.3**
     * 
     * @test
     * @feature media-management
     * @property 12: Alerta de Cota em 80%
     */
    public function near_limit_alert_triggers_at_exact_80_percent_boundary(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Test exact 80% boundary for files
            $usage = $this->createQuotaUsage(80.0, $this->generateRandomPercentage(0.0, 79.99));
            $this->assertTrue(
                $usage->isNearLimit(0.8),
                "Iteration {$i}: isNearLimit(0.8) should return true when filesPercentage=80.0% (exact boundary)"
            );
            
            // Test exact 80% boundary for storage
            $usage = $this->createQuotaUsage($this->generateRandomPercentage(0.0, 79.99), 80.0);
            $this->assertTrue(
                $usage->isNearLimit(0.8),
                "Iteration {$i}: isNearLimit(0.8) should return true when storagePercentage=80.0% (exact boundary)"
            );
        }
    }

    /**
     * Property 12: Alerta de Cota em 80% (Extended range test)
     * 
     * Tests that percentages above 100% (possible after plan downgrade) still trigger alert.
     * 
     * **Validates: Requirements 5.3**
     * 
     * @test
     * @feature media-management
     * @property 12: Alerta de Cota em 80%
     */
    public function near_limit_alert_triggers_for_percentages_above_100(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate percentages above 100% (edge case: plan downgrade scenario)
            $filesPercentage = $this->generateRandomPercentage(100.0, 200.0);
            $storagePercentage = $this->generateRandomPercentage(100.0, 200.0);
            
            $usage = $this->createQuotaUsage($filesPercentage, $storagePercentage);
            
            $this->assertTrue(
                $usage->isNearLimit(0.8),
                "Iteration {$i}: isNearLimit(0.8) should return true when filesPercentage={$filesPercentage}% and storagePercentage={$storagePercentage}% (both > 100%)"
            );
        }
    }

    /**
     * Create a QuotaUsage instance with the given percentages.
     * 
     * Uses realistic values for files and storage based on percentages.
     */
    private function createQuotaUsage(float $filesPercentage, float $storagePercentage): QuotaUsage
    {
        $maxFiles = 100;
        $maxStorageBytes = 500000000; // 500MB
        
        $currentFiles = (int) round(($filesPercentage / 100) * $maxFiles);
        $currentStorageBytes = (int) round(($storagePercentage / 100) * $maxStorageBytes);
        
        return new QuotaUsage(
            currentFiles: $currentFiles,
            maxFiles: $maxFiles,
            currentStorageBytes: $currentStorageBytes,
            maxStorageBytes: $maxStorageBytes,
            filesPercentage: $filesPercentage,
            storagePercentage: $storagePercentage,
        );
    }

    /**
     * Generate a random percentage at or above the given threshold.
     */
    private function generatePercentageAtOrAboveThreshold(float $threshold): float
    {
        // Generate between threshold and 100% (with some buffer for edge cases)
        return $threshold + (mt_rand(0, 2000) / 100); // threshold to threshold+20%
    }

    /**
     * Generate a random percentage within the given range.
     */
    private function generateRandomPercentage(float $min, float $max): float
    {
        $range = $max - $min;
        return $min + (mt_rand(0, (int) ($range * 100)) / 100);
    }
}
