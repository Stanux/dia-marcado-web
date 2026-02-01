<?php

namespace Database\Factories;

use App\Models\SiteLayout;
use App\Models\SiteVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteVersion>
 */
class SiteVersionFactory extends Factory
{
    protected $model = SiteVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_layout_id' => SiteLayout::factory(),
            'user_id' => User::factory(),
            'content' => $this->generateVersionContent(),
            'summary' => fake()->sentence(),
            'is_published' => false,
        ];
    }

    /**
     * Generate version content structure.
     */
    protected function generateVersionContent(): array
    {
        return [
            'version' => '1.0',
            'sections' => [
                'header' => [
                    'enabled' => true,
                    'title' => fake()->words(3, true),
                    'subtitle' => fake()->sentence(),
                ],
                'hero' => [
                    'enabled' => true,
                    'title' => fake()->words(4, true),
                ],
                'saveTheDate' => ['enabled' => true],
                'giftRegistry' => ['enabled' => false],
                'rsvp' => ['enabled' => false],
                'photoGallery' => ['enabled' => false],
                'footer' => ['enabled' => true],
            ],
            'theme' => [
                'primaryColor' => fake()->hexColor(),
                'secondaryColor' => fake()->hexColor(),
            ],
        ];
    }

    /**
     * Create a published version snapshot.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'summary' => 'Publicação do site',
        ]);
    }

    /**
     * Create a version for a specific site layout.
     */
    public function forSiteLayout(SiteLayout $siteLayout): static
    {
        return $this->state(fn (array $attributes) => [
            'site_layout_id' => $siteLayout->id,
        ]);
    }
}
