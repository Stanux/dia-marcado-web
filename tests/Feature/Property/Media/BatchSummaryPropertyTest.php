<?php

namespace Tests\Feature\Property\Media;

use App\Models\AlbumType;
use App\Models\PlanLimit;
use App\Models\SiteMedia;
use App\Models\SystemConfig;
use App\Models\UploadBatch;
use App\Models\Wedding;
use App\Services\Media\BatchUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for batch summary correctness.
 * 
 * @feature media-management
 * @property 3: Resumo de Batch Contém Contagens Corretas
 * 
 * Validates: Requirements 1.5
 */
class BatchSummaryPropertyTest extends TestCase
{
    use RefreshDatabase;

    private BatchUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        
        $this->service = app(BatchUploadService::class);
        
        // Seed album types
        AlbumType::firstOrCreate(['slug' => 'pre_casamento'], ['name' => 'Pré-Casamento', 'description' => 'Fotos do pré-casamento']);
        AlbumType::firstOrCreate(['slug' => 'pos_casamento'], ['name' => 'Pós-Casamento', 'description' => 'Fotos do pós-casamento']);
        AlbumType::firstOrCreate(['slug' => 'uso_site'], ['name' => 'Uso no Site', 'description' => 'Fotos para uso no site']);
        
        // Seed plan limits
        PlanLimit::firstOrCreate(['plan_slug' => 'basic'], ['max_files' => 100, 'max_storage_bytes' => 524288000]);
        PlanLimit::firstOrCreate(['plan_slug' => 'premium'], ['max_files' => 1000, 'max_storage_bytes' => 5368709120]);
        
