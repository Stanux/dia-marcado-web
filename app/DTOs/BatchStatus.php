<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object representing the status of a batch upload.
 * 
 * Tracks the progress of multiple file uploads in a batch,
 * providing methods to check completion status and calculate progress.
 * 
 * @see Requirements 1.5
 */
readonly class BatchStatus
{
    /**
     * Create a new BatchStatus instance.
     *
     * @param string $batchId Unique identifier for the batch
     * @param int $total Total number of files in the batch
     * @param int $completed Number of files successfully uploaded
     * @param int $failed Number of files that failed to upload
     * @param int $pending Number of files still waiting to be processed
     * @param array<int, string> $errors List of error messages for failed uploads
     */
    public function __construct(
        public string $batchId,
        public int $total,
        public int $completed,
        public int $failed,
        public int $pending,
        public array $errors,
    ) {}

    /**
     * Check if the batch upload is complete.
     * 
     * A batch is complete when there are no more pending files,
     * meaning all files have been either completed or failed.
     * 
     * @return bool True if no files are pending (all processed)
     */
    public function isComplete(): bool
    {
        return $this->pending === 0;
    }

    /**
     * Calculate the progress percentage of the batch upload.
     * 
     * Progress is calculated as (completed + failed) / total * 100.
     * Returns 100% if total is 0 to handle edge case of empty batch.
     * 
     * @return float Progress percentage (0.0 to 100.0)
     */
    public function getProgressPercentage(): float
    {
        if ($this->total === 0) {
            return 100.0;
        }

        return (($this->completed + $this->failed) / $this->total) * 100;
    }
}
