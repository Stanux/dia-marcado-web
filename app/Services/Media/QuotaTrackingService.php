<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Contracts\Media\QuotaTrackingServiceInterface;
use App\DTOs\QuotaCheckResult;
use App\DTOs\QuotaUsage;
use App\Models\PlanLimit;
use App\Models\SiteMedia;
use App\Models\Wedding;
use Illuminate\Support\Facades\Cache;

/**
 * Service for tracking and managing storage and file quotas.
 * 
 * Provides methods to track current usage, check upload permissions,
 * and manage quota limits based on subscription plans.
 * 
 * Uses caching for performance optimization with automatic invalidation
 * when files are uploaded or deleted.
 * 
 * @see Requirements 5.1, 5.5, 5.6 - Quota tracking and display
 * @see Requirements 4.3, 4.4 - Blocking uploads when quota exceeded
 * @see Requirements 5.4 - Upgrade prompts for basic plan users
 */
class QuotaTrackingService implements QuotaTrackingServiceInterface
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache key prefix for quota data.
     */
    private const CACHE_KEY_PREFIX = 'quota:';

    /**
     * Default plan slug when wedding has no plan set.
     */
    private const DEFAULT_PLAN = PlanLimit::PLAN_BASIC;

    /**
     * Get the current quota usage for a wedding.
     * 
     * Calculates usage from SiteMedia records with status "completed"
     * and caches the result for performance.
     *
     * @param Wedding $wedding The wedding to get usage for
     * @return QuotaUsage DTO containing current usage and limits
     * 
     * @see Property 11: QuotaUsage.currentFiles must equal count of SiteMedia with status "completed"
     */
    public function getUsage(Wedding $wedding): QuotaUsage
    {
        $cacheKey = $this->getCacheKey($wedding);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($wedding) {
            return $this->calculateUsage($wedding);
        });
    }

    /**
     * Check if a wedding can upload files based on quota limits.
     * 
     * Verifies that the wedding has not exceeded their plan's file count
     * or storage limits. Returns appropriate messages for basic plan users.
     *
     * @param Wedding $wedding The wedding attempting to upload
     * @param int $fileSize The size of the file(s) to upload in bytes
     * @param int $fileCount The number of files to upload (default: 1)
     * @return QuotaCheckResult DTO with canUpload status and optional reason/upgrade message
     * 
     * @see Property 9: Weddings at max_files or max_storage_bytes must return canUpload=false
     * @see Property 13: Basic plan users at limit must receive upgradeMessage
     */
    public function canUpload(Wedding $wedding, int $fileSize, int $fileCount = 1): QuotaCheckResult
    {
        $usage = $this->getUsage($wedding);
        $planLimits = $this->getPlanLimits($wedding);
        $isBasicPlan = $this->isBasicPlan($wedding);

        // Check file count limit
        $newFileCount = $usage->currentFiles + $fileCount;
        if ($newFileCount > $planLimits->max_files) {
            return $this->createBlockedResult(
                reason: 'Cota de arquivos excedida. Você atingiu o limite máximo de ' . $planLimits->max_files . ' arquivos do seu plano.',
                isBasicPlan: $isBasicPlan,
                limitType: 'files'
            );
        }

        // Check storage limit
        $newStorageBytes = $usage->currentStorageBytes + $fileSize;
        if ($newStorageBytes > $planLimits->max_storage_bytes) {
            return $this->createBlockedResult(
                reason: 'Cota de armazenamento excedida. Você atingiu o limite máximo de armazenamento do seu plano.',
                isBasicPlan: $isBasicPlan,
                limitType: 'storage'
            );
        }

        return new QuotaCheckResult(canUpload: true);
    }

    /**
     * Get the plan limits for a wedding.
     * 
     * Returns the PlanLimit model for the wedding's subscription plan.
     * Falls back to basic plan if no plan is set or plan not found.
     *
     * @param Wedding $wedding The wedding to get limits for
     * @return PlanLimit The plan limit configuration
     */
    public function getPlanLimits(Wedding $wedding): PlanLimit
    {
        $planSlug = $this->getWeddingPlanSlug($wedding);
        
        $planLimit = PlanLimit::findBySlug($planSlug);
        
        // Fallback to basic plan if not found
        if (!$planLimit) {
            $planLimit = PlanLimit::findBySlug(self::DEFAULT_PLAN);
        }

        // If still not found, create a default limit object
        if (!$planLimit) {
            $planLimit = new PlanLimit([
                'plan_slug' => self::DEFAULT_PLAN,
                'max_files' => 100,
                'max_storage_bytes' => 524288000, // 500MB
            ]);
        }

        return $planLimit;
    }

    /**
     * Get the usage percentage breakdown for a wedding.
     * 
     * Returns an array with percentage values for both files and storage.
     *
     * @param Wedding $wedding The wedding to get percentages for
     * @return array{files: float, storage: float} Percentage usage for files and storage
     */
    public function getUsagePercentage(Wedding $wedding): array
    {
        $usage = $this->getUsage($wedding);

        return [
            'files' => $usage->filesPercentage,
            'storage' => $usage->storagePercentage,
        ];
    }

    /**
     * Clear the cached quota data for a wedding.
     * 
     * Should be called when files are uploaded or deleted to ensure
     * accurate quota information.
     *
     * @param Wedding $wedding The wedding to clear cache for
     * @return void
     */
    public function clearCache(Wedding $wedding): void
    {
        Cache::forget($this->getCacheKey($wedding));
    }

    /**
     * Calculate the actual usage from database.
     *
     * @param Wedding $wedding The wedding to calculate usage for
     * @return QuotaUsage The calculated usage
     */
    private function calculateUsage(Wedding $wedding): QuotaUsage
    {
        // Query only completed media files for this wedding
        $query = SiteMedia::where('wedding_id', $wedding->id)
            ->where('status', SiteMedia::STATUS_COMPLETED);

        $currentFiles = $query->count();
        $currentStorageBytes = (int) $query->sum('size');

        $planLimits = $this->getPlanLimits($wedding);

        // Calculate percentages (handle division by zero)
        $filesPercentage = $planLimits->max_files > 0
            ? ($currentFiles / $planLimits->max_files) * 100
            : 0.0;

        $storagePercentage = $planLimits->max_storage_bytes > 0
            ? ($currentStorageBytes / $planLimits->max_storage_bytes) * 100
            : 0.0;

        return new QuotaUsage(
            currentFiles: $currentFiles,
            maxFiles: $planLimits->max_files,
            currentStorageBytes: $currentStorageBytes,
            maxStorageBytes: $planLimits->max_storage_bytes,
            filesPercentage: $filesPercentage,
            storagePercentage: $storagePercentage,
        );
    }

    /**
     * Get the cache key for a wedding's quota data.
     *
     * @param Wedding $wedding The wedding
     * @return string The cache key
     */
    private function getCacheKey(Wedding $wedding): string
    {
        return self::CACHE_KEY_PREFIX . $wedding->id;
    }

    /**
     * Get the plan slug for a wedding.
     *
     * @param Wedding $wedding The wedding
     * @return string The plan slug
     */
    private function getWeddingPlanSlug(Wedding $wedding): string
    {
        // Check if wedding has a plan_slug attribute or setting
        if (isset($wedding->plan_slug)) {
            return $wedding->plan_slug;
        }

        // Try to get from settings
        $planSlug = $wedding->getSetting('plan_slug');
        if ($planSlug) {
            return $planSlug;
        }

        return self::DEFAULT_PLAN;
    }

    /**
     * Check if the wedding is on the basic plan.
     *
     * @param Wedding $wedding The wedding
     * @return bool True if on basic plan
     */
    private function isBasicPlan(Wedding $wedding): bool
    {
        return $this->getWeddingPlanSlug($wedding) === PlanLimit::PLAN_BASIC;
    }

    /**
     * Create a blocked upload result with appropriate messages.
     *
     * @param string $reason The reason for blocking
     * @param bool $isBasicPlan Whether the wedding is on basic plan
     * @param string $limitType The type of limit reached ('files' or 'storage')
     * @return QuotaCheckResult The blocked result
     */
    private function createBlockedResult(string $reason, bool $isBasicPlan, string $limitType): QuotaCheckResult
    {
        $upgradeMessage = null;

        if ($isBasicPlan) {
            $upgradeMessage = $limitType === 'files'
                ? 'Faça upgrade para o plano premium para aumentar seu limite de arquivos.'
                : 'Faça upgrade para o plano premium para aumentar seu espaço de armazenamento.';
        }

        return new QuotaCheckResult(
            canUpload: false,
            reason: $reason,
            upgradeMessage: $upgradeMessage,
        );
    }
}
