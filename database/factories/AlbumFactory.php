<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Album>
 */
class AlbumFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Album::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'album_type_id' => fn () => AlbumType::inRandomOrder()->first()?->id ?? $this->createAlbumType()->id,
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'cover_media_id' => null,
        ];
    }

    /**
     * Create an album type if none exists.
     */
    private function createAlbumType(): AlbumType
    {
        return AlbumType::firstOrCreate(
            ['slug' => AlbumType::PRE_WEDDING],
            ['name' => 'Pré Casamento', 'description' => 'Fotos e vídeos do pré-casamento']
        );
    }

    /**
     * Create an album with pre-wedding type.
     */
    public function preWedding(): static
    {
        return $this->state(function (array $attributes) {
            $type = AlbumType::firstOrCreate(
                ['slug' => AlbumType::PRE_WEDDING],
                ['name' => 'Pré Casamento', 'description' => 'Fotos e vídeos do pré-casamento']
            );
            
            return ['album_type_id' => $type->id];
        });
    }

    /**
     * Create an album with post-wedding type.
     */
    public function postWedding(): static
    {
        return $this->state(function (array $attributes) {
            $type = AlbumType::firstOrCreate(
                ['slug' => AlbumType::POST_WEDDING],
                ['name' => 'Pós Casamento', 'description' => 'Fotos e vídeos do pós-casamento']
            );
            
            return ['album_type_id' => $type->id];
        });
    }

    /**
     * Create an album with site usage type.
     */
    public function siteUsage(): static
    {
        return $this->state(function (array $attributes) {
            $type = AlbumType::firstOrCreate(
                ['slug' => AlbumType::SITE_USAGE],
                ['name' => 'Uso do Site', 'description' => 'Imagens para uso no site']
            );
            
            return ['album_type_id' => $type->id];
        });
    }

    /**
     * Create an album for a specific wedding.
     */
    public function forWedding(Wedding $wedding): static
    {
        return $this->state(fn (array $attributes) => [
            'wedding_id' => $wedding->id,
        ]);
    }

    /**
     * Create an album with a specific album type.
     */
    public function withType(AlbumType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'album_type_id' => $type->id,
        ]);
    }
}
