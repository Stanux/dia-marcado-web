<?php

namespace Tests\Feature\Property\Media;

use App\Models\SystemConfig;
use App\Services\Site\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for file size rejection.
 * 
 * @feature media-management
 * @property 8: Rejeição de Arquivos Acima do Limite
 * 
 * Validates: Requirements 3.5
 */
class FileSizeRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MediaUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new MediaUploadService();
        
        // Ensure media config exists
        SystemConfig::set('media.max_image_size', 10485760); // 10MB
        SystemConfig::set('media.max_video_size', 104857600); // 100MB
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
        SystemConfig::set('site.blocked_extensions', ['exe', 'bat', 'sh', 'php', 'js', 'html']);
    }

    /**
     * Property 8: Rejeição de Arquivos Acima do Limite
     * 
     * For any image file with size greater than max_image_size,
     * the upload must be rejected with a descriptive error.
     * 
     * **Validates: Requirements 3.5**
     * 
     * @test
     * @feature media-management
     * @property 8: Rejeição de Arquivos Acima do Limite
     */
    public function oversized_images_are_rejected(): void
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random max size (1MB to 20MB)
            $maxImageSize = mt_rand(1048576, 20971520);
            SystemConfig::set('media.max_image_size', $maxImageSize);
            
            // Generate file size that exceeds limit by at least 2KB to account for rounding
            // when converting bytes to KB for UploadedFile::fake()->create()
            $fileSizeKb = (int) ceil($maxImageSize / 1024) + mt_rand(2, 10240); // 2KB to 10MB over limit
            
            // Pick random image extension
            $extension = $imageExtensions[array_rand($imageExtensions)];
            
            // Create fake file (size in KB)
            $file = UploadedFile::fake()->create("test.{$extension}", $fileSizeKb);
            
            // Actual file size in bytes
            $actualFileSize = $file->getSize();
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation fails
            $this->assertFalse(
                $result->isValid(),
                "Iteration {$iteration}: Image of {$actualFileSize} bytes should be rejected (max: {$maxImageSize})"
            );
            
            // Verify error message mentions size limit
            $errors = $result->getErrors();
            $this->assertNotEmpty($errors, "Iteration {$iteration}: Should have error message");
            $this->assertStringContainsString(
                'MB',
                $errors[0],
                "Iteration {$iteration}: Error should mention size limit in MB"
            );
        }
    }

    /**
     * Property 8: Rejeição de Arquivos Acima do Limite
     * 
     * For any video file with size greater than max_video_size,
     * the upload must be rejected with a descriptive error.
     * 
     * **Validates: Requirements 3.5**
     * 
     * @test
     * @feature media-management
     * @property 8: Rejeição de Arquivos Acima do Limite
     */
    public function oversized_videos_are_rejected(): void
    {
        $videoExtensions = ['mp4', 'webm'];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random max size (10MB to 200MB)
            $maxVideoSize = mt_rand(10485760, 209715200);
            SystemConfig::set('media.max_video_size', $maxVideoSize);
            
            // Generate file size that exceeds limit
            $fileSize = $maxVideoSize + mt_rand(1, 52428800); // 1 byte to 50MB over limit
            
            // Pick random video extension
            $extension = $videoExtensions[array_rand($videoExtensions)];
            
            // Create fake file
            $file = UploadedFile::fake()->create("test.{$extension}", (int) ($fileSize / 1024)); // size in KB
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation fails
            $this->assertFalse(
                $result->isValid(),
                "Iteration {$iteration}: Video of {$fileSize} bytes should be rejected (max: {$maxVideoSize})"
            );
            
            // Verify error message mentions size limit
            $errors = $result->getErrors();
            $this->assertNotEmpty($errors, "Iteration {$iteration}: Should have error message");
            $this->assertStringContainsString(
                'MB',
                $errors[0],
                "Iteration {$iteration}: Error should mention size limit in MB"
            );
        }
    }

    /**
     * Property 8: Rejeição de Arquivos Acima do Limite
     * 
     * For any image file with size within max_image_size,
     * the file should pass size validation.
     * 
     * **Validates: Requirements 3.5**
     * 
     * @test
     * @feature media-management
     * @property 8: Rejeição de Arquivos Acima do Limite
     */
    public function images_within_limit_pass_size_validation(): void
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random max size (5MB to 20MB)
            $maxImageSize = mt_rand(5242880, 20971520);
            SystemConfig::set('media.max_image_size', $maxImageSize);
            
            // Generate file size within limit
            $fileSize = mt_rand(1024, $maxImageSize - 1024); // At least 1KB, at most 1KB under limit
            
            // Pick random image extension
            $extension = $imageExtensions[array_rand($imageExtensions)];
            
            // Create fake file
            $file = UploadedFile::fake()->create("test.{$extension}", (int) ($fileSize / 1024)); // size in KB
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation passes
            $this->assertTrue(
                $result->isValid(),
                "Iteration {$iteration}: Image of {$fileSize} bytes should pass (max: {$maxImageSize}). Errors: " . implode(', ', $result->getErrors())
            );
        }
    }

    /**
     * Property 8: Rejeição de Arquivos Acima do Limite
     * 
     * For any video file with size within max_video_size,
     * the file should pass size validation.
     * 
     * **Validates: Requirements 3.5**
     * 
     * @test
     * @feature media-management
     * @property 8: Rejeição de Arquivos Acima do Limite
     */
    public function videos_within_limit_pass_size_validation(): void
    {
        $videoExtensions = ['mp4', 'webm'];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random max size (50MB to 200MB)
            $maxVideoSize = mt_rand(52428800, 209715200);
            SystemConfig::set('media.max_video_size', $maxVideoSize);
            
            // Generate file size within limit
            $fileSize = mt_rand(1024, $maxVideoSize - 1024); // At least 1KB, at most 1KB under limit
            
            // Pick random video extension
            $extension = $videoExtensions[array_rand($videoExtensions)];
            
            // Create fake file
            $file = UploadedFile::fake()->create("test.{$extension}", (int) ($fileSize / 1024)); // size in KB
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation passes
            $this->assertTrue(
                $result->isValid(),
                "Iteration {$iteration}: Video of {$fileSize} bytes should pass (max: {$maxVideoSize}). Errors: " . implode(', ', $result->getErrors())
            );
        }
    }

    /**
     * Property 8: Rejeição de Arquivos Acima do Limite
     * 
     * Images and videos have different size limits - verify they are applied correctly.
     * 
     * **Validates: Requirements 3.5**
     * 
     * @test
     * @feature media-management
     * @property 8: Rejeição de Arquivos Acima do Limite
     */
    public function different_limits_for_images_and_videos(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Set different limits for images and videos
            $maxImageSize = mt_rand(5242880, 10485760); // 5-10MB for images
            $maxVideoSize = mt_rand(52428800, 104857600); // 50-100MB for videos
            
            SystemConfig::set('media.max_image_size', $maxImageSize);
            SystemConfig::set('media.max_video_size', $maxVideoSize);
            
            // Create a file size that's over image limit but under video limit
            $fileSize = $maxImageSize + mt_rand(1048576, 5242880); // 1-5MB over image limit
            
            // Ensure it's still under video limit
            if ($fileSize >= $maxVideoSize) {
                $fileSize = $maxVideoSize - 1048576; // 1MB under video limit
            }
            
            // Test with image - should fail
            $imageFile = UploadedFile::fake()->create("test.jpg", (int) ($fileSize / 1024));
            $imageResult = $this->service->validateFile($imageFile);
            
            $this->assertFalse(
                $imageResult->isValid(),
                "Iteration {$iteration}: Image of {$fileSize} bytes should be rejected (max: {$maxImageSize})"
            );
            
            // Test with video - should pass (if under video limit)
            if ($fileSize < $maxVideoSize) {
                $videoFile = UploadedFile::fake()->create("test.mp4", (int) ($fileSize / 1024));
                $videoResult = $this->service->validateFile($videoFile);
                
                $this->assertTrue(
                    $videoResult->isValid(),
                    "Iteration {$iteration}: Video of {$fileSize} bytes should pass (max: {$maxVideoSize}). Errors: " . implode(', ', $videoResult->getErrors())
                );
            }
        }
    }

    /**
     * Property 8: Rejeição de Arquivos Acima do Limite
     * 
     * Error messages should be descriptive and include the size limit.
     * 
     * **Validates: Requirements 3.5**
     * 
     * @test
     * @feature media-management
     * @property 8: Rejeição de Arquivos Acima do Limite
     */
    public function error_messages_are_descriptive(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Set a specific limit
            $maxImageSize = mt_rand(1048576, 20971520);
            $maxSizeMb = round($maxImageSize / 1024 / 1024);
            SystemConfig::set('media.max_image_size', $maxImageSize);
            
            // Create oversized file
            $fileSize = $maxImageSize + mt_rand(1048576, 10485760);
            $file = UploadedFile::fake()->create("test.jpg", (int) ($fileSize / 1024));
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify error message is descriptive
            $errors = $result->getErrors();
            $this->assertNotEmpty($errors, "Iteration {$iteration}: Should have error message");
            
            // Error should mention the limit
            $errorMessage = $errors[0];
            $this->assertStringContainsString(
                (string) $maxSizeMb,
                $errorMessage,
                "Iteration {$iteration}: Error should mention the limit ({$maxSizeMb}MB)"
            );
        }
    }
}
