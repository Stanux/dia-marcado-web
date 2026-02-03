<?php

namespace Tests\Feature\Controllers;

use App\Models\AlbumType;
use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for AlbumController.
 * 
 * Tests album creation via API.
 */
class AlbumControllerTest extends TestCase
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
    public function it_creates_album_with_valid_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('albums.store'), [
                'name' => 'My Album',
                'description' => 'Test description',
                'type' => 'uso_site',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'My Album',
            'description' => 'Test description',
            'media_count' => 0,
        ]);

        $this->assertDatabaseHas('albums', [
            'wedding_id' => $this->wedding->id,
            'name' => 'My Album',
            'description' => 'Test description',
        ]);
    }

    /** @test */
    public function it_creates_album_with_default_type()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('albums.store'), [
                'name' => 'My Album',
            ]);

        $response->assertStatus(201);
        
        $albumType = AlbumType::where('slug', 'uso_site')->first();
        $this->assertDatabaseHas('albums', [
            'wedding_id' => $this->wedding->id,
            'name' => 'My Album',
            'album_type_id' => $albumType->id,
        ]);
    }

    /** @test */
    public function it_validates_required_name()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('albums.store'), [
                'description' => 'Test description',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_album_type()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('albums.store'), [
                'name' => 'My Album',
                'type' => 'invalid_type',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson(route('albums.store'), [
            'name' => 'My Album',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_current_wedding()
    {
        $userWithoutWedding = User::factory()->create([
            'current_wedding_id' => null,
        ]);

        $response = $this->actingAs($userWithoutWedding)
            ->postJson(route('albums.store'), [
                'name' => 'My Album',
            ]);

        // The middleware will redirect if no wedding context
        $response->assertStatus(302);
    }
}
