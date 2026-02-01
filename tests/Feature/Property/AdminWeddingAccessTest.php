<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: user-role-management, Property 11: Admin Vê Todos os Casamentos
 * 
 * For any Admin, the wedding listing must return all weddings in the system.
 * 
 * Validates: Requirements 5.1
 */
class AdminWeddingAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 11: Admin can see all weddings
     * @test
     */
    public function admin_can_see_all_weddings(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Create random number of weddings
            $weddingCount = rand(1, 5);
            $weddings = [];

            for ($j = 0; $j < $weddingCount; $j++) {
                $weddings[] = Wedding::create(['title' => "Wedding {$i}-{$j}"]);
            }

            $admin = User::factory()->create(['role' => 'admin']);

            // Admin should see all weddings
            $this->actingAs($admin);

            $allWeddings = Wedding::all();

            $this->assertCount(
                $weddingCount,
                $allWeddings,
                "Iteration {$i}: Admin should see all {$weddingCount} weddings"
            );

            // Cleanup
            foreach ($weddings as $wedding) {
                $wedding->delete();
            }
            $admin->delete();
        }
    }

    /**
     * Property 11: Admin can access any wedding
     * @test
     */
    public function admin_has_access_to_any_wedding(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->create(['role' => 'admin']);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            // Admin is NOT linked to this wedding
            $this->assertTrue(
                $admin->hasAccessTo($wedding),
                "Iteration {$i}: Admin should have access to any wedding"
            );

            // Cleanup
            $admin->delete();
            $wedding->delete();
        }
    }

    /**
     * Test: Non-admin cannot see weddings they don't belong to
     * @test
     */
    public function non_admin_cannot_see_other_weddings(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Create wedding for another user
            $otherWedding = Wedding::create(['title' => "Other Wedding {$i}"]);
            $otherCouple = User::factory()->create(['role' => 'couple']);
            $otherCouple->weddings()->attach($otherWedding->id, ['role' => 'couple', 'permissions' => []]);

            // Create our user with their own wedding
            $myWedding = Wedding::create(['title' => "My Wedding {$i}"]);
            $myCouple = User::factory()->create(['role' => 'couple']);
            $myCouple->weddings()->attach($myWedding->id, ['role' => 'couple', 'permissions' => []]);

            // My couple should NOT have access to other wedding
            $this->assertFalse(
                $myCouple->hasAccessTo($otherWedding),
                "Iteration {$i}: Couple should NOT have access to other weddings"
            );

            // Cleanup
            $otherCouple->weddings()->detach();
            $myCouple->weddings()->detach();
            $otherCouple->delete();
            $myCouple->delete();
            $otherWedding->delete();
            $myWedding->delete();
        }
    }

    /**
     * Test: Admin role in any wedding returns 'admin'
     * @test
     */
    public function admin_role_in_any_wedding_returns_admin(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->create(['role' => 'admin']);
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            $role = $admin->roleIn($wedding);

            $this->assertEquals(
                'admin',
                $role,
                "Iteration {$i}: Admin's role in any wedding should be 'admin'"
            );

            // Cleanup
            $admin->delete();
            $wedding->delete();
        }
    }
}
