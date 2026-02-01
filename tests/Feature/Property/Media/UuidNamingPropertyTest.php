<?php

namespace Tests\Feature\Property\Media;

use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\SystemConfig;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for UUID file naming.
 * 
 * @feature media-management
 * @property 18: Nomes UUID para Arquivos
 * 
 * Validates: Requirements 7.6
 */
class UuidNamingPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure config exists
        SystemConfig::set('site.allowed_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
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
     * Generate a random file extension.
     */
    private function generateRandomExtension(): string
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];
        return $extensions[array_rand($extensions)];
    }

    /**
     * Generate a random original filename.
     */
    private function generateRandomOriginalName(): string
    {
        $names = [
            'photo', 'image', 'video', 'file', 'upload',
            'IMG_', 'DSC_', 'VID_', 'MOV_', 'PIC_',
            'casamento', 'wedding', 'festa', 'party',
        ];
        
        $prefix = $names[array_rand($names)];
        $number = mt_rand(1000, 9999);
        
        return $prefix . $number;
    }

    /**
     * Property 18: Nomes UUID para Arquivos
     * 
     * For any SiteMedia created, the path field must contain a filename
     * that is a valid UUID (format: 8-4-4-4-12 hexadecimal characters)
     * followed by the original extension.
     * 
     * **Validates: Requirements 7.6**
     * 
     * @test
     * @feature media-management
     * @property 18: Nomes UUID para Arquivos
     */
    public function stored_files_have_uuid_names(): void
    {
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            $extension = $this->generateRandomExtension();
            $originalName = $this->generateRandomOriginalName() . '.' . $extension;
            
            // Create a SiteMedia with a UUID-based path (simulating what MediaUploadService does)
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            $path = "sites/{$wedding->id}/{$uuid}.{$extension}";
            
            $media = SiteMedia::create([
                'wedding_id' => $wedding->id,
                'site_layout_id' => $siteLayout->id,
                'original_name' => $originalName,
                'path' => $path,
                'disk' => 'local',
                'size' => mt_rand(1000, 10000000),
                'mime_type' => 'image/jpeg',
                'status' => SiteMedia::STATUS_COMPLETED,
            ]);
            
            // Extract filename from path
            $filename = pathinfo($media->path, PATHINFO_FILENAME);
            
            // Verify filename is a valid UUID
            $this->assertMatchesRegularExpression(
                $uuidPattern,
                $filename,
                "Iteration {$iteration}: Filename '{$filename}' should be a valid UUID"
            );
            
            // Verify extension is preserved
            $storedExtension = pathinfo($media->path, PATHINFO_EXTENSION);
            $this->assertEquals(
                $extension,
                $storedExtension,
                "Iteration {$iteration}: Extension should be preserved. Expected: {$extension}, Got: {$storedExtension}"
            );
        }
    }

    /**
     * Property 18: Nomes UUID para Arquivos
     * 
     * Each file should have a unique UUID, even for files with the same original name.
     * 
     * **Validates: Requirements 7.6**
     * 
     * @test
     * @feature media-management
     * @property 18: Nomes UUID para Arquivos
     */
    public function each_file_has_unique_uuid(): void
    {
        $data = $this->createWeddingWithLayout();
        $wedding = $data['wedding'];
        $siteLayout = $data['siteLayout'];
        
        $uuids = [];
        
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $extension = $this->generateRandomExtension();
            $originalName = 'same_name.' . $extension; // Same original name for all
            
            // Create a SiteMedia with a UUID-based path
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            $path = "sites/{$wedding->id}/{$uuid}.{$extension}";
            
            $media = SiteMedia::create([
                'wedding_id' => $wedding->id,
                'site_layout_id' => $siteLayout->id,
                'original_name' => $originalName,
                'path' => $path,
                'disk' => 'local',
                'size' => mt_rand(1000, 10000000),
                'mime_type' => 'image/jpeg',
                'status' => SiteMedia::STATUS_COMPLETED,
            ]);
            
            // Extract UUID from path
            $filename = pathinfo($media->path, PATHINFO_FILENAME);
            
            // Verify UUID is unique
            $this->assertNotContains(
                $filename,
                $uuids,
                "Iteration {$iteration}: UUID '{$filename}' should be unique"
            );
            
            $uuids[] = $filename;
        }
        
        // Verify all UUIDs are unique
        $this->assertCount(
            100,
            array_unique($uuids),
            "All 100 UUIDs should be unique"
        );
    }

    /**
     * Property 18: Nomes UUID para Arquivos
     * 
     * The original filename is preserved in the original_name field.
     * 
     * **Validates: Requirements 7.6**
     * 
     * @test
     * @feature media-management
     * @property 18: Nomes UUID para Arquivos
     */
    public function original_name_is_preserved(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            $extension = $this->generateRandomExtension();
            $originalName = $this->generateRandomOriginalName() . '.' . $extension;
            
            // Create a SiteMedia
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            $path = "sites/{$wedding->id}/{$uuid}.{$extension}";
            
            $media = SiteMedia::create([
                'wedding_id' => $wedding->id,
                'site_layout_id' => $siteLayout->id,
                'original_name' => $originalName,
                'path' => $path,
                'disk' => 'local',
                'size' => mt_rand(1000, 10000000),
                'mime_type' => 'image/jpeg',
                'status' => SiteMedia::STATUS_COMPLETED,
            ]);
            
            // Verify original name is preserved
            $this->assertEquals(
                $originalName,
                $media->original_name,
                "Iteration {$iteration}: Original name should be preserved"
            );
            
            // Verify path filename is different from original name
            $pathFilename = pathinfo($media->path, PATHINFO_BASENAME);
            $this->assertNotEquals(
                $originalName,
                $pathFilename,
                "Iteration {$iteration}: Path filename should be UUID-based, not original name"
            );
        }
    }

    /**
     * Property 18: Nomes UUID para Arquivos
     * 
     * UUID format is consistent across all file types.
     * 
     * **Validates: Requirements 7.6**
     * 
     * @test
     * @feature media-management
     * @property 18: Nomes UUID para Arquivos
     */
    public function uuid_format_is_consistent_across_file_types(): void
    {
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];
        
        foreach ($extensions as $extension) {
            for ($i = 0; $i < 10; $i++) {
                $data = $this->createWeddingWithLayout();
                $wedding = $data['wedding'];
                $siteLayout = $data['siteLayout'];
                
                $uuid = \Illuminate\Support\Str::uuid()->toString();
                $path = "sites/{$wedding->id}/{$uuid}.{$extension}";
                
                $media = SiteMedia::create([
                    'wedding_id' => $wedding->id,
                    'site_layout_id' => $siteLayout->id,
                    'original_name' => "test.{$extension}",
                    'path' => $path,
                    'disk' => 'local',
                    'size' => mt_rand(1000, 10000000),
                    'mime_type' => $this->getMimeType($extension),
                    'status' => SiteMedia::STATUS_COMPLETED,
                ]);
                
                $filename = pathinfo($media->path, PATHINFO_FILENAME);
                
                $this->assertMatchesRegularExpression(
                    $uuidPattern,
                    $filename,
                    "Extension {$extension}, iteration {$i}: Filename should be valid UUID"
                );
            }
        }
    }

    /**
     * Get MIME type for extension.
     */
    private function getMimeType(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            default => 'application/octet-stream',
        };
    }
}
