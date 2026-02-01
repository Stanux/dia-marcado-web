<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Media\QuotaTrackingServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CheckUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for quota management operations.
 * 
 * Handles quota usage display and upload permission checks.
 * 
 * @Requirements: 5.1, 4.3, 4.4
 */
class QuotaController extends Controller
{
    public function __construct(
        private readonly QuotaTrackingServiceInterface $quotaTrackingService,
    ) {}

    /**
     * Get current quota usage.
     * 
     * GET /api/quota
     * 
     * @Requirements: 5.1
     */
    public function show(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        $usage = $this->quotaTrackingService->getUsage($wedding);
        $limits = $this->quotaTrackingService->getPlanLimits($wedding);

        return response()->json([
            'data' => [
                'files' => [
                    'current' => $usage->currentFiles,
                    'max' => $usage->maxFiles,
                    'percentage' => $usage->filesPercentage,
                ],
                'storage' => [
                    'current_bytes' => $usage->currentStorageBytes,
                    'max_bytes' => $usage->maxStorageBytes,
                    'current_mb' => round($usage->currentStorageBytes / 1024 / 1024, 2),
                    'max_mb' => round($usage->maxStorageBytes / 1024 / 1024, 2),
                    'percentage' => $usage->storagePercentage,
                ],
                'plan' => $limits->plan_slug,
                'is_at_limit' => $usage->isAtLimit(),
                'is_near_limit' => $usage->isNearLimit(0.8),
            ],
        ]);
    }

    /**
     * Check if upload is allowed.
     * 
     * POST /api/quota/check
     * 
     * @Requirements: 4.3, 4.4
     */
    public function checkUpload(CheckUploadRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding;
        $validated = $request->validated();

        $fileSize = $validated['file_size'];
        $fileCount = $validated['file_count'] ?? 1;

        $result = $this->quotaTrackingService->canUpload($wedding, $fileSize, $fileCount);

        return response()->json([
            'data' => [
                'can_upload' => $result->canUpload,
                'reason' => $result->reason,
                'upgrade_message' => $result->upgradeMessage,
            ],
        ]);
    }
}
