<?php

namespace Database\Factories;

use App\Models\IdempotencyKey;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IdempotencyKey>
 */
class IdempotencyKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => Str::uuid()->toString(),
            'transaction_id' => null,
            'response' => null,
            'expires_at' => now()->addHours(24),
        ];
    }

    /**
     * Create an idempotency key with a transaction.
     */
    public function withTransaction(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_id' => Transaction::factory(),
            'response' => [
                'transaction_id' => 'TXN-' . Str::upper(Str::random(16)),
                'status' => 'pending',
                'created_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Create an expired idempotency key.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Create an idempotency key that never expires.
     */
    public function neverExpires(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Create an idempotency key with a custom key.
     */
    public function withKey(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
        ]);
    }
}
