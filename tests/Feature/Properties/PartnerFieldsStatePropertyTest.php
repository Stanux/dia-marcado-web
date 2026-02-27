<?php

namespace Tests\Feature\Properties;

use App\Filament\Pages\WeddingSettings;
use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property: Estado dos Campos de Parceiro
 */
class PartnerFieldsStatePropertyTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function partner_linked_returns_correct_status(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user1 = User::factory()->onboardingCompleted()->create();
            $user2 = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();

            $wedding->users()->attach($user1->id, ['role' => 'couple', 'permissions' => []]);
            $wedding->users()->attach($user2->id, ['role' => 'couple', 'permissions' => []]);

            $this->actingAs($user1);

            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);

            $this->assertEquals(
                'partner_linked',
                $status,
                "Wedding with linked partner should return 'partner_linked' status (iteration $i)"
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_partner_returns_correct_status_when_only_creator_exists(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();

            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);

            $this->actingAs($user);

            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);

            $this->assertEquals(
                'no_partner',
                $status,
                "Wedding without linked partner should return 'no_partner' status (iteration $i)"
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function existing_invites_do_not_change_status_without_linked_partner(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();

            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);

            PartnerInvite::factory()->create([
                'wedding_id' => $wedding->id,
                'inviter_id' => $user->id,
                'status' => fake()->randomElement(['pending', 'declined']),
                'expires_at' => fake()->boolean() ? now()->addDays(7) : now()->subDays(1),
            ]);

            $this->actingAs($user);

            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);

            $this->assertEquals(
                'no_partner',
                $status,
                "Without linked partner, status should remain 'no_partner' even with invites (iteration $i)"
            );
        }
    }
}
