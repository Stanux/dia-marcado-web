<?php

declare(strict_types=1);

namespace App\Contracts\Media;

use App\DTOs\QuotaCheckResult;
use App\DTOs\QuotaUsage;
use App\Models\PlanLimit;
use App\Models\Wedding;

/**
 * Interface for quota tracking service.
 * 
 * Provides methods to track and manage storage and file quotas
 * for weddings based on their subscription plan limits.
 * 
 * @see Requirements 5.1 - Dashboard widget showing quota usage
 * @see Requirements 4.3, 4.4 - Blocking uploads when quota exceeded
 * @see Requirements 5.4 - Upgrade prompts for basic plan users
 */
interface QuotaTrackingServiceInterface
{
    /**
     * Get the current quota usage for a wedding.
     * 
     * Returns detailed information about current file count and storage usage
     * compared to the plan limits, including percentage calculations.
     * 
     * The usage is calculated from SiteMedia records with status "completed"
     * for the given wedding.
     *
     * @param Wedding $wedding The wedding to get usage for
     * @return QuotaUsage DTO containing current usage and limits
     * 
     * @see Requirements 5.1, 5.5, 5.6
     */
    public function getUsage(Wedding $wedding): QuotaUsage;

    /**
     * Check if a wedding can upload files based on quota limits.
     * 
     * Verifies that the wedding has not exceeded their plan's file count
     * or storage limits. Returns a result indicating whether upload is
     * allowed, with a reason if blocked and an upgrade message for
     * basic plan users at their limit.
     *
     * @param Wedding $wedding The wedding attempting to upload
     * @param int $fileSize The size of the file(s) to upload in bytes
     * @param int $fileCount The number of files to upload (default: 1)
     * @return QuotaCheckResult DTO with canUpload status and optional reason/upgrade message
     * 
     * @see Requirements 4.3, 4.4, 5.4
     */
    public function canUpload(Wedding $wedding, int $fileSize, int $fileCount = 1): QuotaCheckResult;

    /**
     * Get the plan limits for a wedding.
     * 
     * Returns the PlanLimit model containing max_files and max_storage_bytes
     * for the wedding's current subscription plan.
     *
     * @param Wedding $wedding The wedding to get limits for
     * @return PlanLimit The plan limit configuration
     * 
     * @see Requirements 4.1, 4.2, 4.5
     */
    public function getPlanLimits(Wedding $wedding): PlanLimit;

    /**
     * Get the usage percentage breakdown for a wedding.
     * 
     * Returns an array with percentage values for both files and storage,
     * useful for displaying progress bars and quota widgets.
     * 
     * Format: ['files' => float, 'storage' => float]
     * Values are percentages from 0.0 to 100.0 (or higher if over limit).
     *
     * @param Wedding $wedding The wedding to get percentages for
     * @return array{files: float, storage: float} Percentage usage for files and storage
     * 
     * @see Requirements 5.1, 5.5
     */
    public function getUsagePercentage(Wedding $wedding): array;
}