        // Ensure media config exists
        SystemConfig::set('media.max_image_size', 10485760); // 10MB
        SystemConfig::set('media.max_video_size', 104857600); // 100MB
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
        SystemConfig::set('site.blocked_extensions', ['exe', 'bat', 'sh', 'php', 'js', 'html']);
        SystemConfig::set('site.max_storage_per_wedding', 524288000); // 500MB
    }

    /**
     * Property 3: Resumo de Batch Contém Contagens Corretas
     * 
     * For any processed batch, completed + failed + pending = total.
     * 
     * **Validates: Requirements 1.5**
     * 
     * @test
     * @feature media-management
     * @property 3: Resumo de Batch Contém Contagens Corretas
     */
    public function batch_status_counts_sum_to_total(): void
    {
        for ($iteration = 0; $iteration < 20; $iteration++) {
            $wedding = Wedding::factory()->create();
            
            // Random total files between 3 and 10
            $totalFiles = rand(3, 10);
            $batch = $this->service->createBatch($wedding, $totalFiles);
            
            // Process a random mix of valid and invalid files
            $validCount = rand(1, $totalFiles - 1);
            $invalidCount = $totalFiles - $validCount;
            
            // Process valid files
            for ($i = 0; $i < $validCount; $i++) {
                $file = UploadedFile::fake()->image("valid{$i}.jpg", 100, 100);
                $this->service->processFile($batch, $file);
            }
            
            // Process invalid files
            for ($i = 0; $i < $invalidCount; $i++) {
                $file = UploadedFile::fake()->create("invalid{$i}.exe", 100);
                $this->service->processFile($batch, $file);
            }
            
            // Get batch status
            $status = $this->service->getBatchStatus($batch);
            
            // Verify the invariant: completed + failed + pending = total
            $sum = $status->completed + $status->failed + $status->pending;
            $this->assertEquals(
                $status->total,
                $sum,
                "Iteration {$iteration}: completed({$status->completed}) + failed({$status->failed}) + pending({$status->pending}) = {$sum} should equal total({$status->total})"
            );
            
            // Verify counts match expected
            $this->assertEquals($validCount, $status->completed, "Iteration {$iteration}: completed should match valid count");
            $this->assertEquals($invalidCount, $status->failed, "Iteration {$iteration}: failed should match invalid count");
            $this->assertEquals(0, $status->pending, "Iteration {$iteration}: pending should be 0 after all files processed");
        }
    }

    /**
     * Property 3: Resumo de Batch Contém Contagens Corretas
     * 
     * Batch status counts match actual SiteMedia record statuses.
     * 
     * **Validates: Requirements 1.5**
     * 
     * @test
     * @feature media-management
     * @property 3: Resumo de Batch Contém Contagens Corretas
     */
    public function batch_status_matches_actual_media_records(): void
    {
        for ($iteration = 0; $iteration < 20; $iteration++) {
            $wedding = Wedding::factory()->create();
            
            $totalFiles = rand(3, 8);
            $batch = $this->service->createBatch($wedding, $totalFiles);
            
            // Process all files as valid
            for ($i = 0; $i < $totalFiles; $i++) {
                $file = UploadedFile::fake()->image("photo{$i}.jpg", 100, 100);
                $this->service->processFile($batch, $file);
            }
            
            // Get batch status
            $status = $this->service->getBatchStatus($batch);
            
            // Count actual media records
            $actualCompleted = SiteMedia::where('batch_id', $batch->id)
                ->where('status', 'completed')
                ->count();
            
            $actualFailed = SiteMedia::where('batch_id', $batch->id)
                ->where('status', 'failed')
                ->count();
            
            // Verify counts match actual records
            $this->assertEquals(
                $actualCompleted,
                $status->completed,
                "Iteration {$iteration}: BatchStatus.completed should match actual completed media count"
            );
            
            $this->assertEquals(
                $actualFailed,
                $status->failed,
                "Iteration {$iteration}: BatchStatus.failed should match actual failed media count"
            );
        }
    }

    /**
     * Property 3: Resumo de Batch Contém Contagens Corretas
     * 
     * isComplete() returns true only when all files are processed.
     * 
     * **Validates: Requirements 1.5**
     * 
     * @test
     * @feature media-management
     * @property 3: Resumo de Batch Contém Contagens Corretas
     */
    public function is_complete_returns_true_when_all_processed(): void
    {
        for ($iteration = 0; $iteration < 20; $iteration++) {
            $wedding = Wedding::factory()->create();
            
            $totalFiles = rand(2, 5);
            $batch = $this->service->createBatch($wedding, $totalFiles);
            
            // Process files one by one and check isComplete
            for ($i = 0; $i < $totalFiles; $i++) {
                $file = UploadedFile::fake()->image("photo{$i}.jpg", 100, 100);
                $this->service->processFile($batch, $file);
                
                $status = $this->service->getBatchStatus($batch);
                
                if ($i < $totalFiles - 1) {
                    // Not all files processed yet
                    $this->assertFalse(
                        $status->isComplete(),
                        "Iteration {$iteration}, File {$i}: isComplete should be false when pending > 0"
                    );
                } else {
                    // All files processed
                    $this->assertTrue(
                        $status->isComplete(),
                        "Iteration {$iteration}: isComplete should be true when all files processed"
                    );
                }
            }
        }
    }

    /**
     * Property 3: Resumo de Batch Contém Contagens Corretas
     * 
     * getProgressPercentage() returns correct percentage.
     * 
     * **Validates: Requirements 1.5**
     * 
     * @test
     * @feature media-management
     * @property 3: Resumo de Batch Contém Contagens Corretas
     */
    public function progress_percentage_is_correct(): void
    {
        for ($iteration = 0; $iteration < 20; $iteration++) {
            $wedding = Wedding::factory()->create();
            
            $totalFiles = rand(4, 10);
            $batch = $this->service->createBatch($wedding, $totalFiles);
            
            // Process files and check progress
            for ($i = 0; $i < $totalFiles; $i++) {
                $file = UploadedFile::fake()->image("photo{$i}.jpg", 100, 100);
                $this->service->processFile($batch, $file);
                
                $status = $this->service->getBatchStatus($batch);
                $expectedPercentage = (($i + 1) / $totalFiles) * 100;
                
                $this->assertEquals(
                    $expectedPercentage,
                    $status->getProgressPercentage(),
                    "Iteration {$iteration}, File {$i}: Progress should be " . $expectedPercentage . "%"
                );
            }
        }
    }

    /**
     * Property 3: Resumo de Batch Contém Contagens Corretas
     * 
     * Errors array contains error messages from failed uploads.
     * 
     * **Validates: Requirements 1.5**
     * 
     * @test
     * @feature media-management
     * @property 3: Resumo de Batch Contém Contagens Corretas
     */
    public function errors_array_contains_failure_messages(): void
    {
        for ($iteration = 0; $iteration < 20; $iteration++) {
            $wedding = Wedding::factory()->create();
            
            $batch = $this->service->createBatch($wedding, 4);
            
            // Process 2 valid and 2 invalid files
            $this->service->processFile($batch, UploadedFile::fake()->image('valid1.jpg', 100, 100));
            $this->service->processFile($batch, UploadedFile::fake()->create('invalid1.exe', 100));
            $this->service->processFile($batch, UploadedFile::fake()->image('valid2.png', 100, 100));
            $this->service->processFile($batch, UploadedFile::fake()->create('invalid2.php', 100));
            
            $status = $this->service->getBatchStatus($batch);
            
            // Verify error count matches failed count
            $this->assertEquals(
                $status->failed,
                count($status->errors),
                "Iteration {$iteration}: Number of errors should match failed count"
            );
            
            // Verify each error is a non-empty string
            foreach ($status->errors as $error) {
                $this->assertIsString($error, "Iteration {$iteration}: Each error should be a string");
                $this->assertNotEmpty($error, "Iteration {$iteration}: Each error should be non-empty");
            }
        }
    }
}
