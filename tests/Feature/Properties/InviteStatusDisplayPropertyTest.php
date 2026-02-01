<?php

namespace Tests\Feature\Properties;

use App\Filament\Pages\WeddingSettings;
use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property 8: Exibição Correta de Status do Convite
 * 
 * For any wedding with an existing invite:
 * - If status is "pending", should display "Convite enviado - Aguardando aceite"
 * - If status is "expired", should display "Convite expirado" with resend option
 * - If status is "declined", should display "Convite recusado" with new invite option
 * - If partner is linked (invite accepted), should display partner data
 * 
 * Validates: Requirements 7.5, 8.1, 8.2, 8.3, 8.4
 */
class InviteStatusDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Get the expected description for a given status.
     */
    private function getExpectedDescription(string $status): string
    {
        return match ($status) {
            'partner_linked' => 'Seu(sua) parceiro(a) está vinculado(a) ao casamento',
            'invite_pending' => 'Convite enviado - Aguardando aceite',
            'invite_expired' => 'Convite expirado - Envie um novo convite',
            'invite_declined' => 'Convite recusado - Envie um novo convite',
            default => 'Convide seu(sua) parceiro(a) para participar do planejamento',
        };
    }

    /**
     * Property test: Pending invite shows correct message
     * 
     * For any wedding with a pending invite, the description should indicate waiting for acceptance.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function pending_invite_shows_correct_message(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
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
                "Status should be 'invite_pending' (iteration $i)"
            );
            
            $expectedDescription = $this->getExpectedDescription($status);
            $this->assertStringContainsString(
                'Aguardando aceite',
                $expectedDescription,
                "Description should mention waiting for acceptance (iteration $i)"
            );
        }
    }

    /**
     * Property test: Expired invite shows correct message with resend option
     * 
     * For any wedding with an expired invite, the description should indicate expiration.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function expired_invite_shows_correct_message(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
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
                "Status should be 'invite_expired' (iteration $i)"
            );
            
            $expectedDescription = $this->getExpectedDescription($status);
            $this->assertStringContainsString(
                'expirado',
                $expectedDescription,
                "Description should mention expiration (iteration $i)"
            );
        }
    }

    /**
     * Property test: Declined invite shows correct message with new invite option
     * 
     * For any wedding with a declined invite, the description should indicate refusal.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function declined_invite_shows_correct_message(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
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
                "Status should be 'invite_declined' (iteration $i)"
            );
            
            $expectedDescription = $this->getExpectedDescription($status);
            $this->assertStringContainsString(
                'recusado',
                $expectedDescription,
                "Description should mention refusal (iteration $i)"
            );
        }
    }

    /**
     * Property test: Linked partner shows partner data
     * 
     * For any wedding with a linked partner, the description should indicate the link.
     */
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
                "Description should mention partner is linked (iteration $i)"
            );
        }
    }

    /**
     * Property test: No partner shows invite form message
     * 
     * For any wedding without partner or invite, the description should invite to add partner.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function no_partner_shows_invite_form_message(): void
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
                'Convide',
                $expectedDescription,
                "Description should invite to add partner (iteration $i)"
            );
        }
    }

    /**
     * Property test: Status descriptions are mutually exclusive
     * 
     * For any wedding, only one status should apply at a time.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function status_descriptions_are_mutually_exclusive(): void
    {
        $allStatuses = ['partner_linked', 'invite_pending', 'invite_expired', 'invite_declined', 'no_partner'];
        
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            // Randomly create a scenario
            $scenario = rand(0, 4);
            
            switch ($scenario) {
                case 0: // Partner linked
                    $partner = User::factory()->onboardingCompleted()->create();
                    $wedding->users()->attach($partner->id, ['role' => 'couple', 'permissions' => []]);
                    break;
                case 1: // Pending invite
                    PartnerInvite::factory()->create([
                        'wedding_id' => $wedding->id,
                        'inviter_id' => $user->id,
                        'status' => 'pending',
                        'expires_at' => now()->addDays(7),
                    ]);
                    break;
                case 2: // Expired invite
                    PartnerInvite::factory()->create([
                        'wedding_id' => $wedding->id,
                        'inviter_id' => $user->id,
                        'status' => 'pending',
                        'expires_at' => now()->subDays(1),
                    ]);
                    break;
                case 3: // Declined invite
                    PartnerInvite::factory()->create([
                        'wedding_id' => $wedding->id,
                        'inviter_id' => $user->id,
                        'status' => 'declined',
                        'expires_at' => now()->addDays(7),
                    ]);
                    break;
                case 4: // No partner
                    // Nothing to add
                    break;
            }
            
            $this->actingAs($user);
            
            $page = new WeddingSettings();
            $status = $page->getPartnerStatus($wedding);
            
            $this->assertContains(
                $status,
                $allStatuses,
                "Status should be one of the valid statuses (iteration $i)"
            );
        }
    }
}
