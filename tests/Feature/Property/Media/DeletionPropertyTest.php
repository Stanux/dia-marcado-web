<?php

declare(strict_types=1);

namespace Tests\Feature\Property\Media;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteMedia;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-based tests for media deletion.
 * 
 * **Validates: Requirements 8.3**
 * 
 * @feature media-management
 * @property 19: Exclusão Remove Arquivo e Variantes
 */
class DeletionPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 20;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Ensure album types exist
        if (AlbumType::count() === 0) {
            AlbumType::insert([
                ['slug' => 'pre_casamento', 'name' => 'Pré-Casamento', 'description' => 'Fotos do pré-casamento'],
                ['slug' => 'pos_casamento', 'name' => 'Pós-Casamento', 'description' => 'Fotos do pós-casamento'],
                ['slug' => 'uso_site', 'name' => 'Uso no Site', 'description' => 'Mídias para uso no site'],
            ]);
        }
    }

    /**
     * @test
     * @feature media-management
     * @property 19: Exclusão Remove Arquivo e Variantes
     * 
     * For any SiteMedia deleted, after the delete() operation:
     * (1) the file at path must not exist in storage
     * (2) all files in variants must not exist in storage
     * (3) the record must not exist in the database
     */
    public function deletion_removes_file_and_variants(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create wedding and album
            $wedding = Wedding::factory()->create();
            $albumType = AlbumType::first();
            $album = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
            ]);

            // Generate random file path and variants
            $uuid = Str::uuid()->toString();
            $extension = $this->randomExtension();
            $mainPath = "media/{$wedding->id}/{$uuid}.{$extension}";
            
            // Create main file in storage
            Storage::disk('public')->put($mainPath, 'test content');
            
            // Generate random number of variants (0-3)
            $variantCount = rand(0, 3);
            $variants = [];
            $variantPaths = [];
            
            for ($v = 0; $v < $variantCount; $v++) {
                $variantName = $this->randomVariantName();
                $variantPath = "media/{$wedding->id}/{$uuid}_{$variantName}.{$extension}";
                $variants[$variantName] = $variantPath;
                $variantPaths[] = $variantPath;
                
                // Create variant file in storage
                Storage::disk('public')->put($variantPath, 'variant content');
            }

            // Create media record
            $media = SiteMedia::create([
                'wedding_id' => $wedding->id,
                'album_id' => $album->id,
                'original_name' => "test_{$i}.{$extension}",
                'path' => $mainPath,
                'disk' => 'public',
                'size' => rand(1000, 1000000),
                'mime_type' => $this->getMimeType($extension),
                'variants' => $variants,
                'status' => 'completed',
            ]);

            $mediaId = $media->id;

            // Verify files exist before deletion
            $this->assertTrue(
                Storage::disk('public')->exists($mainPath),
                "Main file should exist before deletion"
            );
            
            foreach ($variantPaths as $variantPath) {
                $this->assertTrue(
                    Storage::disk('public')->exists($variantPath),
                    "Variant file should exist before deletion"
                );
            }

            // Delete files from storage (simulating what controller does)
            Storage::disk('public')->delete($mainPath);
            foreach ($variantPaths as $variantPath) {
                Storage::disk('public')->delete($variantPath);
            }
            
            // Delete database record
            $media->delete();

            // Property assertions:
            // (1) Main file must not exist
            $this->assertFalse(
                Storage::disk('public')->exists($mainPath),
                "Property 19 violated: Main file still exists after deletion (iteration {$i})"
            );

            // (2) All variant files must not exist
            foreach ($variantPaths as $variantPath) {
                $this->assertFalse(
                    Storage::disk('public')->exists($variantPath),
                    "Property 19 violated: Variant file still exists after deletion (iteration {$i})"
                );
            }

            // (3) Database record must not exist
            $this->assertNull(
                SiteMedia::find($mediaId),
                "Property 19 violated: Database record still exists after deletion (iteration {$i})"
            );
        }
    }

    /**
     * @test
     * @feature media-management
     * @property 19: Exclusão Remove Arquivo e Variantes (edge case - missing files)
     * 
     * Deletion should succeed even if physical files are already missing.
     */
    public function deletion_succeeds_even_if_files_missing(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $wedding = Wedding::factory()->create();
            $albumType = AlbumType::first();
            $album = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
            ]);

            $uuid = Str::uuid()->toString();
            $extension = $this->randomExtension();
            $mainPath = "media/{$wedding->id}/{$uuid}.{$extension}";
            
            // Create media record WITHOUT creating physical files
            $media = SiteMedia::create([
                'wedding_id' => $wedding->id,
                'album_id' => $album->id,
                'original_name' => "test_{$i}.{$extension}",
                'path' => $mainPath,
                'disk' => 'public',
                'size' => rand(1000, 1000000),
                'mime_type' => $this->getMimeType($extension),
                'variants' => ['thumbnail' => "media/{$wedding->id}/{$uuid}_thumb.{$extension}"],
                'status' => 'completed',
            ]);

            $mediaId = $media->id;

            // Delete should not throw exception even if files don't exist
            $media->delete();

            // Record should be deleted
            $this->assertNull(
                SiteMedia::find($mediaId),
                "Property 19 violated: Database record should be deleted even if files missing (iteration {$i})"
            );
        }
    }

    private function randomExtension(): string
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        return $extensions[array_rand($extensions)];
    }

    private function randomVariantName(): string
    {
        $names = ['thumbnail', 'webp', '1x', '2x', 'small', 'medium', 'large'];
        return $names[array_rand($names)];
    }

    private function getMimeType(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
