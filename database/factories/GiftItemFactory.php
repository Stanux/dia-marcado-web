<?php

namespace Database\Factories;

use App\Models\GiftItem;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GiftItem>
 */
class GiftItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Jogo de Panelas',
            'Liquidificador',
            'Cafeteira Elétrica',
            'Jogo de Toalhas',
            'Edredom Casal',
            'Aspirador de Pó',
            'Ferro de Passar',
            'Micro-ondas',
            'Air Fryer',
            'Mixer',
            'Jogo de Facas',
            'Conjunto de Potes',
            'Tábua de Carne',
            'Aparelho de Jantar',
            'Jogo de Copos',
        ]);

        $description = fake()->sentence(10);
        $price = fake()->numberBetween(100, 100000); // R$ 1,00 a R$ 1.000,00
        $quantity = fake()->numberBetween(1, 10);

        return [
            'wedding_id' => Wedding::factory(),
            'name' => $name,
            'description' => $description,
            'photo_url' => fake()->imageUrl(640, 480, 'products', true),
            'price' => $price,
            'quantity_available' => $quantity,
            'quantity_sold' => 0,
            'is_enabled' => true,
            
            // Original values for restoration
            'original_name' => $name,
            'original_description' => $description,
            'original_price' => $price,
            'original_quantity' => $quantity,
        ];
    }

    /**
     * Create a gift item that is sold out.
     */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_available' => 0,
            'quantity_sold' => $attributes['original_quantity'],
            'is_enabled' => false,
        ]);
    }

    /**
     * Create a disabled gift item.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Create a gift item with edited values (different from original).
     */
    public function edited(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $attributes['original_name'] . ' (Editado)',
                'description' => $attributes['original_description'] . ' - Descrição modificada.',
                'price' => $attributes['original_price'] + 1000, // +R$ 10,00
                'quantity_available' => max(1, $attributes['original_quantity'] - 1),
            ];
        });
    }

    /**
     * Create a gift item with minimum price (R$ 1,00).
     */
    public function minimumPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 100,
            'original_price' => 100,
        ]);
    }

    /**
     * Create an expensive gift item.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->numberBetween(50000, 500000), // R$ 500,00 a R$ 5.000,00
            'original_price' => fake()->numberBetween(50000, 500000),
        ]);
    }

    /**
     * Create a gift item with low quantity.
     */
    public function lowQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_available' => 1,
            'original_quantity' => 1,
        ]);
    }

    /**
     * Create a gift item with high quantity.
     */
    public function highQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_available' => fake()->numberBetween(20, 100),
            'original_quantity' => fake()->numberBetween(20, 100),
        ]);
    }
}
