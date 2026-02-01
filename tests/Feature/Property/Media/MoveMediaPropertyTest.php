<?php

namespace Tests\Feature\Property\Media;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\Wedding;
use App\Services\Media\AlbumManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for moving media between albums.
 * 
 * @feature media-management
 * @property 6: Mover Mídia Preserva Arquivo Único
 * 
 * Validates: Requirements 2.5, 8.4
 */
class MoveMediaPropertyTest extends TestCase
{
    use RefreshDatabase;

    private AlbumManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new AlbumManagementService();
        
        // Ensure album types exist
        $this->createAlbumTypes();
    }

    /**
     * Create default album types for testing.
     */
    private function createAlbumTypes(): void
    {
        AlbumType::firstOrCreate(
            ['slug' => AlbumType::PRE_WEDDING],
            ['name' => 'Pré Casamento', 'description' => 'Fotos e vídeos do pré-casamento']
        );
        
        AlbumType::firstOrCreate(
            ['slug' => AlbumType::POST_WEDDING],
            ['name' => 'Pós Casamento', 'description' => 'Fotos e vídeos do pós-casamento']
        );
        
        AlbumType::firstOrCreate(
            ['slug' => AlbumType::SITE_USAGE],
            ['name' => 'Uso do Site', 'description' => 'Imagens para uso no site']
        );
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
     * Generate a random album name.
     */
    private function generateAlbumName(): string
    {
        $prefixes = ['Ensaio', 'Fotos', 'Vídeos', 'Álbum', 'Galeria', 'Coleção'];
        $suffixes = ['Principal', 'Especial', 'Favoritos', 'Seleção', 'Memórias', 'Momentos'];
        
        return $prefixes[array_rand($prefixes)] . ' ' . $suffixes[array_rand($suffixes)] . ' ' . mt_rand(1, 1000);
    }

    /**
     * Generate a random file path.
     */
    private function generateFilePath(): string
    {
        return 'media/' . uniqid() . '_' . mt_rand(1000, 9999) . '.jpg';
    }

    /**
     * Create a completed SiteMedia for a wedding.
     */
    private function createCompletedMedia(Wedding $wedding, SiteLayout $siteLayout, ?Album $album = null): SiteMedia
    {
        return SiteMedia::create([
            'wedding_id' => $wedding->id,
            'site_layout_id' => $siteLayout->id,
            'album_id' => $album?->id,
            'original_name' => 'test-' . uniqid() . '.jpg',
            'path' => $this->generateFilePath(),
            'disk' => 'public',
            'size' => mt_rand(1000, 10000000),
            'mime_type' => 'image/jpeg',
            'status' => SiteMedia::STATUS_COMPLETED,
        ]);
    }

    /**
     * Get a random album type slug.
     */
    private function getRandomTypeSlug(): string
    {
        $types = AlbumType::getSlugs();
        return $types[array_rand($types)];
    }

    /**
     * Property 6: Mover Mídia Preserva Arquivo Único
     * 
     * For any move media operation from album A to album B, after the operation:
     * (1) the album_id of the media must be B
     * 
     * **Validates: Requirements 2.5, 8.4**
     * 
     * @test
     * @feature media-management
     * @property 6: Mover Mídia Preserva Arquivo Único
     */
    public function move_media_updates_album_id_to_target(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create source and target albums (can be same or different types)
            $sourceAlbum = Album::factory()->forWedding($wedding)->create([
                'album_type_id' => AlbumType::where('slug', $this->getRandomTypeSlug())->first()->id,
            ]);
            
            $targetAlbum = Album::factory()->forWedding($wedding)->create([
                'album_type_id' => AlbumType::where('slug', $this->getRandomTypeSlug())->first()->id,
            ]);
            
            // Create media in source album
            $media = $this->createCompletedMedia($wedding, $siteLayout, $sourceAlbum);
            
            // Move media to target album
            $result = $this->service->moveMedia($media, $targetAlbum);
            
            // Verify album_id is updated to target
            $this->assertEquals(
                $targetAlbum->id,
                $result->album_id,
                "Iteration {$iteration}: Media album_id should be updated to target album"
            );
            
            // Verify by refreshing from database
            $media->refresh();
            $this->assertEquals(
                $targetAlbum->id,
                $media->album_id,
                "Iteration {$iteration}: Media album_id should be persisted in database"
            );
        }
    }

    /**
     * Property 6: Mover Mídia Preserva Arquivo Único
     * 
     * For any move media operation from album A to album B, after the operation:
     * (2) the path of the file must remain unchanged
     * 
     * **Validates: Requirements 2.5, 8.4**
     * 
     * @test
     * @feature media-management
     * @property 6: Mover Mídia Preserva Arquivo Único
     */
    public function move_media_preserves_file_path(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create source and target albums
            $sourceAlbum = Album::factory()->forWedding($wedding)->preWedding()->create();
            $targetAlbum = Album::factory()->forWedding($wedding)->postWedding()->create();
            
            // Create media in source album
            $media = $this->createCompletedMedia($wedding, $siteLayout, $sourceAlbum);
            $originalPath = $media->path;
            
            // Move media to target album
            $result = $this->service->moveMedia($media, $targetAlbum);
            
            // Verify path is unchanged
            $this->assertEquals(
                $originalPath,
                $result->path,
                "Iteration {$iteration}: Media path should remain unchanged after move"
            );
            
            // Verify by refreshing from database
            $media->refresh();
            $this->assertEquals(
                $originalPath,
                $media->path,
                "Iteration {$iteration}: Media path should be unchanged in database"
            );
        }
    }

    /**
     * Property 6: Mover Mídia Preserva Arquivo Único
     * 
     * For any move media operation, all other media attributes must remain unchanged.
     * 
     * **Validates: Requirements 2.5, 8.4**
     * 
     * @test
     * @feature media-management
     * @property 6: Mover Mídia Preserva Arquivo Único
     */
    public function move_media_preserves_all_other_attributes(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create source and target albums
            $sourceAlbum = Album::factory()->forWedding($wedding)->preWedding()->create();
            $targetAlbum = Album::factory()->forWedding($wedding)->postWedding()->create();
            
            // Create media in source album
            $media = $this->createCompletedMedia($wedding, $siteLayout, $sourceAlbum);
            
            // Store original values
            $originalWeddingId = $media->wedding_id;
            $originalSiteLayoutId = $media->site_layout_id;
            $originalOriginalName = $media->original_name;
            $originalPath = $media->path;
            $originalDisk = $media->disk;
            $originalSize = $media->size;
            $originalMimeType = $media->mime_type;
            $originalStatus = $media->status;
            
            // Move media to target album
            $result = $this->service->moveMedia($media, $targetAlbum);
            
            // Verify all attributes except album_id are unchanged
            $this->assertEquals($originalWeddingId, $result->wedding_id, "Iteration {$iteration}: wedding_id should be unchanged");
            $this->assertEquals($originalSiteLayoutId, $result->site_layout_id, "Iteration {$iteration}: site_layout_id should be unchanged");
            $this->assertEquals($originalOriginalName, $result->original_name, "Iteration {$iteration}: original_name should be unchanged");
            $this->assertEquals($originalPath, $result->path, "Iteration {$iteration}: path should be unchanged");
            $this->assertEquals($originalDisk, $result->disk, "Iteration {$iteration}: disk should be unchanged");
            $this->assertEquals($originalSize, $result->size, "Iteration {$iteration}: size should be unchanged");
            $this->assertEquals($originalMimeType, $result->mime_type, "Iteration {$iteration}: mime_type should be unchanged");
            $this->assertEquals($originalStatus, $result->status, "Iteration {$iteration}: status should be unchanged");
        }
    }

    /**
     * Property 6: Mover Mídia Preserva Arquivo Único
     * 
     * For any move media operation where media and target album belong to different
     * weddings, the operation must fail.
     * 
     * **Validates: Requirements 2.5, 8.4**
     * 
     * @test
     * @feature media-management
     * @property 6: Mover Mídia Preserva Arquivo Único
     */
    public function move_media_fails_for_different_weddings(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Create two different weddings
            $data1 = $this->createWeddingWithLayout();
            $wedding1 = $data1['wedding'];
            $siteLayout1 = $data1['siteLayout'];
            
            $data2 = $this->createWeddingWithLayout();
            $wedding2 = $data2['wedding'];
            
            // Create album in wedding 1
            $sourceAlbum = Album::factory()->forWedding($wedding1)->preWedding()->create();
            
            // Create album in wedding 2
            $targetAlbum = Album::factory()->forWedding($wedding2)->preWedding()->create();
            
            // Create media in wedding 1
            $media = $this->createCompletedMedia($wedding1, $siteLayout1, $sourceAlbum);
            $originalAlbumId = $media->album_id;
            
            // Attempt to move media to album in different wedding
            $exceptionThrown = false;
            try {
                $this->service->moveMedia($media, $targetAlbum);
            } catch (InvalidArgumentException $e) {
                $exceptionThrown = true;
            }
            
            $this->assertTrue(
                $exceptionThrown,
                "Iteration {$iteration}: Moving media to album in different wedding should throw InvalidArgumentException"
            );
            
            // Verify media album_id was not changed
            $media->refresh();
            $this->assertEquals(
                $originalAlbumId,
                $media->album_id,
                "Iteration {$iteration}: Media album_id should not change after failed move"
            );
        }
    }

    /**
     * Property 6: Mover Mídia Preserva Arquivo Único
     * 
     * For any move media operation, the media should only exist once in the database
     * (no duplication).
     * 
     * **Validates: Requirements 2.5, 8.4**
     * 
     * @test
     * @feature media-management
     * @property 6: Mover Mídia Preserva Arquivo Único
     */
    public function move_media_does_not_duplicate_record(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create source and target albums
            $sourceAlbum = Album::factory()->forWedding($wedding)->preWedding()->create();
            $targetAlbum = Album::factory()->forWedding($wedding)->postWedding()->create();
            
            // Create media in source album
            $media = $this->createCompletedMedia($wedding, $siteLayout, $sourceAlbum);
            $mediaId = $media->id;
            $originalPath = $media->path;
            
            // Count media before move
            $countBefore = SiteMedia::where('wedding_id', $wedding->id)->count();
            
            // Move media to target album
            $this->service->moveMedia($media, $targetAlbum);
            
            // Count media after move
            $countAfter = SiteMedia::where('wedding_id', $wedding->id)->count();
            
            // Verify count is unchanged (no duplication)
            $this->assertEquals(
                $countBefore,
                $countAfter,
                "Iteration {$iteration}: Media count should not change after move (no duplication)"
            );
            
            // Verify only one record exists with this ID
            $mediaWithId = SiteMedia::where('id', $mediaId)->count();
            $this->assertEquals(
                1,
                $mediaWithId,
                "Iteration {$iteration}: Only one media record should exist with the same ID"
            );
            
            // Verify only one record exists with this path
            $mediaWithPath = SiteMedia::where('path', $originalPath)->count();
            $this->assertEquals(
                1,
                $mediaWithPath,
                "Iteration {$iteration}: Only one media record should exist with the same path"
            );
        }
    }

    /**
     * Property 6: Mover Mídia Preserva Arquivo Único
     * 
     * For any sequence of move operations on the same media, the path must remain
     * unchanged throughout all moves.
     * 
     * **Validates: Requirements 2.5, 8.4**
     * 
     * @test
     * @feature media-management
     * @property 6: Mover Mídia Preserva Arquivo Único
     */
    public function multiple_moves_preserve_file_path(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create multiple albums
            $albums = [];
            foreach (AlbumType::getSlugs() as $typeSlug) {
                $albums[] = Album::factory()->forWedding($wedding)->create([
                    'album_type_id' => AlbumType::where('slug', $typeSlug)->first()->id,
                ]);
            }
            
            // Create media in first album
            $media = $this->createCompletedMedia($wedding, $siteLayout, $albums[0]);
            $originalPath = $media->path;
            
            // Perform random number of moves (2-5)
            $moveCount = mt_rand(2, 5);
            for ($i = 0; $i < $moveCount; $i++) {
                // Pick a random target album (different from current)
                $currentAlbumId = $media->album_id;
                $availableAlbums = array_filter($albums, fn($a) => $a->id !== $currentAlbumId);
                $targetAlbum = $availableAlbums[array_rand($availableAlbums)];
                
                // Move media
                $media = $this->service->moveMedia($media, $targetAlbum);
                
                // Verify path is still unchanged
                $this->assertEquals(
                    $originalPath,
                    $media->path,
                    "Iteration {$iteration}, Move {$i}: Path should remain unchanged after move"
                );
            }
            
            // Final verification from database
            $media->refresh();
            $this->assertEquals(
                $originalPath,
                $media->path,
                "Iteration {$iteration}: Path should remain unchanged after all moves"
            );
        }
    }
}
