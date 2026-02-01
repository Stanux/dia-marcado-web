<?php

namespace Tests\Feature\Property\Media;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\PlanLimit;
use App\Models\SiteLayout;
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
 * Property-based tests for batch failure isolation.
 * 
 * @feature media-management
 * @property 2: Falhas Isoladas Não Afetam Outros Uploads
 * 
 * Validates: Requirements 1.4
 */
class BatchIsolationPropertyTest extends TestCase
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
     * Property 2: Falhas Isoladas Não Afetam Outros Uploads
     * 
     * When one file in a batch fails validation, other valid files
     * should still be processed successfully.
     * 
     * **Validates: Requirements 1.4**
     * 
     * @test
     * @feature media-management
     * @property 2: Falhas Isoladas Não Afetam Outros Uploads
     */
    public function failed_file_does_not_affect_other_uploads(): void
    {
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Create wedding with site layout
            $wedding = Wedding::factory()->create();
            $siteLayout = SiteLayout::create([
                'wedding_id' => $wedding->id,
                'slug' => 'test-' . $wedding->id,
                'draft_content' => json_encode(['sections' => []]),
            ]);
            
            // Create batch for 3 files
            $batch = $this->service->createBatch($wedding, 3);
            
            // Process a valid file
            $validFile1 = UploadedFile::fake()->image('valid1.jpg', 100, 100);
            $result1 = $this->service->processFile($batch, $validFile1);
            
            // Process an invalid file (blocked extension)
            $invalidFile = UploadedFile::fake()->create('malware.exe', 100);
            $result2 = $this->service->processFile($batch, $invalidFile);
            
            // Process another valid file
            $validFile2 = UploadedFile::fake()->image('valid2.jpg', 100, 100);
            $result3 = $this->service->processFile($batch, $validFile2);
            
            // Verify first valid file succeeded
            $this->assertTrue(
                $result1->success,
                "Iteration {$iteration}: First valid file should succeed"
            );
            
            // Verify invalid file failed
            $this->assertFalse(
                $result2->success,
                "Iteration {$iteration}: Invalid file should fail"
            );
            
            // Verify second valid file succeeded despite previous failure
            $this->assertTrue(
                $result3->success,
                "Iteration {$iteration}: Second valid file should succeed despite previous failure"
            );
            
            // Verify batch counts
            $batch->refresh();
            $this->assertEquals(2, $batch->completed_files, "Iteration {$iteration}: Should have 2 completed files");
            $this->assertEquals(1, $batch->failed_files, "Iteration {$iteration}: Should have 1 failed file");
        }
    }

    /**
     * Property 2: Falhas Isoladas Não Afetam Outros Uploads
     * 
     * Multiple failures in a batch should not prevent valid files from being processed.
     * 
     * **Validates: Requirements 1.4**
     * 
     * @test
     * @feature media-management
     * @property 2: Falhas Isoladas Não Afetam Outros Uploads
     */
    public function multiple_failures_do_not_block_valid_uploads(): void
    {
        for ($iteration = 0; $iteration < 20; $iteration++) {
            $wedding = Wedding::factory()->create();
            SiteLayout::create([
                'wedding_id' => $wedding->id,
                'slug' => 'test-' . $wedding->id,
                'draft_content' => json_encode(['sections' => []]),
            ]);
            
            // Create batch for 5 files
            $batch = $this->service->createBatch($wedding, 5);
            
            // Process mix of valid and invalid files
            $files = [
                ['file' => UploadedFile::fake()->image('valid1.jpg', 100, 100), 'valid' => true],
                ['file' => UploadedFile::fake()->create('bad1.exe', 100), 'valid' => false],
                ['file' => UploadedFile::fake()->create('bad2.php', 100), 'valid' => false],
                ['file' => UploadedFile::fake()->image('valid2.png', 100, 100), 'valid' => true],
                ['file' => UploadedFile::fake()->image('valid3.gif', 100, 100), 'valid' => true],
            ];
            
            $results = [];
            foreach ($files as $fileData) {
                $results[] = [
                    'result' => $this->service->processFile($batch, $fileData['file']),
                    'expected_valid' => $fileData['valid'],
                ];
            }
            
            // Verify each result matches expectation
            foreach ($results as $index => $data) {
                $this->assertEquals(
                    $data['expected_valid'],
                    $data['result']->success,
                    "Iteration {$iteration}, File {$index}: Result should match expected validity"
                );
            }
            
            // Verify batch counts
            $batch->refresh();
            $this->assertEquals(3, $batch->completed_files, "Iteration {$iteration}: Should have 3 completed files");
            $this->assertEquals(2, $batch->failed_files, "Iteration {$iteration}: Should have 2 failed files");
        }
    }

    /**
     * Property 2: Falhas Isoladas Não Afetam Outros Uploads
     * 
     * Each file's error message should be isolated and specific to that file.
     * 
     * **Validates: Requirements 1.4**
     * 
     * @test
     * @feature media-management
     * @property 2: Falhas Isoladas Não Afetam Outros Uploads
     */
    public function error_messages_are_isolated_per_file(): void
    {
        for ($iteration = 0; $iteration < 20; $iteration++) {
            $wedding = Wedding::factory()->create();
            SiteLayout::create([
                'wedding_id' => $wedding->id,
                'slug' => 'test-' . $wedding->id,
                'draft_content' => json_encode(['sections' => []]),
            ]);
            
            $batch = $this->service->createBatch($wedding, 3);
            
            // Process files with different error types
            $blockedFile = UploadedFile::fake()->create('script.exe', 100);
            $result1 = $this->service->processFile($batch, $blockedFile);
            
            $notAllowedFile = UploadedFile::fake()->create('document.pdf', 100);
            $result2 = $this->service->processFile($batch, $notAllowedFile);
            
            $validFile = UploadedFile::fake()->image('photo.jpg', 100, 100);
            $result3 = $this->service->processFile($batch, $validFile);
            
            // Verify each has appropriate error or success
            $this->assertFalse($result1->success, "Iteration {$iteration}: Blocked file should fail");
            $this->assertNotEmpty($result1->error, "Iteration {$iteration}: Blocked file should have error message");
            
            $this->assertFalse($result2->success, "Iteration {$iteration}: Not allowed file should fail");
            $this->assertNotEmpty($result2->error, "Iteration {$iteration}: Not allowed file should have error message");
            
            $this->assertTrue($result3->success, "Iteration {$iteration}: Valid file should succeed");
            $this->assertNull($result3->error, "Iteration {$iteration}: Valid file should have no error");
        }
    }
}
