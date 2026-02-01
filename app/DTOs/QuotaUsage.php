<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object representing quota usage information.
 * 
 * Tracks current usage of files and storage against plan limits,
 * providing methods to check if limits are reached or approaching.
 * 
 * @see Requirements 5.1, 5.3, 5.5
 */
readonly class QuotaUsage
{
    /**
     * Create a new QuotaUsage instance.
     *
     * @param int $currentFiles Current number of files uploaded
     * @param int $maxFiles Maximum number of files allowed by plan
     * @param int $currentStorageBytes Current storage used in bytes
     * @param int $maxStorageBytes Maximum storage allowed by plan in bytes
     * @param float $filesPercentage Percentage of file quota used (0-100)
     * @param float $storagePercentage Percentage of storage quota used (0-100)
     */
    public function __construct(
        public int $currentFiles,
        public int $maxFiles,
        public int $currentStorageBytes,
        public int $maxStorageBytes,
        public float $filesPercentage,
        public float $storagePercentage,
    ) {}

    /**
     * Check if either files or storage quota is at 100%.
     * 
     * Used to determine if uploads should be blocked.
     * 
     * @return bool True if at limit for files OR storage
     */
    public function isAtLimit(): bool
    {
        return $this->filesPercentage >= 100.0 || $this->storagePercentage >= 100.0;
    }

    /**
     * Check if either files or storage quota is near the limit.
     * 
     * Used to trigger warning alerts to users.
     * Default threshold is 0.8 (80%) per Requirement 5.3.
     * 
     * @param float $threshold Threshold as decimal (0.0 to 1.0), default 0.8 (80%)
     * @return bool True if either percentage >= threshold * 100
     */
    public function isNearLimit(float $threshold = 0.8): bool
    {
        $thresholdPercentage = $threshold * 100;
        
        return $this->filesPercentage >= $thresholdPercentage 
            || $this->storagePercentage >= $thresholdPercentage;
    }
}
