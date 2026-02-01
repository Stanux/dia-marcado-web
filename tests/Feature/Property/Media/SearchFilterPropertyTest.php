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
 * Property-based tests for search and filtering.
 * 
 * **Validates: Requirements 8.6, 6.2**
 * 
 * @feature media-management
 * @property 21: Busca Filtra Corretamente
 * @property 14: Filtragem de Mídia por Álbum e Tipo
 */
class SearchFilterPropertyTest extends TestCase
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
     * @property 21: Busca Filtra Corretamente
     * 
     * For any search query with name filter, all results must contain
     * the search term in their original_name.
     */
    public function search_by_name_returns_matching_results(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $wedding = Wedding::factory()->create();
            $albumType = AlbumType::first();
            $album = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
            ]);

            // Create media with various names
            $searchTerm = 'wedding';
            $matchingNames = ['wedding_photo.jpg', 'my_wedding.png', 'WEDDING_day.gif'];
            $nonMatchingNames = ['birthday.jpg', 'vacation.png', 'party.gif'];

            foreach ($matchingNames as $name) {
                $this->createMedia($wedding, $album, $name);
            }
            foreach ($nonMatchingNames as $name) {
                $this->createMedia($wedding, $album, $name);
            }

            // Query with search filter
            $results = SiteMedia::where('wedding_id', $wedding->id)
                ->where('status', 'completed')
                ->where('original_name', 'ilike', '%' . $searchTerm . '%')
                ->get();

            // Property assertion: all results must contain search term
            foreach ($results as $media) {
                $this->assertStringContainsStringIgnoringCase(
                    $searchTerm,
                    $media->original_name,
                    "Property 21 violated: Result '{$media->original_name}' does not contain search term '{$searchTerm}' (iteration {$i})"
                );
            }

            // Should find exactly the matching names
            $this->assertEquals(
                count($matchingNames),
                $results->count(),
                "Property 21 violated: Expected " . count($matchingNames) . " results, got {$results->count()} (iteration {$i})"
            );
        }
    }

    /**
     * @test
     * @feature media-management
     * @property 14: Filtragem de Mídia por Álbum e Tipo
     * 
     * For any query with album_id filter, all results must belong
     * to the specified album.
     */
    public function filter_by_album_returns_only_album_media(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $wedding = Wedding::factory()->create();
            $albumTypes = AlbumType::all();
            
            // Create multiple albums
            $albums = [];
            foreach ($albumTypes as $type) {
                $albums[$type->slug] = Album::factory()->create([
                    'wedding_id' => $wedding->id,
                    'album_type_id' => $type->id,
                ]);
            }

            // Create media in each album
            $mediaPerAlbum = rand(2, 5);
            foreach ($albums as $slug => $album) {
                for ($m = 0; $m < $mediaPerAlbum; $m++) {
                    $this->createMedia($wedding, $album, "{$slug}_{$m}.jpg");
                }
            }

            // Pick a random album to filter by
            $targetAlbum = $albums[array_rand($albums)];

            // Query with album filter
            $results = SiteMedia::where('wedding_id', $wedding->id)
                ->where('status', 'completed')
                ->where('album_id', $targetAlbum->id)
                ->get();

            // Property assertion: all results must belong to target album
            foreach ($results as $media) {
                $this->assertEquals(
                    $targetAlbum->id,
                    $media->album_id,
                    "Property 14 violated: Media belongs to album {$media->album_id}, expected {$targetAlbum->id} (iteration {$i})"
                );
            }

            // Should find exactly the media in target album
            $this->assertEquals(
                $mediaPerAlbum,
                $results->count(),
                "Property 14 violated: Expected {$mediaPerAlbum} results, got {$results->count()} (iteration {$i})"
            );
        }
    }

    /**
     * @test
     * @feature media-management
     * @property 14: Filtragem de Mídia por Álbum e Tipo
     * 
     * For any query with album_type filter, all results must belong
     * to albums of the specified type.
     */
    public function filter_by_album_type_returns_only_type_media(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $wedding = Wedding::factory()->create();
            $albumTypes = AlbumType::all();
            
            // Create albums of each type
            $albums = [];
            foreach ($albumTypes as $type) {
                $albums[$type->slug] = Album::factory()->create([
                    'wedding_id' => $wedding->id,
                    'album_type_id' => $type->id,
                ]);
            }

            // Create media in each album
            $mediaPerAlbum = rand(2, 5);
            foreach ($albums as $slug => $album) {
                for ($m = 0; $m < $mediaPerAlbum; $m++) {
                    $this->createMedia($wedding, $album, "{$slug}_{$m}.jpg");
                }
            }

            // Pick a random album type to filter by
            $targetType = $albumTypes->random();

            // Query with album type filter
            $results = SiteMedia::where('wedding_id', $wedding->id)
                ->where('status', 'completed')
                ->whereHas('album', function ($q) use ($targetType) {
                    $q->where('album_type_id', $targetType->id);
                })
                ->get();

            // Property assertion: all results must belong to albums of target type
            foreach ($results as $media) {
                $this->assertEquals(
                    $targetType->id,
                    $media->album->album_type_id,
                    "Property 14 violated: Media album type is {$media->album->album_type_id}, expected {$targetType->id} (iteration {$i})"
                );
            }

            // Should find exactly the media in albums of target type
            $this->assertEquals(
                $mediaPerAlbum,
                $results->count(),
                "Property 14 violated: Expected {$mediaPerAlbum} results, got {$results->count()} (iteration {$i})"
            );
        }
    }

    /**
     * @test
     * @feature media-management
     * @property 21: Busca Filtra Corretamente
     * 
     * For any query with mime_type filter, all results must have
     * the specified mime type prefix.
     */
    public function filter_by_mime_type_returns_matching_results(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $wedding = Wedding::factory()->create();
            $albumType = AlbumType::first();
            $album = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumType->id,
            ]);

            // Create media with various mime types
            $imageFiles = ['photo1.jpg', 'photo2.png', 'photo3.gif'];
            $videoFiles = ['video1.mp4', 'video2.webm'];

            foreach ($imageFiles as $name) {
                $this->createMedia($wedding, $album, $name, 'image/jpeg');
            }
            foreach ($videoFiles as $name) {
                $this->createMedia($wedding, $album, $name, 'video/mp4');
            }

            // Query with mime type filter for images
            $results = SiteMedia::where('wedding_id', $wedding->id)
                ->where('status', 'completed')
                ->where('mime_type', 'like', 'image%')
                ->get();

            // Property assertion: all results must have image mime type
            foreach ($results as $media) {
                $this->assertStringStartsWith(
                    'image',
                    $media->mime_type,
                    "Property 21 violated: Mime type '{$media->mime_type}' does not start with 'image' (iteration {$i})"
                );
            }

            // Should find exactly the image files
            $this->assertEquals(
                count($imageFiles),
                $results->count(),
                "Property 21 violated: Expected " . count($imageFiles) . " image results, got {$results->count()} (iteration {$i})"
            );
        }
    }

    /**
     * @test
     * @feature media-management
     * @property 21: Busca Filtra Corretamente
     * 
     * For any query with multiple filters, all results must satisfy
     * ALL filters simultaneously.
     */
    public function combined_filters_return_intersection(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $wedding = Wedding::factory()->create();
            $albumTypes = AlbumType::all();
            
            // Create two albums
            $album1 = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumTypes->first()->id,
            ]);
            $album2 = Album::factory()->create([
                'wedding_id' => $wedding->id,
                'album_type_id' => $albumTypes->last()->id,
            ]);

            // Create media with specific patterns
            // Album 1: wedding photos (images)
            $this->createMedia($wedding, $album1, 'wedding_photo1.jpg', 'image/jpeg');
            $this->createMedia($wedding, $album1, 'wedding_photo2.jpg', 'image/jpeg');
            $this->createMedia($wedding, $album1, 'party_photo.jpg', 'image/jpeg');
            
            // Album 2: wedding videos
            $this->createMedia($wedding, $album2, 'wedding_video.mp4', 'video/mp4');
            $this->createMedia($wedding, $album2, 'wedding_clip.mp4', 'video/mp4');

            // Query with combined filters: album1 + name contains "wedding"
            $results = SiteMedia::where('wedding_id', $wedding->id)
                ->where('status', 'completed')
                ->where('album_id', $album1->id)
                ->where('original_name', 'ilike', '%wedding%')
                ->get();

            // Property assertion: all results must satisfy BOTH filters
            foreach ($results as $media) {
                $this->assertEquals(
                    $album1->id,
                    $media->album_id,
                    "Property 21 violated: Media not in expected album (iteration {$i})"
                );
                $this->assertStringContainsStringIgnoringCase(
                    'wedding',
                    $media->original_name,
                    "Property 21 violated: Media name does not contain 'wedding' (iteration {$i})"
                );
            }

            // Should find exactly 2 results (wedding_photo1 and wedding_photo2)
            $this->assertEquals(
                2,
                $results->count(),
                "Property 21 violated: Expected 2 results for combined filter, got {$results->count()} (iteration {$i})"
            );
        }
    }

    /**
     * Helper to create a media record.
     */
    private function createMedia(Wedding $wedding, Album $album, string $name, string $mimeType = 'image/jpeg'): SiteMedia
    {
        $uuid = Str::uuid()->toString();
        $extension = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
        $path = "media/{$wedding->id}/{$uuid}.{$extension}";
        
        Storage::disk('public')->put($path, 'test content');
        
        return SiteMedia::create([
            'wedding_id' => $wedding->id,
            'album_id' => $album->id,
            'original_name' => $name,
            'path' => $path,
            'disk' => 'public',
            'size' => rand(1000, 1000000),
            'mime_type' => $mimeType,
            'status' => 'completed',
        ]);
    }
}
