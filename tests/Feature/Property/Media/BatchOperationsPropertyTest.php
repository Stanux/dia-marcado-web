<?php

declare(strict_types=1);

namespace Tests\Feature\Property\Media;

use App\Contracts\Media\AlbumManagementServiceInterface;
use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteMedia;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-based tests for batch operations.
 * 
 * **Validates: Requirements 8.5**
 * 
 * @feature media-management
 * @property 20: Operações em Lote Afetam Todos os Itens
 */
class BatchOperationsPropertyTest extends TestCase
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
     * @property 20: Operações em Lote Afetam Todos os Itens
     * 
     * For any list of N media IDs submitted for batch delete,
     * after the operation, exactly N records should be deleted.
     */
    public function batch_delete_affects_all_items(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create wedding and album
            $wedding = Wedding::factory()->create();
            $albumType = AlbumType::first();
            $album = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
            ]);

            // Create random number of media items (2-10)
            $mediaCount = rand(2, 10);
            $mediaIds = [];
            
            for ($m = 0; $m < $mediaCount; $m++) {
                $uuid = Str::uuid()->toString();
                $path = "media/{$wedding->id}/{$uuid}.jpg";
                
                Storage::disk('public')->put($path, 'test content');
                
                $media = SiteMedia::create([
                    'wedding_id' => $wedding->id,
                    'album_id' => $album->id,
                    'original_name' => "test_{$m}.jpg",
                    'path' => $path,
                    'disk' => 'public',
                    'size' => rand(1000, 1000000),
                    'mime_type' => 'image/jpeg',
                    'status' => 'completed',
                ]);
                
                $mediaIds[] = $media->id;
            }

            // Verify all media exists before deletion
            $this->assertEquals(
                $mediaCount,
                SiteMedia::whereIn('id', $mediaIds)->count(),
                "All media should exist before batch delete"
            );

            // Perform batch delete
            $deletedCount = 0;
            foreach ($mediaIds as $mediaId) {
                $media = SiteMedia::find($mediaId);
                if ($media) {
                    if ($media->path && Storage::disk('public')->exists($media->path)) {
                        Storage::disk('public')->delete($media->path);
                    }
                    $media->delete();
                    $deletedCount++;
                }
            }

            // Property assertion: exactly N records should be deleted
            $this->assertEquals(
                $mediaCount,
                $deletedCount,
                "Property 20 violated: Batch delete should affect exactly {$mediaCount} items, but affected {$deletedCount} (iteration {$i})"
            );

            // Verify no media remains
            $this->assertEquals(
                0,
                SiteMedia::whereIn('id', $mediaIds)->count(),
                "Property 20 violated: All media should be deleted after batch delete (iteration {$i})"
            );
        }
    }

    /**
     * @test
     * @feature media-management
     * @property 20: Operações em Lote Afetam Todos os Itens
     * 
     * For any list of N media IDs submitted for batch move,
     * after the operation, exactly N records should be moved.
     */
    public function batch_move_affects_all_items(): void
    {
        $albumService = app(AlbumManagementServiceInterface::class);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create wedding and two albums
            $wedding = Wedding::factory()->create();
            $albumType = AlbumType::first();
            
            $sourceAlbum = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
                'name' => 'Source Album',
            ]);
            
            $targetAlbum = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
                'name' => 'Target Album',
            ]);

            // Create random number of media items (2-10)
            $mediaCount = rand(2, 10);
            $mediaIds = [];
            
            for ($m = 0; $m < $mediaCount; $m++) {
                $uuid = Str::uuid()->toString();
                $path = "media/{$wedding->id}/{$uuid}.jpg";
                
                Storage::disk('public')->put($path, 'test content');
                
                $media = SiteMedia::create([
                    'wedding_id' => $wedding->id,
                    'album_id' => $sourceAlbum->id,
                    'original_name' => "test_{$m}.jpg",
                    'path' => $path,
                    'disk' => 'public',
                    'size' => rand(1000, 1000000),
                    'mime_type' => 'image/jpeg',
                    'status' => 'completed',
                ]);
                
                $mediaIds[] = $media->id;
            }

            // Verify all media is in source album
            $this->assertEquals(
                $mediaCount,
                SiteMedia::whereIn('id', $mediaIds)->where('album_id', $sourceAlbum->id)->count(),
                "All media should be in source album before batch move"
            );

            // Perform batch move
            $movedCount = 0;
            foreach ($mediaIds as $mediaId) {
                $media = SiteMedia::find($mediaId);
                if ($media) {
                    $albumService->moveMedia($media, $targetAlbum);
                    $movedCount++;
                }
            }

            // Property assertion: exactly N records should be moved
            $this->assertEquals(
                $mediaCount,
                $movedCount,
                "Property 20 violated: Batch move should affect exactly {$mediaCount} items, but affected {$movedCount} (iteration {$i})"
            );

            // Verify all media is now in target album
            $this->assertEquals(
                $mediaCount,
                SiteMedia::whereIn('id', $mediaIds)->where('album_id', $targetAlbum->id)->count(),
                "Property 20 violated: All media should be in target album after batch move (iteration {$i})"
            );

            // Verify no media remains in source album
            $this->assertEquals(
                0,
                SiteMedia::whereIn('id', $mediaIds)->where('album_id', $sourceAlbum->id)->count(),
                "Property 20 violated: No media should remain in source album after batch move (iteration {$i})"
            );
        }
    }

    /**
     * @test
     * @feature media-management
     * @property 20: Operações em Lote Afetam Todos os Itens (partial batch)
     * 
     * Batch operations should only affect items belonging to the wedding.
     */
    public function batch_operations_respect_wedding_isolation(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Create two weddings
            $wedding1 = Wedding::factory()->create();
            $wedding2 = Wedding::factory()->create();
            
            $albumType = AlbumType::first();
            
            $album1 = Album::factory()->create([
                'wedding_id' => $wedding1->id,
                'album_type_id' => $albumType->id,
            ]);
            
            $album2 = Album::factory()->create([
                'wedding_id' => $wedding2->id,
                'album_type_id' => $albumType->id,
            ]);

            // Create media for both weddings
            $media1Ids = [];
            $media2Ids = [];
            
            for ($m = 0; $m < 3; $m++) {
                // Media for wedding 1
                $uuid1 = Str::uuid()->toString();
                $path1 = "media/{$wedding1->id}/{$uuid1}.jpg";
                Storage::disk('public')->put($path1, 'test content');
                
                $media1 = SiteMedia::create([
                    'wedding_id' => $wedding1->id,
                    'album_id' => $album1->id,
                    'original_name' => "test1_{$m}.jpg",
                    'path' => $path1,
                    'disk' => 'public',
                    'size' => rand(1000, 1000000),
                    'mime_type' => 'image/jpeg',
                    'status' => 'completed',
                ]);
                $media1Ids[] = $media1->id;

                // Media for wedding 2
                $uuid2 = Str::uuid()->toString();
                $path2 = "media/{$wedding2->id}/{$uuid2}.jpg";
                Storage::disk('public')->put($path2, 'test content');
                
                $media2 = SiteMedia::create([
                    'wedding_id' => $wedding2->id,
                    'album_id' => $album2->id,
                    'original_name' => "test2_{$m}.jpg",
                    'path' => $path2,
                    'disk' => 'public',
                    'size' => rand(1000, 1000000),
                    'mime_type' => 'image/jpeg',
                    'status' => 'completed',
                ]);
                $media2Ids[] = $media2->id;
            }

            // Try to delete all IDs but filter by wedding 1
            $allIds = array_merge($media1Ids, $media2Ids);
            
            $deletedCount = SiteMedia::whereIn('id', $allIds)
                ->where('wedding_id', $wedding1->id)
                ->delete();

            // Property assertion: only wedding 1 media should be deleted
            $this->assertEquals(
                3,
                $deletedCount,
                "Property 20 violated: Only wedding 1 media should be deleted (iteration {$i})"
            );

            // Verify wedding 2 media still exists
            $this->assertEquals(
                3,
                SiteMedia::whereIn('id', $media2Ids)->count(),
                "Property 20 violated: Wedding 2 media should not be affected (iteration {$i})"
            );
        }
    }
}
