<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Models\Wedding;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-saas-foundation, Property 4: Permissões por Perfil
 * 
 * For any user and for any module in the system:
 * - If the user is Admin, they should have access to all modules
 * - If the user is Noivo/Noiva, they should have access to all modules of weddings they participate in
 * - If the user is Organizador, they should have access only to modules configured in their permissions
 * - If the user is Convidado, they should have access only to the APP module
 * 
 * Validates: Requirements 2.2, 2.3, 2.6
 */
class PermissionsByRoleTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = new PermissionService();
    }

    /**
     * Property test: Admin has access to all modules
     * @test
     */
    public function admin_has_access_to_all_modules(): void
    {
        $modules = array_keys(PermissionService::MODULES);

        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->create(['role' => 'admin']);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $randomModule = $modules[array_rand($modules)];

            // Admin should have access with or without wedding context
            $this->assertTrue(
                $this->permissionService->canAccess($admin, $randomModule, null),
                "Iteration {$i}: Admin should access '{$randomModule}' without wedding"
            );

            $this->assertTrue(
                $this->permissionService->canAccess($admin, $randomModule, $wedding),
                "Iteration {$i}: Admin should access '{$randomModule}' with wedding"
            );

            // Cleanup
            $admin->delete();
            $wedding->delete();
        }
    }

    /**
     * Property test: Couple has access to all modules in their wedding
     * @test
     */
    public function couple_has_access_to_all_modules_in_their_wedding(): void
    {
        $modules = array_keys(PermissionService::MODULES);

        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            $randomModule = $modules[array_rand($modules)];

            $this->assertTrue(
                $this->permissionService->canAccess($couple, $randomModule, $wedding),
                "Iteration {$i}: Couple should access '{$randomModule}' in their wedding"
            );

            // Cleanup
            $couple->weddings()->detach();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Property test: Couple cannot access modules in weddings they don't belong to
     * @test
     */
    public function couple_cannot_access_other_weddings(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding1 = Wedding::create(['title' => "Wedding 1 - {$i}"]);
            $wedding2 = Wedding::create(['title' => "Wedding 2 - {$i}"]);

            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding1->id, ['role' => 'couple', 'permissions' => []]);

            $modules = array_keys(PermissionService::MODULES);
            $randomModule = $modules[array_rand($modules)];

            // Should NOT have access to wedding2
            $this->assertFalse(
                $this->permissionService->canAccess($couple, $randomModule, $wedding2),
                "Iteration {$i}: Couple should NOT access '{$randomModule}' in other wedding"
            );

            // Cleanup
            $couple->weddings()->detach();
            $couple->delete();
            $wedding1->delete();
            $wedding2->delete();
        }
    }

    /**
     * Property test: Organizer only has access to permitted modules
     * @test
     */
    public function organizer_only_has_access_to_permitted_modules(): void
    {
        $allModules = array_keys(PermissionService::MODULES);

        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            // Random subset of permissions
            $permittedModules = array_values(array_filter(
                $allModules,
                fn() => (bool) rand(0, 1)
            ));

            $organizer = User::factory()->create(['role' => 'organizer']);
            $organizer->weddings()->attach($wedding->id, [
                'role' => 'organizer',
                'permissions' => $permittedModules,
            ]);

            foreach ($allModules as $module) {
                $expected = in_array($module, $permittedModules);
                $actual = $this->permissionService->canAccess($organizer, $module, $wedding);

                $this->assertEquals(
                    $expected,
                    $actual,
                    "Iteration {$i}: Organizer access to '{$module}' should be " . ($expected ? 'true' : 'false')
                );
            }

            // Cleanup
            $organizer->weddings()->detach();
            $organizer->delete();
            $wedding->delete();
        }
    }

    /**
     * Property test: Guest only has access to APP module
     * @test
     */
    public function guest_only_has_access_to_app_module(): void
    {
        $allModules = array_keys(PermissionService::MODULES);
        $guestModules = PermissionService::GUEST_MODULES;

        for ($i = 0; $i < 100; $i++) {
            $guest = User::factory()->create(['role' => 'guest']);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            $randomModule = $allModules[array_rand($allModules)];
            $expected = in_array($randomModule, $guestModules);

            $this->assertEquals(
                $expected,
                $this->permissionService->canAccess($guest, $randomModule, $wedding),
                "Iteration {$i}: Guest access to '{$randomModule}' should be " . ($expected ? 'true' : 'false')
            );

            // Cleanup
            $guest->delete();
            $wedding->delete();
        }
    }

    /**
     * @test
     */
    public function get_accessible_modules_returns_correct_modules(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);

        // Admin
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertEquals(
            array_keys(PermissionService::MODULES),
            $this->permissionService->getAccessibleModules($admin)
        );

        // Couple
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);
        $this->assertEquals(
            array_keys(PermissionService::MODULES),
            $this->permissionService->getAccessibleModules($couple, $wedding)
        );

        // Organizer with specific permissions
        $organizer = User::factory()->create(['role' => 'organizer']);
        $organizer->weddings()->attach($wedding->id, [
            'role' => 'organizer',
            'permissions' => ['tasks', 'guests'],
        ]);
        $this->assertEquals(
            ['tasks', 'guests'],
            $this->permissionService->getAccessibleModules($organizer, $wedding)
        );

        // Guest
        $guest = User::factory()->create(['role' => 'guest']);
        $this->assertEquals(
            PermissionService::GUEST_MODULES,
            $this->permissionService->getAccessibleModules($guest)
        );
    }
}
