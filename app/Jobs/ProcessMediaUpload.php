<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Media\BatchUploadServiceInterface;
use App\Models\SiteMedia;
use App\Models\UploadBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Job to process a media upload from the queue.
 * 
 * Handles validation, storage, and optimization of uploaded files.
 */
class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $batchId,
        public readonly string $tempFilePath,
        public readonly string $originalName,
        public readonly string $mimeType,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BatchUploadServiceInterface $batchUploadService): void
    {
        $batch = UploadBatch::find($this->batchId);

        if (!$batch) {
            Log::warning('Batch not found for media upload job', [
                'batch_id' => $this->batchId,
            ]);
            $this->cleanupTempFile();
            return;
        }

        if ($batch->status === 'cancelled') {
            Log::info('Skipping upload for cancelled batch', [
                'batch_id' => $this->batchId,
            ]);
            $this->cleanupTempFile();
            return;
        }

        // Check if temp file exists
        if (!Storage::disk('local')->exists($this->tempFilePath)) {
            Log::warning('Temp file not found for media upload', [
                'batch_id' => $this->batchId,
                'temp_path' => $this->tempFilePath,
            ]);
            $this->recordFailure($batch, 'Arquivo temporário não encontrado');
            return;
        }

        try {
            // Create UploadedFile from temp storage
            $fullPath = Storage::disk('local')->path($this->tempFilePath);
            $file = new UploadedFile(
                $fullPath,
                $this->originalName,
                $this->mimeType,
                null,
                true // test mode to skip is_uploaded_file check
            );

            // Process the file
            $result = $batchUploadService->processFile($batch, $file, $this->originalName);

            if (!$result->success) {
                Log::warning('Media upload processing failed', [
                    'batch_id' => $this->batchId,
                    'error' => $result->error,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Exception during media upload processing', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->recordFailure($batch, 'Erro durante processamento: ' . $e->getMessage());

        } finally {
            $this->cleanupTempFile();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Media upload job failed permanently', [
            'batch_id' => $this->batchId,
            'original_name' => $this->originalName,
            'error' => $exception->getMessage(),
        ]);

        $batch = UploadBatch::find($this->batchId);
        if ($batch) {
            $this->recordFailure($batch, 'Falha permanente no processamento');
        }

        $this->cleanupTempFile();
    }

    /**
     * Record a failure in the batch.
     */
    private function recordFailure(UploadBatch $batch, string $error): void
    {
        $batch->increment('failed_files');

        // Check if batch is complete
        $processed = $batch->completed_files + $batch->failed_files;
        if ($processed >= $batch->total_files) {
            $status = $batch->failed_files === $batch->total_files ? 'failed' : 'completed';
            $batch->update(['status' => $status]);
        }
    }

    /**
     * Clean up the temporary file.
     */
    private function cleanupTempFile(): void
    {
        if (Storage::disk('local')->exists($this->tempFilePath)) {
            Storage::disk('local')->delete($this->tempFilePath);
        }
    }
}
