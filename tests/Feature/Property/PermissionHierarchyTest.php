<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Models\Wedding;
use App\Services\PermissionManagementService;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

/**
 * Feature: user-role-management, Properties 9, 10: Permission Hierarchy and Immediate Changes
 * 
 * Property 9: For any user and module:
 * - Admin has access to all modules of any wedding
 * - Noivos has access to all modules of their wedding
 * - Organizer has access only to configured modules
 * - Guest has access only to APP module
 * 
 * Property 10: For any permission change, the next access check must reflect the new permissions.
 * 
 * Validates: Requirements 3.5, 4.1, 4.3, 4.4, 5.2, 6.1, 6.2, 6.4
 */
class PermissionHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionService $permissionService;
    protected PermissionManagementService $permissionManagementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = new PermissionService();
        $this->permissionManagementService = new PermissionManagementService();
    }

    /**
     * Property 9: Admin has access to all modules
     * @test
     */
    public function admin_has_access_to_all_modules_in_any_wedding(): void
    {
        $allModules = array_keys(PermissionService::MODULES);

        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->create(['role' => 'admin']);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            $randomModule = $allModules[array_rand($allModules)];

            $this->assertTrue(
                $this->permissionService->canAccess($admin, $randomModule, $wedding),
                "Iteration {$i}: Admin should access '{$randomModule}' in any wedding"
            );

            // Cleanup
            $admin->delete();
            $wedding->delete();
        }
    }

    /**
     * Property 9: Couple has access to all modules in their wedding
     * @test
     */
    public function couple_has_access_to_all_modules_in_their_wedding(): void
    {
        $allModules = array_keys(PermissionService::MODULES);

        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            foreach ($allModules as $module) {
                $this->assertTrue(
                    $this->permissionService->canAccess($couple, $module, $wedding),
                    "Iteration {$i}: Couple should access '{$module}' in their wedding"
                );
            }

            // Cleanup
            $couple->weddings()->detach();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Property 9: Organizer only has access to permitted modules
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
     * Property 9: Guest only has access to APP module
     * @test
     */
    public function guest_only_has_access_to_app_module(): void
    {
        $allModules = array_keys(PermissionService::MODULES);

        for ($i = 0; $i < 100; $i++) {
            $guest = User::factory()->create(['role' => 'guest']);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            foreach ($allModules as $module) {
                $expected = $module === 'app';
                $actual = $this->permissionService->canAccess($guest, $module, $wedding);

                $this->assertEquals(
                    $expected,
                    $actual,
                    "Iteration {$i}: Guest access to '{$module}' should be " . ($expected ? 'true' : 'false')
                );
            }

            // Cleanup
            $guest->delete();
            $wedding->delete();
        }
    }

    /**
     * Property 10: Permission changes are immediate
     * @test
     */
    public function permission_changes_are_immediate(): void
    {
        $allModules = array_keys(PermissionService::MODULES);

        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            $organizer = User::factory()->create(['role' => 'organizer']);
            $organizer->weddings()->attach($wedding->id, [
                'role' => 'organizer',
                'permissions' => [],
            ]);

            // Initially no permissions
            $randomModule = $allModules[array_rand($allModules)];
            $this->assertFalse(
                $this->permissionService->canAccess($organizer, $randomModule, $wedding),
                "Iteration {$i}: Organizer should NOT have access to '{$randomModule}' initially"
            );

            // Grant permission
            $this->permissionManagementService->updateOrganizerPermissions(
                $couple,
                $wedding,
                $organizer,
                [$randomModule]
            );

            // Refresh organizer to get updated pivot
            $organizer->refresh();

            // Now should have access
            $this->assertTrue(
                $this->permissionService->canAccess($organizer, $randomModule, $wedding),
                "Iteration {$i}: Organizer should have access to '{$randomModule}' after grant"
            );

            // Revoke permission
            $this->permissionManagementService->updateOrganizerPermissions(
                $couple,
                $wedding,
                $organizer,
                []
            );

            // Refresh organizer
            $organizer->refresh();

            // Should no longer have access
            $this->assertFalse(
                $this->permissionService->canAccess($organizer, $randomModule, $wedding),
                "Iteration {$i}: Organizer should NOT have access to '{$randomModule}' after revoke"
            );

            // Cleanup
            $wedding->users()->detach();
            $organizer->delete();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Test: Only couple and admin can manage permissions
     * @test
     */
    public function only_couple_and_admin_can_manage_permissions(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        $organizer = User::factory()->create(['role' => 'organizer']);
        $organizer->weddings()->attach($wedding->id, ['role' => 'organizer', 'permissions' => []]);

        // Organizer cannot manage permissions
        $this->expectException(AccessDeniedHttpException::class);
        $this->permissionManagementService->getOrganizersWithPermissions($organizer, $wedding);
    }

    /**
     * Test: Invalid module throws exception
     * @test
     */
    public function invalid_module_throws_exception(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        $organizer = User::factory()->create(['role' => 'organizer']);
        $organizer->weddings()->attach($wedding->id, ['role' => 'organizer', 'permissions' => []]);

        $this->expectException(\InvalidArgumentException::class);
        $this->permissionManagementService->updateOrganizerPermissions(
            $couple,
            $wedding,
            $organizer,
            ['invalid_module']
        );
    }
}
