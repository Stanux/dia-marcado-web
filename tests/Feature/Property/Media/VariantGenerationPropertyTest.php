<?php

namespace Tests\Feature\Property\Media;

use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\SystemConfig;
use App\Models\Wedding;
use App\Services\Site\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for image variant generation.
 * 
 * @feature media-management
 * @property 17: Geração de Variantes para Imagens
 * 
 * Validates: Requirements 7.5
 */
class VariantGenerationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MediaUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new MediaUploadService();
        
        // Ensure config exists
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
        SystemConfig::set('media.max_image_width', 4096);
        SystemConfig::set('media.max_image_height', 4096);
    }

    /**
     * Create a wedding with an associated SiteLayout.
     */
    private function createWeddingWithLayout(): array
    {
        $wedding = Wedding::factory()->create();
        $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
        
        return ['wedding' => $wedding, 'siteLayout' => $siteLayout];
    }

    /**
     * Create a test image file.
     * 
     * @param int $width Image width
     * @param int $height Image height
     * @param string $extension File extension
     * @return string Path to the created image
     */
    private function createTestImage(int $width, int $height, string $extension = 'jpg'): string
    {
        $image = imagecreatetruecolor($width, $height);
        
        // Fill with a random color
        $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imagefill($image, 0, 0, $color);
        
        // Add some variation
        for ($i = 0; $i < 10; $i++) {
            $x = mt_rand(0, $width - 1);
            $y = mt_rand(0, $height - 1);
            $c = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($image, $x, $y, $c);
        }
        
        // Save to temp file
        $tempPath = sys_get_temp_dir() . '/test_image_' . uniqid() . '.' . $extension;
        
        switch ($extension) {
            case 'png':
                imagepng($image, $tempPath);
                break;
            case 'gif':
                imagegif($image, $tempPath);
                break;
            default:
                imagejpeg($image, $tempPath, 90);
        }
        
        imagedestroy($image);
        
        return $tempPath;
    }

    /**
     * Property 17: Geração de Variantes para Imagens
     * 
     * For any image processed successfully, the optimizeImage method should
     * generate at least a thumbnail variant.
     * 
     * **Validates: Requirements 7.5**
     * 
     * @test
     * @feature media-management
     * @property 17: Geração de Variantes para Imagens
     */
    public function optimize_image_generates_thumbnail(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random image dimensions (at least 100x100 for meaningful thumbnail)
            $width = mt_rand(100, 2000);
            $height = mt_rand(100, 2000);
            
            // Create test image
            $tempPath = $this->createTestImage($width, $height);
            
            try {
                // Optimize the image
                $variants = $this->service->optimizeImage($tempPath);
                
                // Verify thumbnail was generated
                $this->assertArrayHasKey(
                    'thumbnail',
                    $variants,
                    "Iteration {$iteration}: Image {$width}x{$height} should have thumbnail variant"
                );
                
                // Verify thumbnail path is not empty
                $this->assertNotEmpty(
                    $variants['thumbnail'],
                    "Iteration {$iteration}: Thumbnail path should not be empty"
                );
            } finally {
                // Cleanup
                @unlink($tempPath);
                
                // Cleanup variants
                if (isset($variants)) {
                    foreach ($variants as $variantPath) {
                        $fullPath = Storage::disk('local')->path($variantPath);
                        @unlink($fullPath);
                    }
                }
            }
        }
    }

    /**
     * Property 17: Geração de Variantes para Imagens
     * 
     * For any image processed successfully, the optimizeImage method should
     * attempt to generate a WebP variant (if GD supports it).
     * 
     * **Validates: Requirements 7.5**
     * 
     * @test
     * @feature media-management
     * @property 17: Geração de Variantes para Imagens
     */
    public function optimize_image_attempts_webp_generation(): void
    {
        // Check if WebP is supported
        $webpSupported = function_exists('imagewebp');
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random image dimensions
            $width = mt_rand(100, 1000);
            $height = mt_rand(100, 1000);
            
            // Create test image
            $tempPath = $this->createTestImage($width, $height);
            
            try {
                // Optimize the image
                $variants = $this->service->optimizeImage($tempPath);
                
                if ($webpSupported) {
                    // If WebP is supported, verify it was generated
                    $this->assertArrayHasKey(
                        'webp',
                        $variants,
                        "Iteration {$iteration}: Image should have webp variant when WebP is supported"
                    );
                } else {
                    // If WebP is not supported, test passes (graceful degradation)
                    $this->assertTrue(true, "WebP not supported, skipping WebP variant check");
                }
            } finally {
                // Cleanup
                @unlink($tempPath);
                
                // Cleanup variants
                if (isset($variants)) {
                    foreach ($variants as $variantPath) {
                        $fullPath = Storage::disk('local')->path($variantPath);
                        @unlink($fullPath);
                    }
                }
            }
        }
    }

    /**
     * Property 17: Geração de Variantes para Imagens
     * 
     * For any large image (>= 600px in both dimensions), the optimizeImage method
     * should generate 1x and 2x variants.
     * 
     * **Validates: Requirements 7.5**
     * 
     * @test
     * @feature media-management
     * @property 17: Geração de Variantes para Imagens
     */
    public function large_images_get_resolution_variants(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate large image dimensions (>= 600px in both dimensions)
            $width = mt_rand(600, 2000);
            $height = mt_rand(600, 2000);
            
            // Create test image
            $tempPath = $this->createTestImage($width, $height);
            
            try {
                // Optimize the image
                $variants = $this->service->optimizeImage($tempPath);
                
                // Verify 1x variant was generated
                $this->assertArrayHasKey(
                    '1x',
                    $variants,
                    "Iteration {$iteration}: Large image {$width}x{$height} should have 1x variant"
                );
                
                // Verify 2x variant was generated (points to original)
                $this->assertArrayHasKey(
                    '2x',
                    $variants,
                    "Iteration {$iteration}: Large image {$width}x{$height} should have 2x variant"
                );
            } finally {
                // Cleanup
                @unlink($tempPath);
                
                // Cleanup variants
                if (isset($variants)) {
                    foreach ($variants as $variantPath) {
                        $fullPath = Storage::disk('local')->path($variantPath);
                        @unlink($fullPath);
                    }
                }
            }
        }
    }

    /**
     * Property 17: Geração de Variantes para Imagens
     * 
     * For any small image (< 600px in either dimension), the optimizeImage method
     * should NOT generate 1x/2x variants.
     * 
     * **Validates: Requirements 7.5**
     * 
     * @test
     * @feature media-management
     * @property 17: Geração de Variantes para Imagens
     */
    public function small_images_skip_resolution_variants(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate small image dimensions (< 600px in at least one dimension)
            $width = mt_rand(100, 599);
            $height = mt_rand(100, 599);
            
            // Create test image
            $tempPath = $this->createTestImage($width, $height);
            
            try {
                // Optimize the image
                $variants = $this->service->optimizeImage($tempPath);
                
                // Verify 1x variant was NOT generated
                $this->assertArrayNotHasKey(
                    '1x',
                    $variants,
                    "Iteration {$iteration}: Small image {$width}x{$height} should NOT have 1x variant"
                );
                
                // Verify 2x variant was NOT generated
                $this->assertArrayNotHasKey(
                    '2x',
                    $variants,
                    "Iteration {$iteration}: Small image {$width}x{$height} should NOT have 2x variant"
                );
            } finally {
                // Cleanup
                @unlink($tempPath);
                
                // Cleanup variants
                if (isset($variants)) {
                    foreach ($variants as $variantPath) {
                        $fullPath = Storage::disk('local')->path($variantPath);
                        @unlink($fullPath);
                    }
                }
            }
        }
    }

    /**
     * Property 17: Geração de Variantes para Imagens
     * 
     * Variant generation works for all supported image formats.
     * 
     * **Validates: Requirements 7.5**
     * 
     * @test
     * @feature media-management
     * @property 17: Geração de Variantes para Imagens
     */
    public function variant_generation_works_for_all_formats(): void
    {
        $formats = ['jpg', 'png', 'gif'];
        
        foreach ($formats as $format) {
            for ($i = 0; $i < 20; $i++) {
                // Generate random image dimensions
                $width = mt_rand(100, 800);
                $height = mt_rand(100, 800);
                
                // Create test image
                $tempPath = $this->createTestImage($width, $height, $format);
                
                try {
                    // Optimize the image
                    $variants = $this->service->optimizeImage($tempPath);
                    
                    // Verify at least thumbnail was generated
                    $this->assertArrayHasKey(
                        'thumbnail',
                        $variants,
                        "Format {$format}, iteration {$i}: Should have thumbnail variant"
                    );
                } finally {
                    // Cleanup
                    @unlink($tempPath);
                    
                    // Cleanup variants
                    if (isset($variants)) {
                        foreach ($variants as $variantPath) {
                            $fullPath = Storage::disk('local')->path($variantPath);
                            @unlink($fullPath);
                        }
                    }
                }
            }
        }
    }

    /**
     * Property 17: Geração de Variantes para Imagens
     * 
     * Non-existent files return empty variants array.
     * 
     * **Validates: Requirements 7.5**
     * 
     * @test
     * @feature media-management
     * @property 17: Geração de Variantes para Imagens
     */
    public function non_existent_files_return_empty_variants(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate a path that doesn't exist
            $fakePath = sys_get_temp_dir() . '/non_existent_' . uniqid() . '.jpg';
            
            // Optimize the non-existent image
            $variants = $this->service->optimizeImage($fakePath);
            
            // Verify empty array is returned
            $this->assertEmpty(
                $variants,
                "Iteration {$iteration}: Non-existent file should return empty variants"
            );
        }
    }
}
