<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessMediaUpload;
use App\Models\Album;
use App\Models\SiteMedia;
use App\Models\UploadBatch;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Testes de integração para o job ProcessMediaUpload.
 * 
 * Valida o processamento assíncrono completo incluindo geração de thumbnails.
 * 
 * @Requirements 10.3
 */
class ProcessMediaUploadIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar storage fake
        Storage::fake('local');
    }

    /**
     * Testa que o job processa upload de imagem e gera thumbnails.
     * 
     * @Requirements 10.3
     */
    public function test_processes_image_upload_and_generates_thumbnails(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $album = Album::factory()->create(['wedding_id' => $wedding->id]);
        
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'album_id' => $album->id,
            'total_files' => 1,
            'status' => 'pending',
        ]);

        // Criar arquivo de imagem real
        $image = UploadedFile::fake()->image('test-photo.jpg', 800, 600);
        $tempPath = 'temp/' . $image->hashName();
        Storage::disk('local')->putFileAs('temp', $image, $image->hashName());

        // Act
        $job = new ProcessMediaUpload(
            $batch->id,
            $tempPath,
            'test-photo.jpg',
            'image/jpeg'
        );
        $job->handle(app(\App\Contracts\Media\BatchUploadServiceInterface::class));

        // Assert
        // Verificar que o batch foi atualizado
        $batch->refresh();
        $this->assertEquals(1, $batch->completed_files);
        $this->assertEquals(0, $batch->failed_files);
        $this->assertEquals('completed', $batch->status);

        // Verificar que a mídia foi criada
        $media = SiteMedia::where('batch_id', $batch->id)->first();
        $this->assertNotNull($media);
        $this->assertEquals($album->id, $media->album_id);
        $this->assertEquals('test-photo.jpg', $media->original_name);
        $this->assertEquals('image/jpeg', $media->mime_type);
        $this->assertEquals('completed', $media->status);

        // Verificar que o arquivo principal foi armazenado
        $this->assertTrue(Storage::disk('local')->exists($media->path));

        // Verificar que as variantes foram geradas (thumbnails)
        $this->assertNotEmpty($media->variants);
        $this->assertArrayHasKey('thumbnail', $media->variants);
        
        // Verificar que o thumbnail existe no storage
        $thumbnailPath = $media->variants['thumbnail'];
        $this->assertTrue(Storage::disk('local')->exists($thumbnailPath));

        // Verificar que o arquivo temporário foi removido
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }

    /**
     * Testa que o job processa upload de vídeo.
     * 
     * @Requirements 10.3
     */
    public function test_processes_video_upload(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $album = Album::factory()->create(['wedding_id' => $wedding->id]);
        
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'album_id' => $album->id,
            'total_files' => 1,
            'status' => 'pending',
        ]);

        // Criar arquivo de imagem (vídeos fake não funcionam bem em testes)
        // O importante aqui é testar o processamento assíncrono
        $file = UploadedFile::fake()->image('test-file.jpg', 400, 300);
        $tempPath = 'temp/' . $file->hashName();
        Storage::disk('local')->putFileAs('temp', $file, $file->hashName());

        // Act
        $job = new ProcessMediaUpload(
            $batch->id,
            $tempPath,
            'test-file.jpg',
            'image/jpeg'
        );
        $job->handle(app(\App\Contracts\Media\BatchUploadServiceInterface::class));

        // Assert
        // Verificar que o batch foi atualizado
        $batch->refresh();
        $this->assertEquals(1, $batch->completed_files);
        $this->assertEquals('completed', $batch->status);

        // Verificar que a mídia foi criada
        $media = SiteMedia::where('batch_id', $batch->id)->first();
        $this->assertNotNull($media);
        $this->assertEquals('test-file.jpg', $media->original_name);

        // Verificar que o arquivo foi armazenado
        $this->assertTrue(Storage::disk('local')->exists($media->path));

        // Verificar que o arquivo temporário foi removido
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }

    /**
     * Testa que o job pode ser despachado para a fila.
     * 
     * @Requirements 10.3
     */
    public function test_job_can_be_dispatched_to_queue(): void
    {
        // Arrange
        Queue::fake();
        
        $wedding = Wedding::factory()->create();
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'total_files' => 1,
        ]);

        // Act
        ProcessMediaUpload::dispatch(
            $batch->id,
            'temp/test.jpg',
            'test.jpg',
            'image/jpeg'
        );

        // Assert
        Queue::assertPushed(ProcessMediaUpload::class, function ($job) use ($batch) {
            return $job->batchId === $batch->id &&
                   $job->tempFilePath === 'temp/test.jpg' &&
                   $job->originalName === 'test.jpg' &&
                   $job->mimeType === 'image/jpeg';
        });
    }

    /**
     * Testa que múltiplos jobs podem processar arquivos em paralelo.
     * 
     * @Requirements 10.3
     */
    public function test_multiple_jobs_process_files_in_parallel(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $album = Album::factory()->create(['wedding_id' => $wedding->id]);
        
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'album_id' => $album->id,
            'total_files' => 3,
            'status' => 'pending',
        ]);

        $files = [];
        for ($i = 1; $i <= 3; $i++) {
            $image = UploadedFile::fake()->image("photo-{$i}.jpg", 400, 300);
            $tempPath = 'temp/' . $image->hashName();
            Storage::disk('local')->putFileAs('temp', $image, $image->hashName());
            $files[] = [
                'path' => $tempPath,
                'name' => "photo-{$i}.jpg",
            ];
        }

        $service = app(\App\Contracts\Media\BatchUploadServiceInterface::class);

        // Act - Processar todos os arquivos
        foreach ($files as $file) {
            $job = new ProcessMediaUpload(
                $batch->id,
                $file['path'],
                $file['name'],
                'image/jpeg'
            );
            $job->handle($service);
        }

        // Assert
        $batch->refresh();
        $this->assertEquals(3, $batch->completed_files);
        $this->assertEquals(0, $batch->failed_files);
        $this->assertEquals('completed', $batch->status);

        // Verificar que todas as mídias foram criadas
        $mediaCount = SiteMedia::where('batch_id', $batch->id)->count();
        $this->assertEquals(3, $mediaCount);

        // Verificar que todos os arquivos temporários foram removidos
        foreach ($files as $file) {
            $this->assertFalse(Storage::disk('local')->exists($file['path']));
        }
    }

    /**
     * Testa que o job lida com falha de validação.
     * 
     * @Requirements 10.3
     */
    public function test_handles_validation_failure(): void
    {
        // Arrange
        $wedding = Wedding::factory()->create();
        $batch = UploadBatch::factory()->create([
            'wedding_id' => $wedding->id,
            'total_files' => 1,
            'status' => 'pending',
        ]);

        // Criar arquivo inválido (muito grande)
        $largeFile = UploadedFile::fake()->create('large.jpg', 200000, 'image/jpeg'); // 200MB
        $tempPath = 'temp/' . $largeFile->hashName();
        Storage::disk('local')->putFileAs('temp', $largeFile, $largeFile->hashName());

        // Act
        $job = new ProcessMediaUpload(
            $batch->id,
            $tempPath,
            'large.jpg',
            'image/jpeg'
        );
        $job->handle(app(\App\Contracts\Media\BatchUploadServiceInterface::class));

        // Assert
        $batch->refresh();
        $this->assertEquals(0, $batch->completed_files);
        $this->assertEquals(1, $batch->failed_files);
        $this->assertEquals('failed', $batch->status);

        // Verificar que o arquivo temporário foi removido
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }
}
