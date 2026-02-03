<?php

namespace Tests\Feature\Controllers;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for MediaController.
 * 
 * Tests media upload and deletion operations.
 */
class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wedding $wedding;
    private Album $album;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
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
        
        // Create album
        $albumType = AlbumType::where('slug', 'uso_site')->first();
        $this->album = Album::create([
            'wedding_id' => $this->wedding->id,
            'album_type_id' => $albumType->id,
            'name' => 'Test Album',
        ]);
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
    public function it_uploads_image_file()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(1000);

        $response = $this->actingAs($this->user)
            ->postJson(route('media.upload'), [
                'file' => $file,
                'album_id' => $this->album->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'album_id',
            'filename',
            'type',
            'mime_type',
            'size',
            'url',
            'thumbnail_url',
            'created_at',
            'updated_at',
        ]);

        $this->assertDatabaseHas('site_media', [
            'wedding_id' => $this->wedding->id,
            'album_id' => $this->album->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_validates_required_file()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('media.upload'), [
                'album_id' => $this->album->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_required_album_id()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->postJson(route('media.upload'), [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['album_id']);
    }

    /** @test */
    public function it_validates_album_belongs_to_wedding()
    {
        $otherWedding = Wedding::factory()->create();
        $albumType = AlbumType::where('slug', 'uso_site')->first();
        $otherAlbum = Album::create([
            'wedding_id' => $otherWedding->id,
            'album_type_id' => $albumType->id,
            'name' => 'Other Album',
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->postJson(route('media.upload'), [
                'file' => $file,
                'album_id' => $otherAlbum->id,
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_deletes_media_file()
    {
        $siteLayout = SiteLayout::where('wedding_id', $this->wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $this->wedding->id]);
        }
        
        $media = SiteMedia::create([
            'wedding_id' => $this->wedding->id,
            'site_layout_id' => $siteLayout->id,
            'album_id' => $this->album->id,
            'original_name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'disk' => 'public',
            'size' => 1000,
            'mime_type' => 'image/jpeg',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('media.destroy', ['id' => $media->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Mídia excluída com sucesso.',
        ]);

        $this->assertDatabaseMissing('site_media', [
            'id' => $media->id,
        ]);
    }

    /** @test */
    public function it_validates_media_belongs_to_wedding_on_delete()
    {
        $otherWedding = Wedding::factory()->create();
        $otherSiteLayout = SiteLayout::factory()->create(['wedding_id' => $otherWedding->id]);
        
        $media = SiteMedia::create([
            'wedding_id' => $otherWedding->id,
            'site_layout_id' => $otherSiteLayout->id,
            'original_name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'disk' => 'public',
            'size' => 1000,
            'mime_type' => 'image/jpeg',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('media.destroy', ['id' => $media->id]));

        $response->assertStatus(404);
        
        // Media should still exist
        $this->assertDatabaseHas('site_media', [
            'id' => $media->id,
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_upload()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('media.upload'), [
            'file' => $file,
            'album_id' => $this->album->id,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_delete()
    {
        $siteLayout = SiteLayout::where('wedding_id', $this->wedding->id)->first();
        if (!$siteLayout) {
            $siteLayout = SiteLayout::factory()->create(['wedding_id' => $this->wedding->id]);
        }
        
        $media = SiteMedia::create([
            'wedding_id' => $this->wedding->id,
            'site_layout_id' => $siteLayout->id,
            'album_id' => $this->album->id,
            'original_name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'disk' => 'public',
            'size' => 1000,
            'mime_type' => 'image/jpeg',
            'status' => 'completed',
        ]);

        $response = $this->deleteJson(route('media.destroy', ['id' => $media->id]));

        $response->assertStatus(401);
    }
}
