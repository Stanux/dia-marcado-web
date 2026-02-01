<?php

namespace Tests\Feature\Properties;

use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use App\Services\PartnerInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property 6: Criação de Convite ao Salvar Dados de Parceiro
 * 
 * For any valid partner data (name and valid email) in a wedding without a linked partner,
 * when saving, an invite should be created with status "pending".
 * 
 * Validates: Requirements 7.4
 */
class PartnerInviteCreationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private PartnerInviteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PartnerInviteService();
    }

    /**
     * Property test: Valid partner data creates pending invite
     * 
     * For any valid partner name and email, sending an invite should create
     * a PartnerInvite with status "pending".
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function valid_partner_data_creates_pending_invite(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            $partnerName = fake()->name();
            $partnerEmail = fake()->unique()->safeEmail();
            
            $invite = $this->service->sendInvite($wedding, $user, $partnerEmail, $partnerName);
            
            $this->assertInstanceOf(
                PartnerInvite::class,
                $invite,
                "sendInvite should return a PartnerInvite (iteration $i)"
            );
            
            $this->assertEquals(
                'pending',
                $invite->status,
                "New invite should have 'pending' status (iteration $i)"
            );
            
            $this->assertEquals(
                $partnerEmail,
                $invite->email,
                "Invite should have correct email (iteration $i)"
            );
            
            $this->assertEquals(
                $partnerName,
                $invite->name,
                "Invite should have correct name (iteration $i)"
            );
            
            $this->assertEquals(
                $wedding->id,
                $invite->wedding_id,
                "Invite should be linked to correct wedding (iteration $i)"
            );
        }
    }

    /**
     * Property test: Invite has valid expiration date
     * 
     * For any new invite, the expiration date should be in the future.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function invite_has_valid_expiration_date(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            $invite = $this->service->sendInvite(
                $wedding,
                $user,
                fake()->unique()->safeEmail(),
                fake()->name()
            );
            
            $this->assertTrue(
                $invite->expires_at->isFuture(),
                "Invite expiration should be in the future (iteration $i)"
            );
        }
    }

    /**
     * Property test: Invite has valid token
     * 
     * For any new invite, a unique token should be generated.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function invite_has_valid_token(): void
    {
        $tokens = [];
        
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            $invite = $this->service->sendInvite(
                $wedding,
                $user,
                fake()->unique()->safeEmail(),
                fake()->name()
            );
            
            $this->assertNotEmpty(
                $invite->token,
                "Invite should have a token (iteration $i)"
            );
            
            $this->assertNotContains(
                $invite->token,
                $tokens,
                "Invite token should be unique (iteration $i)"
            );
            
            $tokens[] = $invite->token;
        }
    }

    /**
     * Property test: Invite is linked to inviter
     * 
     * For any new invite, it should be linked to the user who sent it.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function invite_is_linked_to_inviter(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($user->id, ['role' => 'couple', 'permissions' => []]);
            
            $invite = $this->service->sendInvite(
                $wedding,
                $user,
                fake()->unique()->safeEmail(),
                fake()->name()
            );
            
            $this->assertEquals(
                $user->id,
                $invite->inviter_id,
                "Invite should be linked to the inviter (iteration $i)"
            );
        }
    }

    /**
     * Property test: Invite detects existing users
     * 
     * For any invite to an existing user's email, the invite should reference that user.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function invite_detects_existing_users(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $inviter = User::factory()->onboardingCompleted()->create();
            $existingUser = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            $wedding->users()->attach($inviter->id, ['role' => 'couple', 'permissions' => []]);
            
            $invite = $this->service->sendInvite(
                $wedding,
                $inviter,
                $existingUser->email,
                $existingUser->name
            );
            
            $this->assertEquals(
                $existingUser->id,
                $invite->existing_user_id,
                "Invite should reference existing user (iteration $i)"
            );
            
            $this->assertTrue(
                $invite->isForExistingUser(),
                "isForExistingUser should return true (iteration $i)"
            );
        }
    }
}
