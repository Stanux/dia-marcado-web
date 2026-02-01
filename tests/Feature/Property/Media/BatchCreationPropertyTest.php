<?php

namespace Tests\Feature\Property\Media;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\UploadBatch;
use App\Models\Wedding;
use App\Services\Media\BatchUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for batch creation.
 * 
 * @feature media-management
 * @property 1: Criação de Batch Cria Entradas Corretas
 * 
 * Validates: Requirements 1.1
 */
class BatchCreationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private BatchUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(BatchUploadService::class);
        
        // Seed album types
        AlbumType::firstOrCreate(['slug' => 'pre_casamento'], ['name' => 'Pré-Casamento', 'description' => 'Fotos do pré-casamento']);
        AlbumType::firstOrCreate(['slug' => 'pos_casamento'], ['name' => 'Pós-Casamento', 'description' => 'Fotos do pós-casamento']);
        AlbumType::firstOrCreate(['slug' => 'uso_site'], ['name' => 'Uso no Site', 'description' => 'Fotos para uso no site']);
    }

    /**
     * Property 1: Criação de Batch Cria Entradas Corretas
     * 
     * For any wedding and total file count, creating a batch should:
     * - Create a batch record with correct wedding_id
     * - Set total_files to the specified count
     * - Initialize completed_files and failed_files to 0
     * - Set status to 'pending'
     * 
     * **Validates: Requirements 1.1**
     * 
     * @test
     * @feature media-management
     * @property 1: Criação de Batch Cria Entradas Corretas
     */
    public function batch_creation_initializes_correct_values(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random total files (1 to 100)
            $totalFiles = mt_rand(1, 100);
            
            // Create a wedding
            $wedding = Wedding::factory()->create();
            
            // Create batch
            $batch = $this->service->createBatch($wedding, $totalFiles);
            
            // Verify batch was created
            $this->assertInstanceOf(UploadBatch::class, $batch);
            
            // Verify wedding_id
            $this->assertEquals(
                $wedding->id,
                $batch->wedding_id,
                "Iteration {$iteration}: Batch should belong to the correct wedding"
            );
            
            // Verify total_files
            $this->assertEquals(
                $totalFiles,
                $batch->total_files,
                "Iteration {$iteration}: Batch should have correct total_files"
            );
            
            // Verify completed_files is 0
            $this->assertEquals(
                0,
                $batch->completed_files,
                "Iteration {$iteration}: Batch should start with 0 completed_files"
            );
            
            // Verify failed_files is 0
            $this->assertEquals(
                0,
                $batch->failed_files,
                "Iteration {$iteration}: Batch should start with 0 failed_files"
            );
            
            // Verify status is pending
            $this->assertEquals(
                'pending',
                $batch->status,
                "Iteration {$iteration}: Batch should start with 'pending' status"
            );
            
            // Verify UUID format
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $batch->id,
                "Iteration {$iteration}: Batch ID should be a valid UUID"
            );
        }
    }

    /**
     * Property 1: Criação de Batch Cria Entradas Corretas
     * 
     * When creating a batch with an album, the album_id should be set.
     * 
     * **Validates: Requirements 1.1**
     * 
     * @test
     * @feature media-management
     * @property 1: Criação de Batch Cria Entradas Corretas
     */
    public function batch_creation_with_album_sets_album_id(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $totalFiles = mt_rand(1, 50);
            
            // Create wedding and album
            $wedding = Wedding::factory()->create();
            $albumType = AlbumType::inRandomOrder()->first();
            $album = Album::create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
                'name' => 'Test Album ' . $iteration,
            ]);
            
            // Create batch with album
            $batch = $this->service->createBatch($wedding, $totalFiles, $album);
            
            // Verify album_id is set
            $this->assertEquals(
                $album->id,
                $batch->album_id,
                "Iteration {$iteration}: Batch should have correct album_id"
            );
        }
    }

    /**
     * Property 1: Criação de Batch Cria Entradas Corretas
     * 
     * When creating a batch without an album, album_id should be null.
     * 
     * **Validates: Requirements 1.1**
     * 
     * @test
     * @feature media-management
     * @property 1: Criação de Batch Cria Entradas Corretas
     */
    public function batch_creation_without_album_has_null_album_id(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $totalFiles = mt_rand(1, 50);
            
            $wedding = Wedding::factory()->create();
            
            // Create batch without album
            $batch = $this->service->createBatch($wedding, $totalFiles);
            
            // Verify album_id is null
            $this->assertNull(
                $batch->album_id,
                "Iteration {$iteration}: Batch without album should have null album_id"
            );
        }
    }

    /**
     * Property 1: Criação de Batch Cria Entradas Corretas
     * 
     * Each batch should have a unique ID.
     * 
     * **Validates: Requirements 1.1**
     * 
     * @test
     * @feature media-management
     * @property 1: Criação de Batch Cria Entradas Corretas
     */
    public function each_batch_has_unique_id(): void
    {
        $wedding = Wedding::factory()->create();
        $batchIds = [];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $batch = $this->service->createBatch($wedding, mt_rand(1, 10));
            
            $this->assertNotContains(
                $batch->id,
                $batchIds,
                "Iteration {$iteration}: Each batch should have a unique ID"
            );
            
            $batchIds[] = $batch->id;
        }
        
        // Verify all IDs are unique
        $this->assertCount(100, array_unique($batchIds));
    }

    /**
     * Property 1: Criação de Batch Cria Entradas Corretas
     * 
     * Batch is persisted to database.
     * 
     * **Validates: Requirements 1.1**
     * 
     * @test
     * @feature media-management
     * @property 1: Criação de Batch Cria Entradas Corretas
     */
    public function batch_is_persisted_to_database(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $totalFiles = mt_rand(1, 50);
            $wedding = Wedding::factory()->create();
            
            $batch = $this->service->createBatch($wedding, $totalFiles);
            
            // Verify batch exists in database
            $this->assertDatabaseHas('upload_batches', [
                'id' => $batch->id,
                'wedding_id' => $wedding->id,
                'total_files' => $totalFiles,
                'completed_files' => 0,
                'failed_files' => 0,
                'status' => 'pending',
            ]);
        }
    }
}
