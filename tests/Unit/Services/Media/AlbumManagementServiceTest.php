<?php

namespace Tests\Unit\Services\Media;

use App\Contracts\Media\AlbumManagementServiceInterface;
use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\Wedding;
use App\Services\Media\AlbumManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Unit tests for AlbumManagementService.
 * 
 * Tests album creation, update, deletion, media movement, and grouping by type.
 * 
 * Validates: Requirements 2.2, 2.4, 2.5, 2.6
 */
class AlbumManagementServiceTest extends TestCase
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
     * Create a wedding with associated SiteLayout.
     */
    private function createWeddingWithLayout(): array
    {
        $wedding = Wedding::factory()->create();
        $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
        
        return ['wedding' => $wedding, 'siteLayout' => $siteLayout];
    }

    /**
     * Create a wedding (convenience method).
     */
    private function createWedding(): Wedding
    {
        return $this->createWeddingWithLayout()['wedding'];
    }

    /**
     * Create a completed SiteMedia for a wedding.
     */
    private function createCompletedMedia(Wedding $wedding, ?Album $album = null): SiteMedia
    {
        $siteLayout = SiteLayout::where('wedding_id', $wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $wedding->id]);
        }
        
        return SiteMedia::create([
            'wedding_id' => $wedding->id,
            'site_layout_id' => $siteLayout->id,
            'album_id' => $album?->id,
            'original_name' => 'test-' . uniqid() . '.jpg',
            'path' => 'media/' . uniqid() . '.jpg',
            'disk' => 'public',
            'size' => 1000000,
            'mime_type' => 'image/jpeg',
            'status' => SiteMedia::STATUS_COMPLETED,
        ]);
    }

    // ==========================================
    // Interface Implementation Tests
    // ==========================================

    #[Test]
    public function it_implements_album_management_service_interface(): void
    {
        $this->assertInstanceOf(AlbumManagementServiceInterface::class, $this->service);
    }

    // ==========================================
    // createAlbum Tests
    // ==========================================

    #[Test]
    public function create_album_creates_album_with_valid_type(): void
    {
        $wedding = $this->createWedding();
        
        $album = $this->service->createAlbum($wedding, AlbumType::PRE_WEDDING, [
            'name' => 'Ensaio Fotográfico',
            'description' => 'Fotos do ensaio pré-casamento',
        ]);
        
        $this->assertInstanceOf(Album::class, $album);
        $this->assertEquals($wedding->id, $album->wedding_id);
        $this->assertEquals('Ensaio Fotográfico', $album->name);
        $this->assertEquals('Fotos do ensaio pré-casamento', $album->description);
        $this->assertEquals(AlbumType::PRE_WEDDING, $album->albumType->slug);
    }

    #[Test]
    public function create_album_fails_with_empty_type_slug(): void
    {
        $wedding = $this->createWedding();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O tipo de álbum é obrigatório.');
        
        $this->service->createAlbum($wedding, '', ['name' => 'Test Album']);
    }

    #[Test]
    public function create_album_fails_with_invalid_type_slug(): void
    {
        $wedding = $this->createWedding();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tipo de álbum inválido: 'invalid_type'");
        
        $this->service->createAlbum($wedding, 'invalid_type', ['name' => 'Test Album']);
    }

    #[Test]
    public function create_album_fails_without_name(): void
    {
        $wedding = $this->createWedding();
        
        $this->expectException(ValidationException::class);
        
        $this->service->createAlbum($wedding, AlbumType::PRE_WEDDING, []);
    }

    #[Test]
    public function create_album_fails_with_empty_name(): void
    {
        $wedding = $this->createWedding();
        
        $this->expectException(ValidationException::class);
        
        $this->service->createAlbum($wedding, AlbumType::PRE_WEDDING, ['name' => '']);
    }

    #[Test]
    public function create_album_with_all_valid_types(): void
    {
        $wedding = $this->createWedding();
        $types = AlbumType::getSlugs();
        
        foreach ($types as $typeSlug) {
            $album = $this->service->createAlbum($wedding, $typeSlug, [
                'name' => "Album {$typeSlug}",
            ]);
            
            $this->assertEquals($typeSlug, $album->albumType->slug);
        }
        
        $this->assertCount(3, Album::where('wedding_id', $wedding->id)->get());
    }

    #[Test]
    public function create_album_with_cover_media(): void
    {
        $wedding = $this->createWedding();
        $media = $this->createCompletedMedia($wedding);
        
        $album = $this->service->createAlbum($wedding, AlbumType::PRE_WEDDING, [
            'name' => 'Album with Cover',
            'cover_media_id' => $media->id,
        ]);
        
        $this->assertEquals($media->id, $album->cover_media_id);
    }

    #[Test]
    public function create_album_fails_with_invalid_cover_media(): void
    {
        $wedding = $this->createWedding();
        
        $this->expectException(ValidationException::class);
        
        // Use a valid UUID format that doesn't exist in the database
        $this->service->createAlbum($wedding, AlbumType::PRE_WEDDING, [
            'name' => 'Album with Invalid Cover',
            'cover_media_id' => '00000000-0000-0000-0000-000000000000',
        ]);
    }

    #[Test]
    public function create_album_fails_with_cover_media_from_different_wedding(): void
    {
        $wedding1 = $this->createWedding();
        $wedding2 = $this->createWedding();
        $media = $this->createCompletedMedia($wedding2);
        
        $this->expectException(ValidationException::class);
        
        $this->service->createAlbum($wedding1, AlbumType::PRE_WEDDING, [
            'name' => 'Album with Wrong Cover',
            'cover_media_id' => $media->id,
        ]);
    }

    // ==========================================
    // updateAlbum Tests
    // ==========================================

    #[Test]
    public function update_album_updates_name(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create([
            'name' => 'Original Name',
        ]);
        
        $updated = $this->service->updateAlbum($album, ['name' => 'New Name']);
        
        $this->assertEquals('New Name', $updated->name);
    }

    #[Test]
    public function update_album_updates_description(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create([
            'description' => 'Original Description',
        ]);
        
        $updated = $this->service->updateAlbum($album, ['description' => 'New Description']);
        
        $this->assertEquals('New Description', $updated->description);
    }

    #[Test]
    public function update_album_can_set_description_to_null(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create([
            'description' => 'Some Description',
        ]);
        
        $updated = $this->service->updateAlbum($album, ['description' => null]);
        
        $this->assertNull($updated->description);
    }

    #[Test]
    public function update_album_fails_with_empty_name(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create();
        
        $this->expectException(ValidationException::class);
        
        $this->service->updateAlbum($album, ['name' => '']);
    }

    #[Test]
    public function update_album_updates_cover_media(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create();
        $media = $this->createCompletedMedia($wedding);
        
        $updated = $this->service->updateAlbum($album, ['cover_media_id' => $media->id]);
        
        $this->assertEquals($media->id, $updated->cover_media_id);
    }

    #[Test]
    public function update_album_can_remove_cover_media(): void
    {
        $wedding = $this->createWedding();
        $media = $this->createCompletedMedia($wedding);
        $album = Album::factory()->forWedding($wedding)->preWedding()->create([
            'cover_media_id' => $media->id,
        ]);
        
        $updated = $this->service->updateAlbum($album, ['cover_media_id' => null]);
        
        $this->assertNull($updated->cover_media_id);
    }

    #[Test]
    public function update_album_returns_fresh_model(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create([
            'name' => 'Original',
        ]);
        
        $updated = $this->service->updateAlbum($album, ['name' => 'Updated']);
        
        $this->assertNotSame($album, $updated);
        $this->assertEquals('Updated', $updated->name);
    }

    // ==========================================
    // deleteAlbum Tests
    // ==========================================

    #[Test]
    public function delete_album_deletes_empty_album(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create();
        $albumId = $album->id;
        
        $result = $this->service->deleteAlbum($album);
        
        $this->assertTrue($result);
        $this->assertNull(Album::find($albumId));
    }

    #[Test]
    public function delete_album_fails_when_has_media_and_no_target(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create();
        $this->createCompletedMedia($wedding, $album);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('O álbum contém 1 arquivo(s) de mídia');
        
        $this->service->deleteAlbum($album);
    }

    #[Test]
    public function delete_album_moves_media_to_target_album(): void
    {
        $wedding = $this->createWedding();
        $sourceAlbum = Album::factory()->forWedding($wedding)->preWedding()->create();
        $targetAlbum = Album::factory()->forWedding($wedding)->postWedding()->create();
        
        $media1 = $this->createCompletedMedia($wedding, $sourceAlbum);
        $media2 = $this->createCompletedMedia($wedding, $sourceAlbum);
        
        $result = $this->service->deleteAlbum($sourceAlbum, $targetAlbum);
        
        $this->assertTrue($result);
        $this->assertNull(Album::find($sourceAlbum->id));
        
        // Verify media was moved
        $media1->refresh();
        $media2->refresh();
        $this->assertEquals($targetAlbum->id, $media1->album_id);
        $this->assertEquals($targetAlbum->id, $media2->album_id);
    }

    #[Test]
    public function delete_album_fails_when_target_is_different_wedding(): void
    {
        $wedding1 = $this->createWedding();
        $wedding2 = $this->createWedding();
        
        $sourceAlbum = Album::factory()->forWedding($wedding1)->preWedding()->create();
        $targetAlbum = Album::factory()->forWedding($wedding2)->preWedding()->create();
        
        $this->createCompletedMedia($wedding1, $sourceAlbum);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O álbum de destino deve pertencer ao mesmo casamento');
        
        $this->service->deleteAlbum($sourceAlbum, $targetAlbum);
    }

    #[Test]
    public function delete_album_fails_when_target_is_same_album(): void
    {
        $wedding = $this->createWedding();
        $album = Album::factory()->forWedding($wedding)->preWedding()->create();
        $this->createCompletedMedia($wedding, $album);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O álbum de destino não pode ser o mesmo álbum');
        
        $this->service->deleteAlbum($album, $album);
    }

    #[Test]
    public function delete_album_clears_cover_media_before_deletion(): void
    {
        $wedding = $this->createWedding();
        $media = $this->createCompletedMedia($wedding);
        $album = Album::factory()->forWedding($wedding)->preWedding()->create([
            'cover_media_id' => $media->id,
        ]);
        
        $result = $this->service->deleteAlbum($album);
        
        $this->assertTrue($result);
        // Media should still exist
        $this->assertNotNull(SiteMedia::find($media->id));
    }

    // ==========================================
    // moveMedia Tests
    // ==========================================

    #[Test]
    public function move_media_updates_album_id(): void
    {
        $wedding = $this->createWedding();
        $sourceAlbum = Album::factory()->forWedding($wedding)->preWedding()->create();
        $targetAlbum = Album::factory()->forWedding($wedding)->postWedding()->create();
        
        $media = $this->createCompletedMedia($wedding, $sourceAlbum);
        
        $result = $this->service->moveMedia($media, $targetAlbum);
        
        $this->assertEquals($targetAlbum->id, $result->album_id);
    }

    #[Test]
    public function move_media_preserves_file_path(): void
    {
        $wedding = $this->createWedding();
        $sourceAlbum = Album::factory()->forWedding($wedding)->preWedding()->create();
        $targetAlbum = Album::factory()->forWedding($wedding)->postWedding()->create();
        
        $media = $this->createCompletedMedia($wedding, $sourceAlbum);
        $originalPath = $media->path;
        
        $result = $this->service->moveMedia($media, $targetAlbum);
        
        $this->assertEquals($originalPath, $result->path);
    }

    #[Test]
    public function move_media_fails_when_different_wedding(): void
    {
        $wedding1 = $this->createWedding();
        $wedding2 = $this->createWedding();
        
        $sourceAlbum = Album::factory()->forWedding($wedding1)->preWedding()->create();
        $targetAlbum = Album::factory()->forWedding($wedding2)->preWedding()->create();
        
        $media = $this->createCompletedMedia($wedding1, $sourceAlbum);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A mídia e o álbum de destino devem pertencer ao mesmo casamento');
        
        $this->service->moveMedia($media, $targetAlbum);
    }

    #[Test]
    public function move_media_returns_refreshed_model(): void
    {
        $wedding = $this->createWedding();
        $sourceAlbum = Album::factory()->forWedding($wedding)->preWedding()->create();
        $targetAlbum = Album::factory()->forWedding($wedding)->postWedding()->create();
        
        $media = $this->createCompletedMedia($wedding, $sourceAlbum);
        
        $result = $this->service->moveMedia($media, $targetAlbum);
        
        $this->assertEquals($targetAlbum->id, $result->album_id);
        $this->assertInstanceOf(SiteMedia::class, $result);
    }

    // ==========================================
    // getAlbumsByType Tests
    // ==========================================

    #[Test]
    public function get_albums_by_type_returns_collection(): void
    {
        $wedding = $this->createWedding();
        
        $result = $this->service->getAlbumsByType($wedding);
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    #[Test]
    public function get_albums_by_type_returns_all_type_keys(): void
    {
        $wedding = $this->createWedding();
        
        $result = $this->service->getAlbumsByType($wedding);
        
        $this->assertTrue($result->has(AlbumType::PRE_WEDDING));
        $this->assertTrue($result->has(AlbumType::POST_WEDDING));
        $this->assertTrue($result->has(AlbumType::SITE_USAGE));
    }

    #[Test]
    public function get_albums_by_type_returns_empty_collections_when_no_albums(): void
    {
        $wedding = $this->createWedding();
        
        $result = $this->service->getAlbumsByType($wedding);
        
        foreach (AlbumType::getSlugs() as $typeSlug) {
            $this->assertCount(0, $result[$typeSlug]);
        }
    }

    #[Test]
    public function get_albums_by_type_groups_albums_correctly(): void
    {
        $wedding = $this->createWedding();
        
        // Create albums of different types
        Album::factory()->forWedding($wedding)->preWedding()->count(2)->create();
        Album::factory()->forWedding($wedding)->postWedding()->count(3)->create();
        Album::factory()->forWedding($wedding)->siteUsage()->count(1)->create();
        
        $result = $this->service->getAlbumsByType($wedding);
        
        $this->assertCount(2, $result[AlbumType::PRE_WEDDING]);
        $this->assertCount(3, $result[AlbumType::POST_WEDDING]);
        $this->assertCount(1, $result[AlbumType::SITE_USAGE]);
    }

    #[Test]
    public function get_albums_by_type_only_returns_albums_for_specified_wedding(): void
    {
        $wedding1 = $this->createWedding();
        $wedding2 = $this->createWedding();
        
        // Create albums for both weddings
        Album::factory()->forWedding($wedding1)->preWedding()->count(2)->create();
        Album::factory()->forWedding($wedding2)->preWedding()->count(5)->create();
        
        $result = $this->service->getAlbumsByType($wedding1);
        
        $this->assertCount(2, $result[AlbumType::PRE_WEDDING]);
    }

    #[Test]
    public function get_albums_by_type_each_album_has_correct_type(): void
    {
        $wedding = $this->createWedding();
        
        Album::factory()->forWedding($wedding)->preWedding()->count(3)->create();
        Album::factory()->forWedding($wedding)->postWedding()->count(2)->create();
        
        $result = $this->service->getAlbumsByType($wedding);
        
        // Verify all albums in pre_casamento group have correct type
        foreach ($result[AlbumType::PRE_WEDDING] as $album) {
            $this->assertEquals(AlbumType::PRE_WEDDING, $album->albumType->slug);
        }
        
        // Verify all albums in pos_casamento group have correct type
        foreach ($result[AlbumType::POST_WEDDING] as $album) {
            $this->assertEquals(AlbumType::POST_WEDDING, $album->albumType->slug);
        }
    }

    // ==========================================
    // Three Album Types Exist Test
    // ==========================================

    #[Test]
    public function three_album_types_exist(): void
    {
        $types = AlbumType::all();
        
        $this->assertCount(3, $types);
        $this->assertTrue($types->contains('slug', AlbumType::PRE_WEDDING));
        $this->assertTrue($types->contains('slug', AlbumType::POST_WEDDING));
        $this->assertTrue($types->contains('slug', AlbumType::SITE_USAGE));
    }
}
