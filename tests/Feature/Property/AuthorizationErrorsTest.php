<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature: wedding-saas-foundation, Property 3: Autorização e Erros de Acesso
 * 
 * For any request to a protected resource:
 * - If there is no valid token, it should return 401 Unauthorized
 * - If the token is not linked to a valid wedding_id (for non-Admins), it should return 401 Unauthorized
 * - If the user does not have permission on the requested module, it should return 403 Forbidden
 * 
 * Validates: Requirements 1.4, 2.7, 4.3, 4.4
 */
class AuthorizationErrorsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property test: Requests without token return 401 Unauthorized
     * @test
     */
    public function requests_without_token_return_401(): void
    {
        $endpoints = [
            ['GET', '/api/wedding'],
            ['GET', '/api/tasks'],
            ['POST', '/api/tasks'],
            ['GET', '/api/guests'],
            ['GET', '/api/finance'],
            ['GET', '/api/reports'],
            ['GET', '/api/sites'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $endpoint = $endpoints[array_rand($endpoints)];
            [$method, $uri] = $endpoint;

            $response = $this->json($method, $uri);

            $response->assertStatus(401);
            $response->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        }
    }

    /**
     * Property test: Non-admin users without wedding context return 401
     * @test
     */
    public function non_admin_without_wedding_context_returns_401(): void
    {
        $roles = ['couple', 'organizer', 'guest'];

        for ($i = 0; $i < 100; $i++) {
            $role = $roles[array_rand($roles)];
            $user = User::factory()->create([
                'role' => $role,
                'current_wedding_id' => null,
            ]);

            Sanctum::actingAs($user);

            // Request without X-Wedding-ID header
            $response = $this->getJson('/api/wedding');

            $response->assertStatus(401);
            $response->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Wedding context required',
            ]);

            // Cleanup
            $user->delete();
        }
    }

    /**
     * Property test: Users with invalid wedding_id return 401
     * @test
     */
    public function users_with_invalid_wedding_id_return_401(): void
    {
        $roles = ['couple', 'organizer'];

        for ($i = 0; $i < 100; $i++) {
            $role = $roles[array_rand($roles)];
            $user = User::factory()->create([
                'role' => $role,
                'current_wedding_id' => null,
            ]);

            Sanctum::actingAs($user);

            // Request with non-existent wedding ID
            $fakeWeddingId = fake()->uuid();
            $response = $this->getJson('/api/wedding', [
                'X-Wedding-ID' => $fakeWeddingId,
            ]);

            $response->assertStatus(401);
            $response->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid wedding',
            ]);

            // Cleanup
            $user->delete();
        }
    }

    /**
     * Property test: Users accessing wedding they don't belong to return 403
     * @test
     */
    public function users_accessing_unauthorized_wedding_return_403(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Create two weddings
            $wedding1 = Wedding::create(['title' => "Wedding 1 - {$i}"]);
            $wedding2 = Wedding::create(['title' => "Wedding 2 - {$i}"]);

            // Create user with access to wedding1 only
            $role = fake()->randomElement(['couple', 'organizer']);
            $user = User::factory()->create(['role' => $role]);
            $user->weddings()->attach($wedding1->id, [
                'role' => $role,
                'permissions' => array_keys(\App\Services\PermissionService::MODULES),
            ]);

            Sanctum::actingAs($user);

            // Try to access wedding2
            $response = $this->getJson('/api/wedding', [
                'X-Wedding-ID' => $wedding2->id,
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'error' => 'Forbidden',
                'message' => 'Access to this wedding is not allowed',
            ]);

            // Cleanup
            $user->weddings()->detach();
            $user->delete();
            $wedding1->delete();
            $wedding2->delete();
        }
    }

    /**
     * Property test: Users without module permission return 403
     * @test
     */
    public function users_without_module_permission_return_403(): void
    {
        $moduleEndpoints = [
            'tasks' => '/api/tasks',
            'guests' => '/api/guests',
            'finance' => '/api/finance',
            'reports' => '/api/reports',
            'sites' => '/api/sites',
        ];

        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            // Create organizer with NO permissions
            $organizer = User::factory()->create(['role' => 'organizer']);
            $organizer->weddings()->attach($wedding->id, [
                'role' => 'organizer',
                'permissions' => [], // No permissions
            ]);

            Sanctum::actingAs($organizer);

            // Pick a random module endpoint
            $module = array_rand($moduleEndpoints);
            $endpoint = $moduleEndpoints[$module];

            $response = $this->getJson($endpoint, [
                'X-Wedding-ID' => $wedding->id,
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'error' => 'Forbidden',
                'message' => "Access to module '{$module}' is not allowed",
            ]);

            // Cleanup
            $organizer->weddings()->detach();
            $organizer->delete();
            $wedding->delete();
        }
    }

    /**
     * Property test: Guest users can only access APP module
     * @test
     */
    public function guest_users_can_only_access_app_module(): void
    {
        $restrictedEndpoints = [
            'tasks' => '/api/tasks',
            'guests' => '/api/guests',
            'finance' => '/api/finance',
            'reports' => '/api/reports',
            'sites' => '/api/sites',
        ];

        for ($i = 0; $i < 100; $i++) {
            // Create a wedding for the guest
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            
            $guest = User::factory()->create(['role' => 'guest']);
            $guest->weddings()->attach($wedding->id, [
                'role' => 'guest',
                'permissions' => ['app'], // Guests only have app permission
            ]);

            Sanctum::actingAs($guest);

            // Pick a random restricted endpoint
            $module = array_rand($restrictedEndpoints);
            $endpoint = $restrictedEndpoints[$module];

            $response = $this->getJson($endpoint, [
                'X-Wedding-ID' => $wedding->id,
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'error' => 'Forbidden',
                'message' => "Access to module '{$module}' is not allowed",
            ]);

            // Cleanup
            $guest->weddings()->detach();
            $guest->delete();
            $wedding->delete();
        }
    }

    /**
     * Property test: Admin users can access any wedding without context
     * @test
     */
    public function admin_users_can_access_without_wedding_context(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $admin = User::factory()->create([
                'role' => 'admin',
                'current_wedding_id' => null,
            ]);

            Sanctum::actingAs($admin);

            // Admin should be able to access user endpoint without wedding context
            $response = $this->getJson('/api/user');

            $response->assertStatus(200);

            // Cleanup
            $admin->delete();
        }
    }

    /**
     * Property test: Valid authenticated users with proper permissions get 200
     * @test
     */
    public function valid_users_with_permissions_get_200(): void
    {
        $moduleEndpoints = [
            'tasks' => '/api/tasks',
            'guests' => '/api/guests',
            'finance' => '/api/finance',
            'reports' => '/api/reports',
            'sites' => '/api/sites',
        ];

        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);

            // Create couple user with full access
            $couple = User::factory()->create([
                'role' => 'couple',
                'current_wedding_id' => $wedding->id,
            ]);
            $couple->weddings()->attach($wedding->id, [
                'role' => 'couple',
                'permissions' => [],
            ]);

            Sanctum::actingAs($couple);

            // Pick a random module endpoint
            $endpoint = $moduleEndpoints[array_rand($moduleEndpoints)];

            $response = $this->getJson($endpoint, [
                'X-Wedding-ID' => $wedding->id,
            ]);

            $response->assertStatus(200);

            // Cleanup
            $couple->weddings()->detach();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * @test
     */
    public function organizer_with_specific_permissions_can_access_allowed_modules(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);

        // Create organizer with only 'tasks' and 'guests' permissions
        $organizer = User::factory()->create(['role' => 'organizer']);
        $organizer->weddings()->attach($wedding->id, [
            'role' => 'organizer',
            'permissions' => ['tasks', 'guests'],
        ]);

        Sanctum::actingAs($organizer);

        // Should be able to access tasks
        $response = $this->getJson('/api/tasks', ['X-Wedding-ID' => $wedding->id]);
        $response->assertStatus(200);

        // Should be able to access guests
        $response = $this->getJson('/api/guests', ['X-Wedding-ID' => $wedding->id]);
        $response->assertStatus(200);

        // Should NOT be able to access finance
        $response = $this->getJson('/api/finance', ['X-Wedding-ID' => $wedding->id]);
        $response->assertStatus(403);

        // Should NOT be able to access reports
        $response = $this->getJson('/api/reports', ['X-Wedding-ID' => $wedding->id]);
        $response->assertStatus(403);
    }
}
