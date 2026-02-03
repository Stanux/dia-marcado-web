<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Contracts\Media\BatchUploadServiceInterface;
use App\Jobs\ProcessMediaUpload;
use App\Models\UploadBatch;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Testes para o job ProcessMediaUpload.
 * 
 * Valida o processamento assíncrono de uploads de mídia.
 */
class ProcessMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar storage fake
        Storage::fake('local');
    }

    /**
     * Testa que o job processa arquivo com sucesso.
     */
    public function test_processes_file_successfully(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'total_files' => 1,
            'status' => 'pending',
        ]);

        // Criar arquivo temporário
        $tempPath = 'temp/test-file.jpg';
        Storage::disk('local')->put($tempPath, UploadedFile::fake()->image('test.jpg')->getContent());

        // Mock do serviço
        $mockService = $this->createMock(BatchUploadServiceInterface::class);
        $mockService->expects($this->once())
            ->method('processFile')
            ->with(
                $this->callback(fn($b) => $b->id === $batch->id),
                $this->isInstanceOf(UploadedFile::class),
                'test.jpg'
            )
            ->willReturn(\App\DTOs\UploadResult::success('test-id', new \App\Models\SiteMedia()));

        $this->app->instance(BatchUploadServiceInterface::class, $mockService);

        // Act
        $job = new ProcessMediaUpload(
            $batch->id,
            $tempPath,
            'test.jpg',
            'image/jpeg'
        );
        $job->handle($mockService);

        // Assert
        // Arquivo temporário deve ser removido
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }

    /**
     * Testa que o job lida com batch não encontrado.
     */
    public function test_handles_batch_not_found(): void
    {
        // Arrange
        $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Batch not found for media upload job', [
                'batch_id' => $nonExistentId,
            ]);

        $tempPath = 'temp/test-file.jpg';
        Storage::disk('local')->put($tempPath, 'content');

        $mockService = $this->createMock(BatchUploadServiceInterface::class);
        $mockService->expects($this->never())->method('processFile');

        // Act
        $job = new ProcessMediaUpload(
            $nonExistentId,
            $tempPath,
            'test.jpg',
            'image/jpeg'
        );
        $job->handle($mockService);

        // Assert
        // Arquivo temporário deve ser removido
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }

    /**
     * Testa que o job pula processamento de batch cancelado.
     */
    public function test_skips_cancelled_batch(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'status' => 'cancelled',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Skipping upload for cancelled batch', [
                'batch_id' => $batch->id,
            ]);

        $tempPath = 'temp/test-file.jpg';
        Storage::disk('local')->put($tempPath, 'content');

        $mockService = $this->createMock(BatchUploadServiceInterface::class);
        $mockService->expects($this->never())->method('processFile');

        // Act
        $job = new ProcessMediaUpload(
            $batch->id,
            $tempPath,
            'test.jpg',
            'image/jpeg'
        );
        $job->handle($mockService);

        // Assert
        // Arquivo temporário deve ser removido
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }

    /**
     * Testa que o job lida com arquivo temporário não encontrado.
     */
    public function test_handles_temp_file_not_found(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'total_files' => 1,
            'status' => 'pending',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Temp file not found for media upload', [
                'batch_id' => $batch->id,
                'temp_path' => 'temp/non-existent.jpg',
            ]);

        $mockService = $this->createMock(BatchUploadServiceInterface::class);
        $mockService->expects($this->never())->method('processFile');

        // Act
        $job = new ProcessMediaUpload(
            $batch->id,
            'temp/non-existent.jpg',
            'test.jpg',
            'image/jpeg'
        );
        $job->handle($mockService);

        // Assert
        $batch->refresh();
        $this->assertEquals(1, $batch->failed_files);
    }

    /**
     * Testa que o job lida com exceções durante processamento.
     */
    public function test_handles_processing_exception(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'total_files' => 1,
            'status' => 'pending',
        ]);

        $tempPath = 'temp/test-file.jpg';
        Storage::disk('local')->put($tempPath, UploadedFile::fake()->image('test.jpg')->getContent());

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($batch) {
                return $message === 'Exception during media upload processing' &&
                       $context['batch_id'] === $batch->id;
            });

        $mockService = $this->createMock(BatchUploadServiceInterface::class);
        $mockService->expects($this->once())
            ->method('processFile')
            ->willThrowException(new \Exception('Test exception'));

        // Act
        $job = new ProcessMediaUpload(
            $batch->id,
            $tempPath,
            'test.jpg',
            'image/jpeg'
        );
        $job->handle($mockService);

        // Assert
        $batch->refresh();
        $this->assertEquals(1, $batch->failed_files);
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }

    /**
     * Testa que o job tem configuração correta de retry.
     */
    public function test_has_correct_retry_configuration(): void
    {
        // Arrange
        $job = new ProcessMediaUpload(
            'batch-id',
            'temp/file.jpg',
            'test.jpg',
            'image/jpeg'
        );

        // Assert
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(30, $job->backoff);
    }

    /**
     * Testa que o método failed registra erro permanente.
     */
    public function test_failed_method_logs_permanent_failure(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'total_files' => 1,
            'status' => 'pending',
        ]);

        $tempPath = 'temp/test-file.jpg';
        Storage::disk('local')->put($tempPath, 'content');

        Log::shouldReceive('error')
            ->once()
            ->with('Media upload job failed permanently', [
                'batch_id' => $batch->id,
                'original_name' => 'test.jpg',
                'error' => 'Test failure',
            ]);

        $job = new ProcessMediaUpload(
            $batch->id,
            $tempPath,
            'test.jpg',
            'image/jpeg'
        );

        // Act
        $job->failed(new \Exception('Test failure'));

        // Assert
        $batch->refresh();
        $this->assertEquals(1, $batch->failed_files);
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }
}
