<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Contracts\Media\BatchUploadServiceInterface;
use App\Contracts\Media\QuotaTrackingServiceInterface;
use App\Contracts\Site\MediaUploadServiceInterface;
use App\DTOs\BatchStatus;
use App\DTOs\UploadResult;
use App\Exceptions\MediaUploadException;
use App\Models\Album;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\UploadBatch;
use App\Models\Wedding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for handling batch uploads with progress tracking.
 */
class BatchUploadService implements BatchUploadServiceInterface
{
    public function __construct(
        private readonly MediaUploadServiceInterface $mediaUploadService,
        private readonly QuotaTrackingServiceInterface $quotaTrackingService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function createBatch(Wedding $wedding, int $totalFiles, ?Album $album = null): UploadBatch
    {
        return DB::transaction(function () use ($wedding, $totalFiles, $album) {
            $batch = UploadBatch::create([
                'id' => Str::uuid()->toString(),
                'wedding_id' => $wedding->id,
                'album_id' => $album?->id,
                'total_files' => $totalFiles,
                'completed_files' => 0,
                'failed_files' => 0,
                'status' => 'pending',
            ]);

            Log::info('Upload batch created', [
                'batch_id' => $batch->id,
                'wedding_id' => $wedding->id,
                'total_files' => $totalFiles,
                'album_id' => $album?->id,
            ]);

            return $batch;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function processFile(UploadBatch $batch, UploadedFile $file, ?string $originalName = null): UploadResult
    {
        // Check if batch is still active
        if ($batch->status === 'cancelled') {
            $this->createFailedMediaRecord($batch, $originalName ?? $file->getClientOriginalName(), 'Batch foi cancelado');
            return UploadResult::failure(null, 'Batch foi cancelado');
        }

        // Update batch status to processing if first file
        if ($batch->status === 'pending') {
            $batch->update(['status' => 'processing']);
        }

        // Check quota before upload
        $quotaCheck = $this->quotaTrackingService->canUpload($batch->wedding, $file->getSize());
        if (!$quotaCheck->canUpload) {
            $errorMessage = $quotaCheck->reason ?? 'Cota excedida';
            $this->createFailedMediaRecord($batch, $originalName ?? $file->getClientOriginalName(), $errorMessage);
            $this->incrementFailed($batch, $errorMessage);
            return UploadResult::failure(null, $errorMessage);
        }

        // Pre-validate the file BEFORE any database operations
        // This prevents PostgreSQL transaction abort issues
        $siteLayout = $this->getOrCreateSiteLayout($batch->wedding);
        $validation = $this->mediaUploadService->validateFile($file);
        
        if (!$validation->isValid()) {
            $errorMessage = implode(', ', $validation->getErrors());
            $this->createFailedMediaRecord($batch, $originalName ?? $file->getClientOriginalName(), $errorMessage);
            $this->incrementFailed($batch, $errorMessage);

            Log::warning('File upload failed validation', [
                'batch_id' => $batch->id,
                'error' => $errorMessage,
                'original_name' => $originalName ?? $file->getClientOriginalName(),
            ]);

            return UploadResult::failure(null, $errorMessage);
        }

        try {
            // Upload the file (validation already passed)
            $media = $this->mediaUploadService->upload($file, $siteLayout);

            // Assign to album if specified
            if ($batch->album_id) {
                $media->update(['album_id' => $batch->album_id]);
            }

            // Update batch progress
            $media->update([
                'batch_id' => $batch->id,
                'status' => 'completed',
            ]);

            $this->incrementCompleted($batch);

            Log::info('File processed successfully', [
                'batch_id' => $batch->id,
                'media_id' => $media->id,
                'original_name' => $originalName ?? $file->getClientOriginalName(),
            ]);

            return UploadResult::success($media->id, $media);

        } catch (MediaUploadException $e) {
            $errorMessage = implode(', ', $e->getErrors());
            $this->incrementFailed($batch, $errorMessage);

            Log::warning('File upload failed', [
                'batch_id' => $batch->id,
                'error' => $errorMessage,
                'original_name' => $originalName ?? $file->getClientOriginalName(),
            ]);

            return UploadResult::failure(null, $errorMessage);

        } catch (\Throwable $e) {
            $this->incrementFailed($batch, $e->getMessage());

            Log::error('Unexpected error during file upload', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'original_name' => $originalName ?? $file->getClientOriginalName(),
            ]);

            return UploadResult::failure(null, 'Erro inesperado durante o upload');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelBatch(UploadBatch $batch): bool
    {
        if ($batch->status === 'completed' || $batch->status === 'cancelled') {
            return false;
        }

        $batch->update(['status' => 'cancelled']);

        // Mark any pending media as cancelled
        SiteMedia::where('batch_id', $batch->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'error_message' => 'Batch cancelado',
            ]);

        Log::info('Upload batch cancelled', [
            'batch_id' => $batch->id,
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchStatus(UploadBatch $batch): BatchStatus
    {
        $batch->refresh();

        $errors = SiteMedia::where('batch_id', $batch->id)
            ->where('status', 'failed')
            ->whereNotNull('error_message')
            ->pluck('error_message')
            ->toArray();

        return new BatchStatus(
            batchId: $batch->id,
            total: $batch->total_files,
            completed: $batch->completed_files,
            failed: $batch->failed_files,
            pending: $batch->total_files - $batch->completed_files - $batch->failed_files,
            errors: $errors,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBatches(Wedding $wedding, bool $includeCompleted = false): Collection
    {
        $query = UploadBatch::where('wedding_id', $wedding->id)
            ->orderBy('created_at', 'desc');

        if (!$includeCompleted) {
            $query->whereNotIn('status', ['completed', 'cancelled']);
        }

        return $query->get();
    }

    /**
     * Increment completed files count and check if batch is done.
     */
    private function incrementCompleted(UploadBatch $batch): void
    {
        $batch->increment('completed_files');
        $this->checkBatchCompletion($batch);
    }

    /**
     * Increment failed files count and check if batch is done.
     */
    private function incrementFailed(UploadBatch $batch, string $error): void
    {
        $batch->increment('failed_files');
        $this->checkBatchCompletion($batch);
    }

    /**
     * Check if batch is complete and update status.
     */
    private function checkBatchCompletion(UploadBatch $batch): void
    {
        $batch->refresh();

        $processed = $batch->completed_files + $batch->failed_files;
        
        if ($processed >= $batch->total_files) {
            $status = $batch->failed_files === $batch->total_files ? 'failed' : 'completed';
            $batch->update(['status' => $status]);

            Log::info('Upload batch completed', [
                'batch_id' => $batch->id,
                'status' => $status,
                'completed' => $batch->completed_files,
                'failed' => $batch->failed_files,
            ]);
        }
    }

    /**
     * Create a SiteMedia record for a failed upload to track error messages.
     */
    private function createFailedMediaRecord(UploadBatch $batch, string $originalName, string $errorMessage): void
    {
        $siteLayout = $this->getOrCreateSiteLayout($batch->wedding);
        
        SiteMedia::create([
            'site_layout_id' => $siteLayout->id,
            'wedding_id' => $batch->wedding_id,
            'batch_id' => $batch->id,
            'album_id' => $batch->album_id,
            'original_name' => $originalName,
            'path' => '', // No file stored for failed uploads
            'size' => 0,
            'mime_type' => 'application/octet-stream',
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get or create a site layout for the wedding.
     */
    private function getOrCreateSiteLayout(Wedding $wedding): SiteLayout
    {
        $siteLayout = SiteLayout::where('wedding_id', $wedding->id)->first();

        if (!$siteLayout) {
            // Create a minimal site layout for media storage
            // Use UUID for slug to ensure uniqueness
            $siteLayout = SiteLayout::create([
                'wedding_id' => $wedding->id,
                'slug' => Str::uuid()->toString(),
                'draft_content' => json_encode(['sections' => []]),
            ]);
        }

        return $siteLayout;
    }
}
