<?php

namespace Database\Factories;

use App\Models\Wedding;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wedding>
 */
class WeddingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true) . ' Wedding',
            'wedding_date' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'venue' => fake()->company(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'settings' => $this->generateRandomSettings(),
            'is_active' => true,
        ];
    }

    /**
     * Generate random settings for the wedding.
     */
    protected function generateRandomSettings(): array
    {
        return [
            'theme' => fake()->randomElement(['classic', 'modern', 'rustic', 'beach', 'garden']),
            'color_primary' => fake()->hexColor(),
            'color_secondary' => fake()->hexColor(),
            'guest_limit' => fake()->numberBetween(50, 500),
            'rsvp_deadline' => fake()->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'features' => [
                'gamification' => fake()->boolean(70),
                'photo_sharing' => fake()->boolean(80),
                'live_streaming' => fake()->boolean(30),
                'gift_registry' => fake()->boolean(60),
            ],
            'notifications' => [
                'email' => fake()->boolean(90),
                'sms' => fake()->boolean(50),
                'push' => fake()->boolean(70),
            ],
        ];
    }

    /**
     * Create an inactive wedding.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a wedding with a past date.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'wedding_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }

    /**
     * Create a wedding with minimal settings.
     */
    public function minimalSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => [
                'theme' => 'classic',
            ],
        ]);
    }

    /**
     * Create a wedding with empty settings.
     */
    public function emptySettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => [],
        ]);
    }

    /**
     * Create a wedding with complex nested settings.
     */
    public function complexSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => [
                'theme' => fake()->randomElement(['classic', 'modern', 'rustic']),
                'colors' => [
                    'primary' => fake()->hexColor(),
                    'secondary' => fake()->hexColor(),
                    'accent' => fake()->hexColor(),
                ],
                'modules' => [
                    'sites' => ['enabled' => true, 'subdomain' => fake()->slug()],
                    'tasks' => ['enabled' => true, 'categories' => fake()->words(5)],
                    'guests' => ['enabled' => true, 'max_guests' => fake()->numberBetween(50, 500)],
                    'finance' => ['enabled' => fake()->boolean(), 'budget' => fake()->numberBetween(10000, 100000)],
                    'reports' => ['enabled' => fake()->boolean()],
                    'app' => ['enabled' => true],
                ],
                'integrations' => [
                    'ai_reports' => fake()->boolean(),
                    'payment_gateway' => fake()->randomElement(['stripe', 'paypal', null]),
                ],
                'metadata' => [
                    'created_by' => fake()->uuid(),
                    'version' => '1.0',
                    'last_updated' => now()->toISOString(),
                ],
            ],
        ]);
    }
}
