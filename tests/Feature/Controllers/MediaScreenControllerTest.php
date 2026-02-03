<?php

namespace Tests\Feature\Controllers;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for MediaScreenController.
 * 
 * Tests the media screen page rendering with albums and media.
 */
class MediaScreenControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wedding $wedding;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create album types
        $this->createAlbumTypes();
        
        // Create user and wedding
        $this->wedding = Wedding::factory()->create();
        $this->user = User::factory()->create([
            'current_wedding_id' => $this->wedding->id,
        ]);
        
        // Attach user to wedding
        $this->user->weddings()->attach($this->wedding->id, ['role' => 'couple']);
        
        // Create site layout for wedding
        SiteLayout::factory()->create(['wedding_id' => $this->wedding->id]);
    }

    private function createAlbumTypes(): void
    {
        AlbumType::firstOrCreate(
            ['slug' => 'pre_casamento'],
            ['name' => 'Pré Casamento', 'description' => 'Fotos do pré-casamento']
        );
        
        AlbumType::firstOrCreate(
            ['slug' => 'pos_casamento'],
            ['name' => 'Pós Casamento', 'description' => 'Fotos do pós-casamento']
        );
        
        AlbumType::firstOrCreate(
            ['slug' => 'uso_site'],
            ['name' => 'Uso do Site', 'description' => 'Imagens para o site']
        );
    }

    /** @test */
    public function it_renders_media_screen_with_empty_albums()
    {
        session(['filament_wedding_id' => $this->wedding->id]);
        
        $response = $this->actingAs($this->user)
            ->get(route('midias.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('MediaScreen')
                ->has('albums')
                ->where('albums', [])
        );
    }

    /** @test */
    public function it_loads_albums_with_media_count()
    {
        session(['filament_wedding_id' => $this->wedding->id]);
        
        $albumType = AlbumType::where('slug', 'uso_site')->first();
        
        $album = Album::create([
            'wedding_id' => $this->wedding->id,
            'album_type_id' => $albumType->id,
            'name' => 'Test Album',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('midias.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('MediaScreen')
                ->has('albums', 1)
                ->where('albums.0.id', $album->id)
                ->where('albums.0.name', 'Test Album')
                ->where('albums.0.media_count', 0)
        );
    }

    /** @test */
    public function it_loads_albums_with_completed_media()
    {
        session(['filament_wedding_id' => $this->wedding->id]);
        
        $albumType = AlbumType::where('slug', 'uso_site')->first();
        
        $album = Album::create([
            'wedding_id' => $this->wedding->id,
            'album_type_id' => $albumType->id,
            'name' => 'Test Album',
        ]);

        // Ensure site layout exists (should be created in setUp, but let's be explicit)
        $siteLayout = SiteLayout::where('wedding_id', $this->wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $this->wedding->id]);
        }

        // Create completed media
        SiteMedia::create([
            'wedding_id' => $this->wedding->id,
            'site_layout_id' => $siteLayout->id,
            'album_id' => $album->id,
            'original_name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'disk' => 'public',
            'size' => 1000,
            'mime_type' => 'image/jpeg',
            'status' => 'completed',
        ]);

        // Create pending media (should not be included)
        SiteMedia::create([
            'wedding_id' => $this->wedding->id,
            'site_layout_id' => $siteLayout->id,
            'album_id' => $album->id,
            'original_name' => 'pending.jpg',
            'path' => 'media/pending.jpg',
            'disk' => 'public',
            'size' => 1000,
            'mime_type' => 'image/jpeg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('midias.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('MediaScreen')
                ->has('albums', 1)
                ->where('albums.0.media_count', 2) // withCount includes all media
                ->has('albums.0.media', 1) // but only completed media in the collection
                ->where('albums.0.media.0.filename', 'test.jpg')
        );
    }
}
