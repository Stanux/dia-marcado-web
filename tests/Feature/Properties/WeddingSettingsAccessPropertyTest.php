<?php

namespace Tests\Feature\Properties;

use App\Models\User;
use App\Models\Wedding;
use App\Services\WeddingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: onboarding-improvements, Property 1: Controle de Acesso à Página de Configurações
 * 
 * For any user and wedding, the user should only have access to the settings page
 * if they have the "couple" role in the wedding. Users with other roles or no link
 * should be blocked.
 * 
 * Validates: Requirements 3.4
 */
class WeddingSettingsAccessPropertyTest extends TestCase
{
    use RefreshDatabase;

    private WeddingSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WeddingSettingsService();
    }

    /**
     * Property test: Users with "couple" role can edit wedding settings
     * 
     * For any user with the "couple" role in a wedding,
     * canEdit should return true.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function users_with_couple_role_can_edit_settings(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            // Attach user as couple
            $wedding->users()->attach($user->id, [
                'role' => 'couple',
                'permissions' => [],
            ]);
            
            $canEdit = $this->service->canEdit($user, $wedding);
            
            $this->assertTrue(
                $canEdit,
                "User with 'couple' role should be able to edit settings (iteration $i)"
            );
        }
    }

    /**
     * Property test: Users with "organizer" role cannot edit wedding settings
     * 
     * For any user with the "organizer" role in a wedding,
     * canEdit should return false.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function users_with_organizer_role_cannot_edit_settings(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            // Attach user as organizer
            $wedding->users()->attach($user->id, [
                'role' => 'organizer',
                'permissions' => [],
            ]);
            
            $canEdit = $this->service->canEdit($user, $wedding);
            
            $this->assertFalse(
                $canEdit,
                "User with 'organizer' role should not be able to edit settings (iteration $i)"
            );
        }
    }

    /**
     * Property test: Users with "guest" role cannot edit wedding settings
     * 
     * For any user with the "guest" role in a wedding,
     * canEdit should return false.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function users_with_guest_role_cannot_edit_settings(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            // Attach user as guest
            $wedding->users()->attach($user->id, [
                'role' => 'guest',
                'permissions' => [],
            ]);
            
            $canEdit = $this->service->canEdit($user, $wedding);
            
            $this->assertFalse(
                $canEdit,
                "User with 'guest' role should not be able to edit settings (iteration $i)"
            );
        }
    }

    /**
     * Property test: Users not linked to wedding cannot edit settings
     * 
     * For any user not linked to a wedding,
     * canEdit should return false.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function users_not_linked_to_wedding_cannot_edit_settings(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            // User is NOT attached to wedding
            
            $canEdit = $this->service->canEdit($user, $wedding);
            
            $this->assertFalse(
                $canEdit,
                "User not linked to wedding should not be able to edit settings (iteration $i)"
            );
        }
    }

    /**
     * Property test: Only couple role grants edit access among all roles
     * 
     * For any user and wedding, only the "couple" role should grant edit access.
     * All other roles should be denied.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function only_couple_role_grants_edit_access(): void
    {
        $roles = ['couple', 'organizer', 'guest'];
        
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingCompleted()->create();
            $wedding = Wedding::factory()->create();
            
            // Pick a random role
            $role = $roles[array_rand($roles)];
            
            $wedding->users()->attach($user->id, [
                'role' => $role,
                'permissions' => [],
            ]);
            
            $canEdit = $this->service->canEdit($user, $wedding);
            $expectedCanEdit = ($role === 'couple');
            
            $this->assertEquals(
                $expectedCanEdit,
                $canEdit,
                "canEdit should be " . ($expectedCanEdit ? 'true' : 'false') . " for role '$role' (iteration $i)"
            );
        }
    }
}
