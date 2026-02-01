<?php

declare(strict_types=1);

namespace Tests\Feature\Properties;

use App\Models\SystemConfig;
use App\Services\Site\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Property tests for MediaUploadService file validation.
 * 
 * Property 11: Validação de Upload
 * Validates: Requirements 16.1-16.5
 * 
 * For any uploaded file:
 * - If extension is in blocked_extensions → reject
 * - If extension is not in allowed_extensions → reject
 * - If size > max_file_size → reject
 * - If MIME type doesn't match extension → reject
 * - Otherwise → accept
 */
class UploadValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MediaUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaUploadService();
        Storage::fake('local');
        
        // Seed system configs
        $this->seedSystemConfigs();
    }

    private function seedSystemConfigs(): void
    {
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
        SystemConfig::set('site.blocked_extensions', ['exe', 'bat', 'sh', 'php', 'js', 'html']);
        SystemConfig::set('site.max_file_size', 10485760); // 10MB
        SystemConfig::set('media.max_image_size', 10485760); // 10MB for images
        SystemConfig::set('media.max_video_size', 104857600); // 100MB for videos
    }

    /**
     * @test
     * @group property
     */
    public function blocked_extensions_are_rejected(): void
    {
        $blockedExtensions = ['exe', 'bat', 'sh', 'php', 'js', 'html'];

        foreach ($blockedExtensions as $extension) {
            $file = UploadedFile::fake()->create("malicious.{$extension}", 100);
            
            $result = $this->service->validateFile($file);
            
            $this->assertFalse(
                $result->isValid(),
                "Blocked extension .{$extension} should be rejected"
            );
            $this->assertTrue(
                $result->hasErrors(),
                "Blocked extension .{$extension} should have errors"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function non_allowed_extensions_are_rejected(): void
    {
        $nonAllowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'txt', 'csv', 'svg'];

        foreach ($nonAllowedExtensions as $extension) {
            $file = UploadedFile::fake()->create("document.{$extension}", 100);
            
            $result = $this->service->validateFile($file);
            
            $this->assertFalse(
                $result->isValid(),
                "Non-allowed extension .{$extension} should be rejected"
            );
            $this->assertTrue(
                $result->hasErrors(),
                "Non-allowed extension .{$extension} should have errors"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function files_exceeding_max_size_are_rejected(): void
    {
        // Test with files larger than 10MB (10485760 bytes)
        $oversizedFiles = [
            11 * 1024, // 11MB in KB
            15 * 1024, // 15MB in KB
            20 * 1024, // 20MB in KB
        ];

        foreach ($oversizedFiles as $sizeKb) {
            $file = UploadedFile::fake()->create('large_image.jpg', $sizeKb, 'image/jpeg');
            
            $result = $this->service->validateFile($file);
            
            $this->assertFalse(
                $result->isValid(),
                "File of {$sizeKb}KB should be rejected for exceeding max size"
            );
            $this->assertTrue(
                $result->hasErrors(),
                "Oversized file should have errors"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function valid_image_files_are_accepted(): void
    {
        $validImages = [
            ['name' => 'photo.jpg', 'mime' => 'image/jpeg'],
            ['name' => 'photo.jpeg', 'mime' => 'image/jpeg'],
            ['name' => 'image.png', 'mime' => 'image/png'],
            ['name' => 'animation.gif', 'mime' => 'image/gif'],
            ['name' => 'modern.webp', 'mime' => 'image/webp'],
        ];

        foreach ($validImages as $imageData) {
            $file = UploadedFile::fake()->create($imageData['name'], 500, $imageData['mime']);
            
            $result = $this->service->validateFile($file);
            
            $this->assertTrue(
                $result->isValid(),
                "Valid image {$imageData['name']} with MIME {$imageData['mime']} should be accepted"
            );
            $this->assertFalse(
                $result->hasErrors(),
                "Valid image should not have errors"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function valid_video_files_are_accepted(): void
    {
        $validVideos = [
            ['name' => 'video.mp4', 'mime' => 'video/mp4'],
            ['name' => 'clip.webm', 'mime' => 'video/webm'],
        ];

        foreach ($validVideos as $videoData) {
            $file = UploadedFile::fake()->create($videoData['name'], 5000, $videoData['mime']);
            
            $result = $this->service->validateFile($file);
            
            $this->assertTrue(
                $result->isValid(),
                "Valid video {$videoData['name']} with MIME {$videoData['mime']} should be accepted"
            );
            $this->assertFalse(
                $result->hasErrors(),
                "Valid video should not have errors"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function files_within_size_limit_are_accepted(): void
    {
        // Test with files under 10MB
        $validSizes = [
            100,    // 100KB
            1024,   // 1MB
            5120,   // 5MB
            10240,  // 10MB (exactly at limit)
        ];

        foreach ($validSizes as $sizeKb) {
            $file = UploadedFile::fake()->create('image.jpg', $sizeKb, 'image/jpeg');
            
            $result = $this->service->validateFile($file);
            
            $this->assertTrue(
                $result->isValid(),
                "File of {$sizeKb}KB should be accepted (under max size)"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_blocked_extensions_always_rejected(): void
    {
        // Generate 100 random test cases with blocked extensions
        $blockedExtensions = ['exe', 'bat', 'sh', 'php', 'js', 'html'];
        $filenames = ['file', 'document', 'image', 'video', 'data', 'script', 'program'];
        $sizes = [1, 10, 100, 500, 1000, 5000];

        for ($i = 0; $i < 100; $i++) {
            $extension = $blockedExtensions[array_rand($blockedExtensions)];
            $filename = $filenames[array_rand($filenames)];
            $size = $sizes[array_rand($sizes)];
            
            $file = UploadedFile::fake()->create("{$filename}.{$extension}", $size);
            
            $result = $this->service->validateFile($file);
            
            $this->assertFalse(
                $result->isValid(),
                "Iteration {$i}: Blocked extension .{$extension} should always be rejected"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_non_allowed_extensions_always_rejected(): void
    {
        // Generate 100 random test cases with non-allowed extensions
        $nonAllowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'txt', 'csv', 'svg', 'bmp', 'tiff', 'psd', 'ai'];
        $filenames = ['report', 'document', 'spreadsheet', 'archive', 'data', 'design'];
        $sizes = [1, 10, 100, 500, 1000, 5000];

        for ($i = 0; $i < 100; $i++) {
            $extension = $nonAllowedExtensions[array_rand($nonAllowedExtensions)];
            $filename = $filenames[array_rand($filenames)];
            $size = $sizes[array_rand($sizes)];
            
            $file = UploadedFile::fake()->create("{$filename}.{$extension}", $size);
            
            $result = $this->service->validateFile($file);
            
            $this->assertFalse(
                $result->isValid(),
                "Iteration {$i}: Non-allowed extension .{$extension} should always be rejected"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_oversized_files_always_rejected(): void
    {
        // Generate 100 random test cases with oversized files
        $testCases = [
            // Images - over 10MB
            ['extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'sizes' => [11 * 1024, 12 * 1024, 15 * 1024, 20 * 1024]],
            // Videos - over 100MB  
            ['extensions' => ['mp4', 'webm'], 'sizes' => [101 * 1024, 110 * 1024, 120 * 1024, 150 * 1024]],
        ];
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
        ];
        $filenames = ['photo', 'image', 'video', 'media', 'upload'];

        for ($i = 0; $i < 100; $i++) {
            $testCase = $testCases[array_rand($testCases)];
            $extension = $testCase['extensions'][array_rand($testCase['extensions'])];
            $filename = $filenames[array_rand($filenames)];
            $size = $testCase['sizes'][array_rand($testCase['sizes'])];
            $mime = $mimeTypes[$extension];
            
            $file = UploadedFile::fake()->create("{$filename}.{$extension}", $size, $mime);
            
            $result = $this->service->validateFile($file);
            
            $this->assertFalse(
                $result->isValid(),
                "Iteration {$i}: Oversized file ({$size}KB, {$extension}) should always be rejected"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function property_valid_files_always_accepted(): void
    {
        // Generate 100 random test cases with valid files
        $validConfigs = [
            ['ext' => 'jpg', 'mime' => 'image/jpeg'],
            ['ext' => 'jpeg', 'mime' => 'image/jpeg'],
            ['ext' => 'png', 'mime' => 'image/png'],
            ['ext' => 'gif', 'mime' => 'image/gif'],
            ['ext' => 'webp', 'mime' => 'image/webp'],
            ['ext' => 'mp4', 'mime' => 'video/mp4'],
            ['ext' => 'webm', 'mime' => 'video/webm'],
        ];
        $filenames = ['photo', 'image', 'video', 'media', 'upload', 'file', 'content'];
        
        // Valid sizes under 10MB (in KB)
        $validSizes = [1, 10, 50, 100, 500, 1000, 2000, 5000, 8000, 10000];

        for ($i = 0; $i < 100; $i++) {
            $config = $validConfigs[array_rand($validConfigs)];
            $filename = $filenames[array_rand($filenames)];
            $size = $validSizes[array_rand($validSizes)];
            
            $file = UploadedFile::fake()->create(
                "{$filename}.{$config['ext']}", 
                $size, 
                $config['mime']
            );
            
            $result = $this->service->validateFile($file);
            
            $this->assertTrue(
                $result->isValid(),
                "Iteration {$i}: Valid file {$filename}.{$config['ext']} ({$size}KB, {$config['mime']}) should be accepted"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function validation_respects_dynamic_config_changes(): void
    {
        // Test that validation respects config changes
        
        // Initially, pdf is not allowed
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100);
        $result = $this->service->validateFile($pdfFile);
        $this->assertFalse($result->isValid(), 'PDF should be rejected with default config');

        // Add pdf to allowed extensions
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'pdf']);
        
        $result = $this->service->validateFile($pdfFile);
        $this->assertTrue($result->isValid(), 'PDF should be accepted after config change');

        // Reduce max file size
        SystemConfig::set('media.max_image_size', 1048576); // 1MB
        
        $largeFile = UploadedFile::fake()->create('large.jpg', 2048, 'image/jpeg'); // 2MB
        $result = $this->service->validateFile($largeFile);
        $this->assertFalse($result->isValid(), 'File should be rejected with reduced max size');
    }

    /**
     * @test
     * @group property
     */
    public function blocked_extensions_take_precedence_over_allowed(): void
    {
        // If an extension is in both blocked and allowed, it should be rejected
        SystemConfig::set('site.allowed_extensions', ['jpg', 'php']); // php in allowed
        SystemConfig::set('site.blocked_extensions', ['php']); // php also in blocked
        
        $file = UploadedFile::fake()->create('script.php', 100);
        $result = $this->service->validateFile($file);
        
        $this->assertFalse(
            $result->isValid(),
            'Blocked extensions should take precedence over allowed'
        );
    }

    /**
     * @test
     * @group property
     */
    public function case_insensitive_extension_handling(): void
    {
        $extensions = ['JPG', 'Jpg', 'jPg', 'JPEG', 'PNG', 'Png', 'GIF', 'WEBP', 'MP4', 'WEBM'];
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
        ];

        foreach ($extensions as $ext) {
            $lowerExt = strtolower($ext);
            $mime = $mimeMap[$lowerExt] ?? 'application/octet-stream';
            
            $file = UploadedFile::fake()->create("file.{$ext}", 100, $mime);
            $result = $this->service->validateFile($file);
            
            $this->assertTrue(
                $result->isValid(),
                "Extension {$ext} should be handled case-insensitively"
            );
        }
    }

    /**
     * @test
     * @group property
     */
    public function error_messages_are_descriptive(): void
    {
        // Test blocked extension error message
        $blockedFile = UploadedFile::fake()->create('script.php', 100);
        $result = $this->service->validateFile($blockedFile);
        $errors = $result->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('php', $errors[0]);

        // Test non-allowed extension error message
        $nonAllowedFile = UploadedFile::fake()->create('document.pdf', 100);
        $result = $this->service->validateFile($nonAllowedFile);
        $errors = $result->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Use:', $errors[0]);

        // Test oversized file error message
        $oversizedFile = UploadedFile::fake()->create('large.jpg', 15 * 1024, 'image/jpeg');
        $result = $this->service->validateFile($oversizedFile);
        $errors = $result->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('MB', $errors[0]);
    }
}
