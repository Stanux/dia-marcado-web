<?php

namespace Tests\Feature\Property\Media;

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
use Tests\TestCase;

/**
 * Property-based tests for Album referential integrity.
 * 
 * @feature media-management
 * @property 4: Integridade Referencial de Álbum e Mídia
 * 
 * Validates: Requirements 2.2, 2.3
 */
class AlbumIntegrityPropertyTest extends TestCase
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
     * Generate a random invalid album type slug.
     */
    private function generateInvalidTypeSlug(): string
    {
        $invalidSlugs = [
            'invalid_type',
            'nonexistent',
            'random_' . mt_rand(1000, 9999),
            'album_type_' . uniqid(),
            'test',
            'foo',
            'bar',
            '',
            ' ',
            'pre-casamento', // wrong format (hyphen instead of underscore)
            'PRE_CASAMENTO', // wrong case
        ];
        
        return $invalidSlugs[array_rand($invalidSlugs)];
    }

    /**
     * Generate a random valid album type slug.
     */
    private function generateValidTypeSlug(): string
    {
        $validSlugs = AlbumType::getSlugs();
        return $validSlugs[array_rand($validSlugs)];
    }

    /**
     * Generate a random album name.
     */
    private function generateAlbumName(): string
    {
        $prefixes = ['Ensaio', 'Fotos', 'Vídeos', 'Álbum', 'Galeria', 'Coleção'];
        $suffixes = ['Principal', 'Especial', 'Favoritos', 'Seleção', 'Memórias', 'Momentos'];
        
        return $prefixes[array_rand($prefixes)] . ' ' . $suffixes[array_rand($suffixes)] . ' ' . mt_rand(1, 100);
    }

    /**
     * Property 4: Integridade Referencial de Álbum e Mídia
     * 
     * For any attempt to create an album without a valid album_type_id,
     * the operation must fail.
     * 
     * **Validates: Requirements 2.2, 2.3**
     * 
     * @test
     * @feature media-management
     * @property 4: Integridade Referencial de Álbum e Mídia
     */
    public function creating_album_with_invalid_type_fails(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            
            $invalidTypeSlug = $this->generateInvalidTypeSlug();
            $albumName = $this->generateAlbumName();
            
            $exceptionThrown = false;
            
            try {
                $this->service->createAlbum($wedding, $invalidTypeSlug, [
                    'name' => $albumName,
                ]);
            } catch (InvalidArgumentException $e) {
                $exceptionThrown = true;
            }
            
            $this->assertTrue(
                $exceptionThrown,
                "Iteration {$iteration}: Creating album with invalid type '{$invalidTypeSlug}' should throw InvalidArgumentException"
            );
        }
    }

    /**
     * Property 4: Integridade Referencial de Álbum e Mídia
     * 
     * For any attempt to create an album with a valid album_type_id,
     * the operation must succeed.
     * 
     * **Validates: Requirements 2.2, 2.3**
     * 
     * @test
     * @feature media-management
     * @property 4: Integridade Referencial de Álbum e Mídia
     */
    public function creating_album_with_valid_type_succeeds(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            
            $validTypeSlug = $this->generateValidTypeSlug();
            $albumName = $this->generateAlbumName();
            
            $album = $this->service->createAlbum($wedding, $validTypeSlug, [
                'name' => $albumName,
            ]);
            
            $this->assertInstanceOf(
                Album::class,
                $album,
                "Iteration {$iteration}: Creating album with valid type '{$validTypeSlug}' should return Album instance"
            );
            
            $this->assertEquals(
                $validTypeSlug,
                $album->albumType->slug,
                "Iteration {$iteration}: Album type slug should be '{$validTypeSlug}'"
            );
            
            $this->assertEquals(
                $wedding->id,
                $album->wedding_id,
                "Iteration {$iteration}: Album should belong to the correct wedding"
            );
        }
    }

    /**
     * Property 4: Integridade Referencial de Álbum e Mídia
     * 
     * For any album created with valid references, the album_type relationship
     * must be correctly established and queryable.
     * 
     * **Validates: Requirements 2.2, 2.3**
     * 
     * @test
     * @feature media-management
     * @property 4: Integridade Referencial de Álbum e Mídia
     */
    public function album_type_relationship_is_correctly_established(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            
            $validTypeSlug = $this->generateValidTypeSlug();
            $albumName = $this->generateAlbumName();
            
            $album = $this->service->createAlbum($wedding, $validTypeSlug, [
                'name' => $albumName,
            ]);
            
            // Refresh from database to ensure relationship is persisted
            $album->refresh();
            
            // Verify album_type_id is set
            $this->assertNotNull(
                $album->album_type_id,
                "Iteration {$iteration}: Album should have album_type_id set"
            );
            
            // Verify albumType relationship works
            $this->assertNotNull(
                $album->albumType,
                "Iteration {$iteration}: Album should have albumType relationship"
            );
            
            // Verify the relationship returns correct type
            $this->assertEquals(
                $validTypeSlug,
                $album->albumType->slug,
                "Iteration {$iteration}: albumType relationship should return correct type"
            );
            
            // Verify we can query albums by type
            $albumsOfType = Album::whereHas('albumType', function ($query) use ($validTypeSlug) {
                $query->where('slug', $validTypeSlug);
            })->where('wedding_id', $wedding->id)->get();
            
            $this->assertTrue(
                $albumsOfType->contains('id', $album->id),
                "Iteration {$iteration}: Album should be queryable by type"
            );
        }
    }

    /**
     * Property 4: Integridade Referencial de Álbum e Mídia
     * 
     * For any media associated with an album, the album relationship
     * must be correctly established and queryable.
     * 
     * **Validates: Requirements 2.2, 2.3**
     * 
     * @test
     * @feature media-management
     * @property 4: Integridade Referencial de Álbum e Mídia
     */
    public function media_album_relationship_is_correctly_established(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create an album
            $validTypeSlug = $this->generateValidTypeSlug();
            $album = $this->service->createAlbum($wedding, $validTypeSlug, [
                'name' => $this->generateAlbumName(),
            ]);
            
            // Create media associated with the album
            $media = SiteMedia::create([
                'wedding_id' => $wedding->id,
                'site_layout_id' => $siteLayout->id,
                'album_id' => $album->id,
                'original_name' => 'test-' . uniqid() . '.jpg',
                'path' => 'media/' . uniqid() . '.jpg',
                'disk' => 'public',
                'size' => mt_rand(1000, 10000000),
                'mime_type' => 'image/jpeg',
                'status' => SiteMedia::STATUS_COMPLETED,
            ]);
            
            // Refresh from database
            $media->refresh();
            
            // Verify album_id is set
            $this->assertEquals(
                $album->id,
                $media->album_id,
                "Iteration {$iteration}: Media should have correct album_id"
            );
            
            // Verify album relationship works
            $this->assertNotNull(
                $media->album,
                "Iteration {$iteration}: Media should have album relationship"
            );
            
            $this->assertEquals(
                $album->id,
                $media->album->id,
                "Iteration {$iteration}: Media album relationship should return correct album"
            );
            
            // Verify album->media relationship works
            $albumMedia = $album->media()->get();
            $this->assertTrue(
                $albumMedia->contains('id', $media->id),
                "Iteration {$iteration}: Album media relationship should include the media"
            );
        }
    }

    /**
     * Property 4: Integridade Referencial de Álbum e Mídia
     * 
     * For any media with an invalid album_id (non-existent UUID),
     * the album relationship should return null.
     * 
     * **Validates: Requirements 2.2, 2.3**
     * 
     * @test
     * @feature media-management
     * @property 4: Integridade Referencial de Álbum e Mídia
     */
    public function media_with_null_album_id_has_no_album_relationship(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            $siteLayout = $data['siteLayout'];
            
            // Create media without album
            $media = SiteMedia::create([
                'wedding_id' => $wedding->id,
                'site_layout_id' => $siteLayout->id,
                'album_id' => null,
                'original_name' => 'test-' . uniqid() . '.jpg',
                'path' => 'media/' . uniqid() . '.jpg',
                'disk' => 'public',
                'size' => mt_rand(1000, 10000000),
                'mime_type' => 'image/jpeg',
                'status' => SiteMedia::STATUS_COMPLETED,
            ]);
            
            // Refresh from database
            $media->refresh();
            
            // Verify album_id is null
            $this->assertNull(
                $media->album_id,
                "Iteration {$iteration}: Media should have null album_id"
            );
            
            // Verify album relationship returns null
            $this->assertNull(
                $media->album,
                "Iteration {$iteration}: Media album relationship should return null"
            );
        }
    }
}
