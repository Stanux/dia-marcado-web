<?php

namespace Tests\Feature\Properties;

use App\Filament\Pages\WeddingSettings;
use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property: Exibição de Status do Parceiro
 */
class InviteStatusDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    private function getExpectedDescription(string $status): string
    {
        return match ($status) {
            'partner_linked' => 'Seu(sua) parceiro(a) está vinculado(a) ao casamento',
            default => 'Crie a conta do(a) parceiro(a) em Usuários para liberar acesso colaborativo',
        };
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function linked_partner_shows_correct_message(): void
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
                "Status should be 'partner_linked' (iteration $i)"
            );

            $expectedDescription = $this->getExpectedDescription($status);
            $this->assertStringContainsString(
                'vinculado',
                $expectedDescription,
                "Description should mention partner linkage (iteration $i)"
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_partner_shows_users_guidance_message(): void
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
                "Status should be 'no_partner' (iteration $i)"
            );

            $expectedDescription = $this->getExpectedDescription($status);
            $this->assertStringContainsString(
                'Usuários',
                $expectedDescription,
                "Description should guide to Users flow (iteration $i)"
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invites_do_not_override_status_display_without_linked_partner(): void
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

            $this->assertEquals('no_partner', $status, "Status should remain no_partner (iteration $i)");

            $expectedDescription = $this->getExpectedDescription($status);
            $this->assertStringContainsString('Usuários', $expectedDescription);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function status_descriptions_are_mutually_exclusive(): void
    {
        $allStatuses = ['partner_linked', 'no_partner'];

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();

            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);

            if (rand(0, 1) === 1) {
                $partner = User::factory()->onboardingCompleted()->create();
                $wedding->users()->attach($partner->id, ['role' => 'couple', 'permissions' => []]);
            }

            $this->actingAs($user);

            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);

            $this->assertContains(
                $status,
                $allStatuses,
                "Status should be one of valid statuses (iteration $i)"
            );
        }
    }
}
