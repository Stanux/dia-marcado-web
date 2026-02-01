<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Site\MediaUploadServiceInterface;
use App\Models\SiteMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Job to optimize an uploaded image.
 * 
 * Generates variants (thumbnail, webp, 1x/2x) after successful upload.
 */
class OptimizeMediaImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $mediaId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MediaUploadServiceInterface $mediaUploadService): void
    {
        $media = SiteMedia::find($this->mediaId);

        if (!$media) {
            Log::warning('Media not found for optimization', [
                'media_id' => $this->mediaId,
            ]);
            return;
        }

        // Only optimize images
        if (!str_starts_with($media->mime_type ?? '', 'image/')) {
            Log::info('Skipping optimization for non-image media', [
                'media_id' => $this->mediaId,
                'mime_type' => $media->mime_type,
            ]);
            return;
        }

        // Check if file exists
        $disk = Storage::disk($media->disk ?? 'local');
        if (!$disk->exists($media->path)) {
            Log::warning('Media file not found for optimization', [
                'media_id' => $this->mediaId,
                'path' => $media->path,
            ]);
            return;
        }

        try {
            $fullPath = $disk->path($media->path);

            // Generate variants
            $variants = $mediaUploadService->optimizeImage($fullPath);

            // Update media record with variants
            if (!empty($variants)) {
                $existingVariants = $media->variants ?? [];
                $mergedVariants = array_merge($existingVariants, $variants);
                $media->update(['variants' => $mergedVariants]);

                Log::info('Media image optimized successfully', [
                    'media_id' => $this->mediaId,
                    'variants' => array_keys($variants),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Failed to optimize media image', [
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);

            // Don't fail the job - optimization is not critical
            // The original file is still available
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Media optimization job failed permanently', [
            'media_id' => $this->mediaId,
            'error' => $exception->getMessage(),
        ]);

        // Optimization failure is not critical - original file is still usable
    }
}
