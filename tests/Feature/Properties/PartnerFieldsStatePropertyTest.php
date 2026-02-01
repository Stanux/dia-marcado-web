<?php

namespace Tests\Feature\Properties;

use App\Filament\Pages\WeddingSettings;
use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property 5: Estado dos Campos de Parceiro
 * 
 * For any wedding:
 * - If the wedding has a linked partner, partner fields should be read-only
 * - If the wedding has no linked partner, partner fields should be editable
 * 
 * Validates: Requirements 7.2, 7.3
 */
class PartnerFieldsStatePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property test: Partner linked means read-only fields
     * 
     * For any wedding with a linked partner, the partner status should be "partner_linked".
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partner_linked_returns_correct_status(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user1 = User::factory()->onboardingCompleted()->create();
            $user2 = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            // Attach both users as couple
            $wedding->users()->attach($user1->id, ['role' => 'couple', 'permissions' => []]);
            $wedding->users()->attach($user2->id, ['role' => 'couple', 'permissions' => []]);
            
            // Simulate being logged in as user1
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

    /**
     * Property test: No partner and no invite means editable fields
     * 
     * For any wedding without a partner and without invites, status should be "no_partner".
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function no_partner_no_invite_returns_correct_status(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            // Attach only one user as couple
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            $this->actingAs($user);
            
            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);
            
            $this->assertEquals(
                'no_partner',
                $status,
                "Wedding without partner or invite should return 'no_partner' status (iteration $i)"
            );
        }
    }

    /**
     * Property test: Pending invite returns correct status
     * 
     * For any wedding with a pending invite, status should be "invite_pending".
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function pending_invite_returns_correct_status(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            // Create a pending invite
            PartnerInvite::factory()->create([
                'wedding_id' => $wedding->id,
                'inviter_id' => $user->id,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
            ]);
            
            $this->actingAs($user);
            
            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);
            
            $this->assertEquals(
                'invite_pending',
                $status,
                "Wedding with pending invite should return 'invite_pending' status (iteration $i)"
            );
        }
    }

    /**
     * Property test: Expired invite returns correct status
     * 
     * For any wedding with an expired invite, status should be "invite_expired".
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function expired_invite_returns_correct_status(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            // Create an expired invite
            PartnerInvite::factory()->create([
                'wedding_id' => $wedding->id,
                'inviter_id' => $user->id,
                'status' => 'pending',
                'expires_at' => now()->subDays(1),
            ]);
            
            $this->actingAs($user);
            
            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);
            
            $this->assertEquals(
                'invite_expired',
                $status,
                "Wedding with expired invite should return 'invite_expired' status (iteration $i)"
            );
        }
    }

    /**
     * Property test: Declined invite returns correct status
     * 
     * For any wedding with a declined invite, status should be "invite_declined".
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function declined_invite_returns_correct_status(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            // Create a declined invite
            PartnerInvite::factory()->create([
                'wedding_id' => $wedding->id,
                'inviter_id' => $user->id,
                'status' => 'declined',
                'expires_at' => now()->addDays(7),
            ]);
            
            $this->actingAs($user);
            
            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);
            
            $this->assertEquals(
                'invite_declined',
                $status,
                "Wedding with declined invite should return 'invite_declined' status (iteration $i)"
            );
        }
    }
}
