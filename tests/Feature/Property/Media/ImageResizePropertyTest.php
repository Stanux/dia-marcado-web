<?php

namespace Tests\Feature\Property\Media;

use App\Models\SystemConfig;
use App\Services\Site\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for automatic image resizing.
 * 
 * @feature media-management
 * @property 7: Redimensionamento Automático de Imagens Grandes
 * 
 * Validates: Requirements 3.4
 */
class ImageResizePropertyTest extends TestCase
{
    use RefreshDatabase;

    private MediaUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new MediaUploadService();
        
        // Ensure media config exists
        SystemConfig::set('media.max_image_width', 4096);
        SystemConfig::set('media.max_image_height', 4096);
    }

    /**
     * Property 7: Redimensionamento Automático de Imagens Grandes
     * 
     * For any image with dimensions larger than max_image_width or max_image_height,
     * checkImageDimensions should return exceeds=true.
     * 
     * **Validates: Requirements 3.4**
     * 
     * @test
     * @feature media-management
     * @property 7: Redimensionamento Automático de Imagens Grandes
     */
    public function check_image_dimensions_detects_oversized_images(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random max dimensions
            $maxWidth = mt_rand(100, 4096);
            $maxHeight = mt_rand(100, 4096);
            
            SystemConfig::set('media.max_image_width', $maxWidth);
            SystemConfig::set('media.max_image_height', $maxHeight);
            
            // Generate image dimensions that exceed limits
            $exceedsWidth = mt_rand(0, 1) === 1;
            $exceedsHeight = mt_rand(0, 1) === 1;
            
            // Ensure at least one dimension exceeds
            if (!$exceedsWidth && !$exceedsHeight) {
                $exceedsWidth = true;
            }
            
            $imageWidth = $exceedsWidth ? $maxWidth + mt_rand(1, 1000) : mt_rand(1, $maxWidth);
            $imageHeight = $exceedsHeight ? $maxHeight + mt_rand(1, 1000) : mt_rand(1, $maxHeight);
            
            // Create a test image
            $tempPath = $this->createTestImage($imageWidth, $imageHeight);
            
            try {
                // Check dimensions
                $result = $this->service->checkImageDimensions($tempPath);
                
                // Verify exceeds is true when dimensions exceed limits
                $shouldExceed = $imageWidth > $maxWidth || $imageHeight > $maxHeight;
                $this->assertEquals(
                    $shouldExceed,
                    $result['exceeds'],
                    "Iteration {$iteration}: Image {$imageWidth}x{$imageHeight} with max {$maxWidth}x{$maxHeight} - exceeds should be " . ($shouldExceed ? 'true' : 'false')
                );
                
                // Verify dimensions are correctly reported
                $this->assertEquals($imageWidth, $result['width'], "Iteration {$iteration}: Width should be {$imageWidth}");
                $this->assertEquals($imageHeight, $result['height'], "Iteration {$iteration}: Height should be {$imageHeight}");
                $this->assertEquals($maxWidth, $result['maxWidth'], "Iteration {$iteration}: maxWidth should be {$maxWidth}");
                $this->assertEquals($maxHeight, $result['maxHeight'], "Iteration {$iteration}: maxHeight should be {$maxHeight}");
            } finally {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Property 7: Redimensionamento Automático de Imagens Grandes
     * 
     * For any image within max dimensions, checkImageDimensions should return exceeds=false.
     * 
     * **Validates: Requirements 3.4**
     * 
     * @test
     * @feature media-management
     * @property 7: Redimensionamento Automático de Imagens Grandes
     */
    public function check_image_dimensions_allows_valid_images(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random max dimensions
            $maxWidth = mt_rand(100, 4096);
            $maxHeight = mt_rand(100, 4096);
            
            SystemConfig::set('media.max_image_width', $maxWidth);
            SystemConfig::set('media.max_image_height', $maxHeight);
            
            // Generate image dimensions within limits
            $imageWidth = mt_rand(1, $maxWidth);
            $imageHeight = mt_rand(1, $maxHeight);
            
            // Create a test image
            $tempPath = $this->createTestImage($imageWidth, $imageHeight);
            
            try {
                // Check dimensions
                $result = $this->service->checkImageDimensions($tempPath);
                
                // Verify exceeds is false
                $this->assertFalse(
                    $result['exceeds'],
                    "Iteration {$iteration}: Image {$imageWidth}x{$imageHeight} within max {$maxWidth}x{$maxHeight} should not exceed"
                );
            } finally {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Property 7: Redimensionamento Automático de Imagens Grandes
     * 
     * For any image that is resized, the resulting dimensions should be within
     * the configured limits while maintaining aspect ratio.
     * 
     * **Validates: Requirements 3.4**
     * 
     * @test
     * @feature media-management
     * @property 7: Redimensionamento Automático de Imagens Grandes
     */
    public function resize_maintains_aspect_ratio_and_fits_limits(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random max dimensions
            $maxWidth = mt_rand(100, 500);
            $maxHeight = mt_rand(100, 500);
            
            SystemConfig::set('media.max_image_width', $maxWidth);
            SystemConfig::set('media.max_image_height', $maxHeight);
            
            // Generate oversized image dimensions
            $imageWidth = $maxWidth + mt_rand(100, 1000);
            $imageHeight = $maxHeight + mt_rand(100, 1000);
            $originalRatio = $imageWidth / $imageHeight;
            
            // Create a test image
            $tempPath = $this->createTestImage($imageWidth, $imageHeight);
            
            try {
                // Resize the image
                $resized = $this->service->resizeToMaxDimensions($tempPath);
                
                // Verify resize happened
                $this->assertTrue(
                    $resized,
                    "Iteration {$iteration}: Image {$imageWidth}x{$imageHeight} should be resized"
                );
                
                // Check new dimensions
                $newDimensions = $this->service->checkImageDimensions($tempPath);
                
                // Verify new dimensions are within limits
                $this->assertLessThanOrEqual(
                    $maxWidth,
                    $newDimensions['width'],
                    "Iteration {$iteration}: Resized width should be <= {$maxWidth}"
                );
                
                $this->assertLessThanOrEqual(
                    $maxHeight,
                    $newDimensions['height'],
                    "Iteration {$iteration}: Resized height should be <= {$maxHeight}"
                );
                
                // Verify aspect ratio is maintained (with small tolerance for rounding)
                if ($newDimensions['width'] > 0 && $newDimensions['height'] > 0) {
                    $newRatio = $newDimensions['width'] / $newDimensions['height'];
                    $ratioDiff = abs($originalRatio - $newRatio);
                    
                    $this->assertLessThan(
                        0.1, // Allow 10% tolerance for rounding
                        $ratioDiff,
                        "Iteration {$iteration}: Aspect ratio should be maintained. Original: {$originalRatio}, New: {$newRatio}"
                    );
                }
            } finally {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Property 7: Redimensionamento Automático de Imagens Grandes
     * 
     * For any image within limits, resizeToMaxDimensions should return false
     * (no resize needed).
     * 
     * **Validates: Requirements 3.4**
     * 
     * @test
     * @feature media-management
     * @property 7: Redimensionamento Automático de Imagens Grandes
     */
    public function resize_skips_images_within_limits(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random max dimensions
            $maxWidth = mt_rand(200, 4096);
            $maxHeight = mt_rand(200, 4096);
            
            SystemConfig::set('media.max_image_width', $maxWidth);
            SystemConfig::set('media.max_image_height', $maxHeight);
            
            // Generate image dimensions within limits
            $imageWidth = mt_rand(10, $maxWidth);
            $imageHeight = mt_rand(10, $maxHeight);
            
            // Create a test image
            $tempPath = $this->createTestImage($imageWidth, $imageHeight);
            
            try {
                // Attempt resize
                $resized = $this->service->resizeToMaxDimensions($tempPath);
                
                // Verify no resize happened
                $this->assertFalse(
                    $resized,
                    "Iteration {$iteration}: Image {$imageWidth}x{$imageHeight} within max {$maxWidth}x{$maxHeight} should not be resized"
                );
                
                // Verify dimensions unchanged
                $dimensions = $this->service->checkImageDimensions($tempPath);
                $this->assertEquals($imageWidth, $dimensions['width'], "Iteration {$iteration}: Width should be unchanged");
                $this->assertEquals($imageHeight, $dimensions['height'], "Iteration {$iteration}: Height should be unchanged");
            } finally {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Create a test image with specified dimensions.
     * 
     * @param int $width Image width
     * @param int $height Image height
     * @return string Path to the created image
     */
    private function createTestImage(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        
        // Fill with a random color
        $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imagefill($image, 0, 0, $color);
        
        // Save to temp file
        $tempPath = sys_get_temp_dir() . '/test_image_' . uniqid() . '.jpg';
        imagejpeg($image, $tempPath, 90);
        imagedestroy($image);
        
        return $tempPath;
    }
}
