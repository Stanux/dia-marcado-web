<?php

namespace Database\Factories;

use App\Models\SiteLayout;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteLayout>
 */
class SiteLayoutFactory extends Factory
{
    protected $model = SiteLayout::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'draft_content' => $this->generateDefaultContent(),
            'published_content' => null,
            'slug' => fake()->unique()->slug(3),
            'custom_domain' => null,
            'access_token' => null,
            'is_published' => false,
            'published_at' => null,
        ];
    }

    /**
     * Generate default site content structure.
     */
    protected function generateDefaultContent(): array
    {
        return [
            'version' => '1.0',
            'sections' => [
                'header' => [
                    'enabled' => true,
                    'logo' => ['url' => '', 'alt' => ''],
                    'title' => fake()->words(3, true),
                    'subtitle' => fake()->sentence(),
                    'showDate' => true,
                    'navigation' => [],
                    'actionButton' => ['label' => '', 'target' => '', 'style' => 'primary'],
                    'style' => [
                        'height' => '80px',
                        'alignment' => 'center',
                        'backgroundColor' => '#ffffff',
                        'sticky' => false,
                    ],
                ],
                'hero' => [
                    'enabled' => true,
                    'media' => ['type' => 'image', 'url' => '', 'fallback' => ''],
                    'title' => fake()->words(4, true),
                    'subtitle' => fake()->sentence(),
                    'ctaPrimary' => ['label' => '', 'target' => ''],
                    'ctaSecondary' => ['label' => '', 'target' => ''],
                    'layout' => 'full-bleed',
                    'style' => [
                        'overlay' => ['color' => '#000000', 'opacity' => 0.3],
                        'textAlign' => 'center',
                        'animation' => 'fade',
                    ],
                ],
                'saveTheDate' => [
                    'enabled' => true,
                    'showMap' => true,
                    'mapCoordinates' => ['lat' => null, 'lng' => null],
                    'description' => fake()->paragraph(),
                    'showCountdown' => true,
                    'countdownFormat' => 'days',
                    'showCalendarButton' => true,
                    'style' => ['backgroundColor' => '#f5f5f5', 'layout' => 'card'],
                ],
                'giftRegistry' => [
                    'enabled' => false,
                    'style' => ['backgroundColor' => '#ffffff'],
                ],
                'rsvp' => [
                    'enabled' => false,
                    'style' => ['backgroundColor' => '#f5f5f5'],
                ],
                'photoGallery' => [
                    'enabled' => false,
                    'albums' => [
                        'before' => ['title' => 'Nossa História', 'photos' => []],
                        'after' => ['title' => 'O Grande Dia', 'photos' => []],
                    ],
                    'layout' => 'masonry',
                    'allowDownload' => true,
                    'style' => ['backgroundColor' => '#ffffff'],
                ],
                'footer' => [
                    'enabled' => true,
                    'socialLinks' => [],
                    'copyrightText' => '',
                    'copyrightYear' => null,
                    'showPrivacyPolicy' => false,
                    'showBackToTop' => true,
                    'style' => [
                        'backgroundColor' => '#333333',
                        'textColor' => '#ffffff',
                    ],
                ],
            ],
            'meta' => [
                'title' => '',
                'description' => '',
                'ogImage' => '',
                'canonical' => '',
            ],
            'theme' => [
                'primaryColor' => fake()->hexColor(),
                'secondaryColor' => fake()->hexColor(),
                'fontFamily' => 'Playfair Display',
                'fontSize' => '16px',
            ],
        ];
    }

    /**
     * Create a published site.
     */
    public function published(): static
    {
        return $this->state(function (array $attributes) {
            $content = $attributes['draft_content'] ?? $this->generateDefaultContent();
            return [
                'is_published' => true,
                'published_at' => now(),
                'published_content' => $content,
            ];
        });
    }

    /**
     * Create a site with access token (password protected).
     */
    public function withAccessToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_token' => fake()->password(8, 12),
        ]);
    }

    /**
     * Create a site with custom domain.
     */
    public function withCustomDomain(): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_domain' => fake()->domainName(),
        ]);
    }
}
