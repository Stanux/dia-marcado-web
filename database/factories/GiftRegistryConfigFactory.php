<?php

namespace Database\Factories;

use App\Models\GiftRegistryConfig;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GiftRegistryConfig>
 */
class GiftRegistryConfigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'is_enabled' => true,
            'section_title' => fake()->randomElement([
                'Lista de Presentes',
                'Nossos Presentes',
                'Presenteie os Noivos',
                'Lista de Casamento',
                'Presentes Especiais',
            ]),
            'title_font_family' => fake()->randomElement([
                'Arial',
                'Helvetica',
                'Georgia',
                'Times New Roman',
                'Verdana',
                'Roboto',
                'Open Sans',
                'Lato',
            ]),
            'title_font_size' => fake()->numberBetween(24, 48),
            'title_color' => fake()->hexColor(),
            'title_style' => fake()->randomElement(['normal', 'bold', 'italic', 'bold_italic']),
            'fee_modality' => fake()->randomElement(['couple_pays', 'guest_pays']),
        ];
    }

    /**
     * Create a config with couple_pays modality.
     */
    public function couplePays(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_modality' => 'couple_pays',
        ]);
    }

    /**
     * Create a config with guest_pays modality.
     */
    public function guestPays(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_modality' => 'guest_pays',
        ]);
    }

    /**
     * Create a disabled config.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Create a config with default styling.
     */
    public function defaultStyling(): static
    {
        return $this->state(fn (array $attributes) => [
            'section_title' => 'Lista de Presentes',
            'title_font_family' => null,
            'title_font_size' => null,
            'title_color' => null,
            'title_style' => 'normal',
        ]);
    }

    /**
     * Create a config with custom styling.
     */
    public function customStyling(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_font_family' => 'Georgia',
            'title_font_size' => 36,
            'title_color' => '#2C3E50',
            'title_style' => 'bold',
        ]);
    }
}
