<?php

namespace Tests\Feature\Property\Media;

use App\Models\SystemConfig;
use App\Services\Site\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for file validation.
 * 
 * @feature media-management
 * @property 16: Validação de Arquivo Rejeita Inválidos
 * 
 * Validates: Requirements 7.1, 7.2, 7.3, 7.4
 */
class FileValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MediaUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new MediaUploadService();
        
        // Ensure config exists
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
        SystemConfig::set('site.blocked_extensions', ['exe', 'bat', 'sh', 'php', 'js', 'html', 'svg']);
        SystemConfig::set('media.max_image_size', 10485760);
        SystemConfig::set('media.max_video_size', 104857600);
    }

    /**
     * Property 16: Validação de Arquivo Rejeita Inválidos
     * 
     * For any file with extension not in the allowed list,
     * validateFile must return isValid()=false.
     * 
     * **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
     * 
     * @test
     * @feature media-management
     * @property 16: Validação de Arquivo Rejeita Inválidos
     */
    public function non_allowed_extensions_are_rejected(): void
    {
        $nonAllowedExtensions = [
            'doc', 'docx', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'xml', 'json', 'yaml', 'yml',
            'zip', 'rar', 'tar', 'gz', '7z',
            'bmp', 'tiff', 'ico', 'psd', 'ai',
            'avi', 'mov', 'wmv', 'flv', 'mkv',
            'mp3', 'wav', 'ogg', 'flac', 'aac',
        ];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Pick random non-allowed extension
            $extension = $nonAllowedExtensions[array_rand($nonAllowedExtensions)];
            
            // Create fake file
            $file = UploadedFile::fake()->create("test.{$extension}", 100);
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation fails
            $this->assertFalse(
                $result->isValid(),
                "Iteration {$iteration}: File with extension .{$extension} should be rejected"
            );
        }
    }

    /**
     * Property 16: Validação de Arquivo Rejeita Inválidos
     * 
     * For any file with blocked extension (security risk),
     * validateFile must return isValid()=false.
     * 
     * **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
     * 
     * @test
     * @feature media-management
     * @property 16: Validação de Arquivo Rejeita Inválidos
     */
    public function blocked_extensions_are_rejected(): void
    {
        $blockedExtensions = ['exe', 'bat', 'sh', 'php', 'js', 'html', 'svg', 'cmd', 'com', 'vbs', 'ps1'];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Pick random blocked extension
            $extension = $blockedExtensions[array_rand($blockedExtensions)];
            
            // Update blocked list to include this extension
            $currentBlocked = SystemConfig::get('site.blocked_extensions', []);
            if (!in_array($extension, $currentBlocked)) {
                $currentBlocked[] = $extension;
                SystemConfig::set('site.blocked_extensions', $currentBlocked);
            }
            
            // Create fake file
            $file = UploadedFile::fake()->create("test.{$extension}", 100);
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation fails
            $this->assertFalse(
                $result->isValid(),
                "Iteration {$iteration}: File with blocked extension .{$extension} should be rejected"
            );
            
            // Verify error message mentions the extension
            $errors = $result->getErrors();
            $this->assertNotEmpty($errors, "Iteration {$iteration}: Should have error message");
        }
    }

    /**
     * Property 16: Validação de Arquivo Rejeita Inválidos
     * 
     * For any file with allowed extension and valid size,
     * validateFile must return isValid()=true.
     * 
     * **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
     * 
     * @test
     * @feature media-management
     * @property 16: Validação de Arquivo Rejeita Inválidos
     */
    public function allowed_extensions_are_accepted(): void
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Pick random allowed extension
            $extension = $allowedExtensions[array_rand($allowedExtensions)];
            
            // Create fake file with valid size
            $file = UploadedFile::fake()->create("test.{$extension}", 100); // 100KB
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation passes
            $this->assertTrue(
                $result->isValid(),
                "Iteration {$iteration}: File with allowed extension .{$extension} should be accepted. Errors: " . implode(', ', $result->getErrors())
            );
        }
    }

    /**
     * Property 16: Validação de Arquivo Rejeita Inválidos
     * 
     * Blocked extensions take precedence over allowed extensions.
     * If an extension is in both lists, it should be rejected.
     * 
     * **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
     * 
     * @test
     * @feature media-management
     * @property 16: Validação de Arquivo Rejeita Inválidos
     */
    public function blocked_takes_precedence_over_allowed(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Pick a random extension
            $extension = 'test' . mt_rand(1, 1000);
            
            // Add to both allowed and blocked lists
            SystemConfig::set('site.allowed_extensions', [$extension, 'jpg', 'png']);
            SystemConfig::set('site.blocked_extensions', [$extension, 'exe', 'php']);
            
            // Create fake file
            $file = UploadedFile::fake()->create("test.{$extension}", 100);
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation fails (blocked takes precedence)
            $this->assertFalse(
                $result->isValid(),
                "Iteration {$iteration}: Extension .{$extension} in both lists should be rejected (blocked takes precedence)"
            );
        }
        
        // Reset to default
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
        SystemConfig::set('site.blocked_extensions', ['exe', 'bat', 'sh', 'php', 'js', 'html', 'svg']);
    }

    /**
     * Property 16: Validação de Arquivo Rejeita Inválidos
     * 
     * Extension validation is case-insensitive.
     * 
     * **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
     * 
     * @test
     * @feature media-management
     * @property 16: Validação de Arquivo Rejeita Inválidos
     */
    public function extension_validation_is_case_insensitive(): void
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Pick random allowed extension
            $extension = $allowedExtensions[array_rand($allowedExtensions)];
            
            // Randomly change case
            $caseVariants = [
                strtoupper($extension),
                ucfirst($extension),
                strtoupper(substr($extension, 0, 1)) . strtolower(substr($extension, 1)),
            ];
            $variantExtension = $caseVariants[array_rand($caseVariants)];
            
            // Create fake file
            $file = UploadedFile::fake()->create("test.{$variantExtension}", 100);
            
            // Validate file
            $result = $this->service->validateFile($file);
            
            // Verify validation passes (case-insensitive)
            $this->assertTrue(
                $result->isValid(),
                "Iteration {$iteration}: File with extension .{$variantExtension} (variant of .{$extension}) should be accepted. Errors: " . implode(', ', $result->getErrors())
            );
        }
    }

    /**
     * Property 16: Validação de Arquivo Rejeita Inválidos
     * 
     * Dynamic configuration changes are respected immediately.
     * 
     * **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
     * 
     * @test
     * @feature media-management
     * @property 16: Validação de Arquivo Rejeita Inválidos
     */
    public function dynamic_config_changes_are_respected(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate a unique extension
            $extension = 'ext' . mt_rand(1000, 9999);
            
            // Initially not allowed
            SystemConfig::set('site.allowed_extensions', ['jpg', 'png']);
            SystemConfig::set('site.blocked_extensions', ['exe', 'php']);
            
            $file = UploadedFile::fake()->create("test.{$extension}", 100);
            
            // Should be rejected
            $result1 = $this->service->validateFile($file);
            $this->assertFalse(
                $result1->isValid(),
                "Iteration {$iteration}: Extension .{$extension} should be rejected when not in allowed list"
            );
            
            // Add to allowed list
            SystemConfig::set('site.allowed_extensions', ['jpg', 'png', $extension]);
            
            // Should now be accepted
            $result2 = $this->service->validateFile($file);
            $this->assertTrue(
                $result2->isValid(),
                "Iteration {$iteration}: Extension .{$extension} should be accepted after adding to allowed list. Errors: " . implode(', ', $result2->getErrors())
            );
            
            // Add to blocked list
            SystemConfig::set('site.blocked_extensions', ['exe', 'php', $extension]);
            
            // Should be rejected again
            $result3 = $this->service->validateFile($file);
            $this->assertFalse(
                $result3->isValid(),
                "Iteration {$iteration}: Extension .{$extension} should be rejected after adding to blocked list"
            );
        }
        
        // Reset to default
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
        SystemConfig::set('site.blocked_extensions', ['exe', 'bat', 'sh', 'php', 'js', 'html', 'svg']);
    }
}
