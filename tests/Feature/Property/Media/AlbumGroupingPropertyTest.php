<?php

namespace Tests\Feature\Property\Media;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteLayout;
use App\Models\Wedding;
use App\Services\Media\AlbumManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for Album grouping by type.
 * 
 * @feature media-management
 * @property 5: Agrupamento de Álbuns por Tipo
 * 
 * Validates: Requirements 2.4
 */
class AlbumGroupingPropertyTest extends TestCase
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
     * Generate random album distribution across types.
     * 
     * @return array<string, int> Map of type slug to count
     */
    private function generateRandomAlbumDistribution(): array
    {
        return [
            AlbumType::PRE_WEDDING => mt_rand(0, 5),
            AlbumType::POST_WEDDING => mt_rand(0, 5),
            AlbumType::SITE_USAGE => mt_rand(0, 5),
        ];
    }

    /**
     * Property 5: Agrupamento de Álbuns por Tipo
     * 
     * For any wedding with albums of multiple types, the getAlbumsByType function
     * must return a collection where all albums are grouped by their album_type_id,
     * and each group contains only albums of that type.
     * 
     * **Validates: Requirements 2.4**
     * 
     * @test
     * @feature media-management
     * @property 5: Agrupamento de Álbuns por Tipo
     */
    public function albums_are_grouped_by_type(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            
            // Generate random distribution of albums
            $distribution = $this->generateRandomAlbumDistribution();
            
            // Create albums according to distribution
            foreach ($distribution as $typeSlug => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $this->service->createAlbum($wedding, $typeSlug, [
                        'name' => $this->generateAlbumName(),
                    ]);
                }
            }
            
            // Get albums grouped by type
            $grouped = $this->service->getAlbumsByType($wedding);
            
            // Verify result is a Collection
            $this->assertInstanceOf(
                Collection::class,
                $grouped,
                "Iteration {$iteration}: Result should be a Collection"
            );
            
            // Verify all type keys are present
            foreach (AlbumType::getSlugs() as $typeSlug) {
                $this->assertTrue(
                    $grouped->has($typeSlug),
                    "Iteration {$iteration}: Result should have key '{$typeSlug}'"
                );
            }
            
            // Verify each group contains only albums of that type
            foreach ($grouped as $typeSlug => $albums) {
                foreach ($albums as $album) {
                    $this->assertEquals(
                        $typeSlug,
                        $album->albumType->slug,
                        "Iteration {$iteration}: Album in group '{$typeSlug}' should have type '{$typeSlug}' but has '{$album->albumType->slug}'"
                    );
                }
            }
            
            // Verify counts match distribution
            foreach ($distribution as $typeSlug => $expectedCount) {
                $actualCount = $grouped[$typeSlug]->count();
                $this->assertEquals(
                    $expectedCount,
                    $actualCount,
                    "Iteration {$iteration}: Group '{$typeSlug}' should have {$expectedCount} albums but has {$actualCount}"
                );
            }
        }
    }

    /**
     * Property 5: Agrupamento de Álbuns por Tipo
     * 
     * For any wedding with no albums, getAlbumsByType must return a collection
     * with all type keys present but with empty collections as values.
     * 
     * **Validates: Requirements 2.4**
     * 
     * @test
     * @feature media-management
     * @property 5: Agrupamento de Álbuns por Tipo
     */
    public function empty_wedding_returns_all_type_keys_with_empty_collections(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            
            // Get albums grouped by type (no albums created)
            $grouped = $this->service->getAlbumsByType($wedding);
            
            // Verify all type keys are present
            $allTypes = AlbumType::getSlugs();
            foreach ($allTypes as $typeSlug) {
                $this->assertTrue(
                    $grouped->has($typeSlug),
                    "Iteration {$iteration}: Result should have key '{$typeSlug}' even with no albums"
                );
                
                $this->assertCount(
                    0,
                    $grouped[$typeSlug],
                    "Iteration {$iteration}: Group '{$typeSlug}' should be empty"
                );
            }
        }
    }

    /**
     * Property 5: Agrupamento de Álbuns por Tipo
     * 
     * For any wedding, the total count of albums across all groups must equal
     * the total count of albums for that wedding.
     * 
     * **Validates: Requirements 2.4**
     * 
     * @test
     * @feature media-management
     * @property 5: Agrupamento de Álbuns por Tipo
     */
    public function total_albums_across_groups_equals_total_albums(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            
            // Generate random distribution of albums
            $distribution = $this->generateRandomAlbumDistribution();
            $expectedTotal = array_sum($distribution);
            
            // Create albums according to distribution
            foreach ($distribution as $typeSlug => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $this->service->createAlbum($wedding, $typeSlug, [
                        'name' => $this->generateAlbumName(),
                    ]);
                }
            }
            
            // Get albums grouped by type
            $grouped = $this->service->getAlbumsByType($wedding);
            
            // Calculate total across all groups
            $actualTotal = 0;
            foreach ($grouped as $albums) {
                $actualTotal += $albums->count();
            }
            
            // Verify total matches
            $this->assertEquals(
                $expectedTotal,
                $actualTotal,
                "Iteration {$iteration}: Total albums across groups ({$actualTotal}) should equal expected total ({$expectedTotal})"
            );
            
            // Also verify against database count
            $dbCount = Album::where('wedding_id', $wedding->id)->count();
            $this->assertEquals(
                $dbCount,
                $actualTotal,
                "Iteration {$iteration}: Total albums across groups ({$actualTotal}) should equal database count ({$dbCount})"
            );
        }
    }

    /**
     * Property 5: Agrupamento de Álbuns por Tipo
     * 
     * For any two weddings, getAlbumsByType for one wedding must not include
     * albums from the other wedding.
     * 
     * **Validates: Requirements 2.4**
     * 
     * @test
     * @feature media-management
     * @property 5: Agrupamento de Álbuns por Tipo
     */
    public function grouping_is_isolated_per_wedding(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Create two weddings
            $data1 = $this->createWeddingWithLayout();
            $wedding1 = $data1['wedding'];
            
            $data2 = $this->createWeddingWithLayout();
            $wedding2 = $data2['wedding'];
            
            // Generate different distributions for each wedding
            $distribution1 = $this->generateRandomAlbumDistribution();
            $distribution2 = $this->generateRandomAlbumDistribution();
            
            // Create albums for wedding 1
            foreach ($distribution1 as $typeSlug => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $this->service->createAlbum($wedding1, $typeSlug, [
                        'name' => 'W1-' . $this->generateAlbumName(),
                    ]);
                }
            }
            
            // Create albums for wedding 2
            foreach ($distribution2 as $typeSlug => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $this->service->createAlbum($wedding2, $typeSlug, [
                        'name' => 'W2-' . $this->generateAlbumName(),
                    ]);
                }
            }
            
            // Get grouped albums for each wedding
            $grouped1 = $this->service->getAlbumsByType($wedding1);
            $grouped2 = $this->service->getAlbumsByType($wedding2);
            
            // Verify wedding 1 albums only belong to wedding 1
            foreach ($grouped1 as $typeSlug => $albums) {
                foreach ($albums as $album) {
                    $this->assertEquals(
                        $wedding1->id,
                        $album->wedding_id,
                        "Iteration {$iteration}: Album in wedding 1 group should belong to wedding 1"
                    );
                }
                
                $this->assertEquals(
                    $distribution1[$typeSlug],
                    $albums->count(),
                    "Iteration {$iteration}: Wedding 1 group '{$typeSlug}' should have {$distribution1[$typeSlug]} albums"
                );
            }
            
            // Verify wedding 2 albums only belong to wedding 2
            foreach ($grouped2 as $typeSlug => $albums) {
                foreach ($albums as $album) {
                    $this->assertEquals(
                        $wedding2->id,
                        $album->wedding_id,
                        "Iteration {$iteration}: Album in wedding 2 group should belong to wedding 2"
                    );
                }
                
                $this->assertEquals(
                    $distribution2[$typeSlug],
                    $albums->count(),
                    "Iteration {$iteration}: Wedding 2 group '{$typeSlug}' should have {$distribution2[$typeSlug]} albums"
                );
            }
        }
    }

    /**
     * Property 5: Agrupamento de Álbuns por Tipo
     * 
     * For any album in a group, the album's albumType relationship must be loaded
     * and accessible without additional queries.
     * 
     * **Validates: Requirements 2.4**
     * 
     * @test
     * @feature media-management
     * @property 5: Agrupamento de Álbuns por Tipo
     */
    public function grouped_albums_have_album_type_relationship_loaded(): void
    {
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $data = $this->createWeddingWithLayout();
            $wedding = $data['wedding'];
            
            // Create at least one album of each type
            foreach (AlbumType::getSlugs() as $typeSlug) {
                $this->service->createAlbum($wedding, $typeSlug, [
                    'name' => $this->generateAlbumName(),
                ]);
            }
            
            // Get albums grouped by type
            $grouped = $this->service->getAlbumsByType($wedding);
            
            // Verify albumType relationship is loaded for each album
            foreach ($grouped as $typeSlug => $albums) {
                foreach ($albums as $album) {
                    // Check that albumType is loaded (not null and is AlbumType instance)
                    $this->assertNotNull(
                        $album->albumType,
                        "Iteration {$iteration}: Album should have albumType relationship loaded"
                    );
                    
                    $this->assertInstanceOf(
                        AlbumType::class,
                        $album->albumType,
                        "Iteration {$iteration}: albumType should be an AlbumType instance"
                    );
                    
                    // Verify the type matches the group key
                    $this->assertEquals(
                        $typeSlug,
                        $album->albumType->slug,
                        "Iteration {$iteration}: Album albumType slug should match group key"
                    );
                }
            }
        }
    }
}
