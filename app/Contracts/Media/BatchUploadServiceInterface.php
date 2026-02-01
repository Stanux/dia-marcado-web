<?php

declare(strict_types=1);

namespace App\Contracts\Media;

use App\DTOs\BatchStatus;
use App\DTOs\UploadResult;
use App\Models\Album;
use App\Models\UploadBatch;
use App\Models\Wedding;
use Illuminate\Http\UploadedFile;

/**
 * Interface for batch upload service.
 * 
 * Handles multi-file uploads with progress tracking and error isolation.
 */
interface BatchUploadServiceInterface
{
    /**
     * Create a new upload batch.
     * 
     * Creates pending entries for each file in the batch.
     * 
     * @param Wedding $wedding The wedding context
     * @param int $totalFiles Total number of files to upload
     * @param Album|null $album Optional album to assign files to
     * @return UploadBatch The created batch
     */
    public function createBatch(Wedding $wedding, int $totalFiles, ?Album $album = null): UploadBatch;

    /**
     * Process a single file in a batch.
     * 
     * Validates, stores, and optimizes the file.
     * Updates batch progress on completion.
     * 
     * @param UploadBatch $batch The batch this file belongs to
     * @param UploadedFile $file The file to process
     * @param string|null $originalName Original filename for tracking
     * @return UploadResult Result of the upload operation
     */
    public function processFile(UploadBatch $batch, UploadedFile $file, ?string $originalName = null): UploadResult;

    /**
     * Cancel an upload batch.
     * 
     * Marks remaining pending files as cancelled.
     * Does not delete already completed uploads.
     * 
     * @param UploadBatch $batch The batch to cancel
     * @return bool True if cancelled successfully
     */
    public function cancelBatch(UploadBatch $batch): bool;

    /**
     * Get the current status of a batch.
     * 
     * @param UploadBatch $batch The batch to check
     * @return BatchStatus Current status with progress info
     */
    public function getBatchStatus(UploadBatch $batch): BatchStatus;

    /**
     * Get all batches for a wedding.
     * 
     * @param Wedding $wedding The wedding context
     * @param bool $includeCompleted Include completed batches
     * @return \Illuminate\Support\Collection<UploadBatch>
     */
    public function getBatches(Wedding $wedding, bool $includeCompleted = false): \Illuminate\Support\Collection;
}
