<?php

namespace Database\Factories;

use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartnerInvite>
 */
class PartnerInviteFactory extends Factory
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
            'inviter_id' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'token' => Str::random(64),
            'status' => 'pending',
            'existing_user_id' => null,
            'previous_wedding_id' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Create an invite for an existing user.
     */
    public function forExistingUser(User $user = null, Wedding $previousWedding = null): static
    {
        return $this->state(function (array $attributes) use ($user, $previousWedding) {
            $existingUser = $user ?? User::factory()->create();
            
            return [
                'email' => $existingUser->email,
                'name' => $existingUser->name,
                'existing_user_id' => $existingUser->id,
                'previous_wedding_id' => $previousWedding?->id,
            ];
        });
    }

    /**
     * Create an accepted invite.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    /**
     * Create a declined invite.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
        ]);
    }

    /**
     * Create an expired invite.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Create an invite that is about to expire.
     */
    public function expiringToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours(1),
        ]);
    }

    /**
     * Create an invite with a specific wedding and inviter.
     */
    public function forWedding(Wedding $wedding, User $inviter): static
    {
        return $this->state(fn (array $attributes) => [
            'wedding_id' => $wedding->id,
            'inviter_id' => $inviter->id,
        ]);
    }
}
