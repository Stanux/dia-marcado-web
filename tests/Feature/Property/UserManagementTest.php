<?php

namespace Tests\Feature\Property;

use App\Http\Middleware\EnsureOnboardingComplete;
use App\Models\User;
use App\Models\Wedding;
use App\Services\PermissionService;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

/**
 * Feature: user-role-management, Properties 5, 6, 7, 8, 13: User Management Properties
 * 
 * Property 5: For any Noivos creating an Organizer with permissions, the user must have role "organizer" and permissions saved correctly.
 * Property 6: For any Organizer removed from a wedding, the user record must continue to exist.
 * Property 7: For any Guest creation, the user must have role "guest" and be linked to the wedding.
 * Property 8: For any Guest creation without name or email, the system must reject with validation error.
 * Property 13: For any user created (except self-registration), created_by must contain the creator's ID.
 * 
 * Validates: Requirements 2.2, 2.3, 2.6, 3.2, 3.3, 6.6
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected UserManagementService $userManagementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userManagementService = new UserManagementService(new PermissionService());
    }

    /**
     * Property 5: Organizer creation with permissions
     * @test
     */
    public function organizer_is_created_with_correct_role_and_permissions(): void
    {
        $allModules = ['sites', 'tasks', 'guests', 'finance', 'reports', 'app', 'users'];

        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            // Random permissions
            $permissions = array_values(array_filter(
                $allModules,
                fn() => (bool) rand(0, 1)
            ));

            $organizerData = [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => fake()->password(8, 20),
            ];

            $organizer = $this->userManagementService->createOrganizer(
                $couple,
                $wedding,
                $organizerData,
                $permissions
            );

            // Verify role
            $this->assertEquals(
                'organizer',
                $organizer->role,
                "Iteration {$i}: Organizer should have role 'organizer'"
            );

            // Verify linked to wedding
            $pivot = $organizer->weddings()
                ->where('wedding_id', $wedding->id)
                ->first()
                ?->pivot;

            $this->assertNotNull($pivot, "Iteration {$i}: Organizer should be linked to wedding");
            $this->assertEquals('organizer', $pivot->role);
            $this->assertEquals($permissions, $pivot->permissions);

            // Cleanup
            $wedding->users()->detach();
            $organizer->delete();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Test: Organizer existente pode ser vinculado em múltiplos casamentos.
     * @test
     */
    public function existing_organizer_can_be_linked_to_another_wedding(): void
    {
        $weddingA = Wedding::create(['title' => 'Wedding A']);
        $weddingB = Wedding::create(['title' => 'Wedding B']);

        $coupleB = User::factory()->create(['role' => 'couple']);
        $coupleB->weddings()->attach($weddingB->id, ['role' => 'couple', 'permissions' => []]);

        $existingOrganizer = User::factory()
            ->organizer()
            ->onboardingPending()
            ->create([
                'email' => 'organizer.multiple@example.com',
                'password' => Hash::make('old-secret'),
            ]);

        $existingOrganizer->weddings()->attach($weddingA->id, [
            'role' => 'organizer',
            'permissions' => ['event_data'],
        ]);

        $linkedOrganizer = $this->userManagementService->createOrganizer(
            $coupleB,
            $weddingB,
            [
                'name' => 'Nome Ignorado',
                'email' => 'organizer.multiple@example.com',
                'password' => 'new-secret',
            ],
            ['event_data', 'site_editor']
        );

        $this->assertSame($existingOrganizer->id, $linkedOrganizer->id);
        $this->assertDatabaseCount('users', 2);

        $pivotInWeddingB = $linkedOrganizer->weddings()
            ->where('wedding_id', $weddingB->id)
            ->first()
            ?->pivot;

        $this->assertNotNull($pivotInWeddingB);
        $this->assertSame('organizer', $pivotInWeddingB->role);
        $this->assertSame(['event_data', 'site_editor'], $pivotInWeddingB->permissions);

        $linkedOrganizer->refresh();

        $this->assertTrue($linkedOrganizer->hasCompletedOnboarding());
        $this->assertTrue(Hash::check('old-secret', $linkedOrganizer->password));
        $this->assertFalse(Hash::check('new-secret', $linkedOrganizer->password));

        $this->assertTrue(
            $linkedOrganizer->weddings()->where('wedding_id', $weddingA->id)->exists()
        );
    }

    /**
     * Test: Organizer já vinculado ao casamento não pode ser recriado.
     * @test
     */
    public function existing_organizer_linked_to_same_wedding_is_rejected(): void
    {
        $wedding = Wedding::create(['title' => 'Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        $existingOrganizer = User::factory()
            ->organizer()
            ->create(['email' => 'organizer.same@example.com']);

        $existingOrganizer->weddings()->attach($wedding->id, [
            'role' => 'organizer',
            'permissions' => ['event_data'],
        ]);

        $this->expectException(ValidationException::class);

        $this->userManagementService->createOrganizer(
            $couple,
            $wedding,
            [
                'name' => 'Organizer',
                'email' => 'organizer.same@example.com',
            ],
            ['site_editor']
        );
    }

    /**
     * Property 6: Removal preserves user record
     * @test
     */
    public function removal_from_wedding_preserves_user_record(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            $organizer = $this->userManagementService->createOrganizer(
                $couple,
                $wedding,
                [
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                ],
                []
            );

            $organizerId = $organizer->id;

            // Remove from wedding
            $this->userManagementService->removeFromWedding($organizer, $wedding);

            // Verify user still exists
            $this->assertDatabaseHas('users', ['id' => $organizerId]);

            // Verify not linked to wedding
            $this->assertFalse(
                $wedding->users()->where('user_id', $organizerId)->exists(),
                "Iteration {$i}: User should not be linked to wedding after removal"
            );

            // Cleanup
            $couple->weddings()->detach();
            User::find($organizerId)?->delete();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Test: Ao remover do casamento atual, contexto do usuário deve apontar para outro casamento disponível.
     * @test
     */
    public function removal_from_current_wedding_moves_user_context_to_another_wedding(): void
    {
        $weddingA = Wedding::create(['title' => 'Wedding A']);
        $weddingB = Wedding::create(['title' => 'Wedding B']);

        $organizer = User::factory()
            ->organizer()
            ->create([
                'current_wedding_id' => $weddingA->id,
            ]);

        $organizer->weddings()->attach($weddingA->id, [
            'role' => 'organizer',
            'permissions' => ['users'],
        ]);
        $organizer->weddings()->attach($weddingB->id, [
            'role' => 'organizer',
            'permissions' => ['users'],
        ]);

        $this->userManagementService->removeFromWedding($organizer, $weddingA);

        $organizer->refresh();

        $this->assertDatabaseHas('users', ['id' => $organizer->id]);
        $this->assertFalse(
            $organizer->weddings()->where('wedding_id', $weddingA->id)->exists()
        );
        $this->assertTrue(
            $organizer->weddings()->where('wedding_id', $weddingB->id)->exists()
        );
        $this->assertSame($weddingB->id, $organizer->current_wedding_id);
    }

    /**
     * Test: Ao remover do último casamento, contexto atual deve ser limpo.
     * @test
     */
    public function removal_from_last_wedding_clears_user_context(): void
    {
        $wedding = Wedding::create(['title' => 'Wedding']);

        $organizer = User::factory()
            ->organizer()
            ->create([
                'current_wedding_id' => $wedding->id,
            ]);

        $organizer->weddings()->attach($wedding->id, [
            'role' => 'organizer',
            'permissions' => ['users'],
        ]);

        $this->userManagementService->removeFromWedding($organizer, $wedding);

        $organizer->refresh();

        $this->assertDatabaseHas('users', ['id' => $organizer->id]);
        $this->assertFalse(
            $organizer->weddings()->where('wedding_id', $wedding->id)->exists()
        );
        $this->assertNull($organizer->current_wedding_id);
    }

    /**
     * Property 7: Guest creation with correct role
     * @test
     */
    public function guest_is_created_with_correct_role_and_linked(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            $guestData = [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
            ];

            $guest = $this->userManagementService->createGuest($couple, $wedding, $guestData);

            // Verify role
            $this->assertEquals(
                'guest',
                $guest->role,
                "Iteration {$i}: Guest should have role 'guest'"
            );

            // Verify linked to wedding
            $pivot = $guest->weddings()
                ->where('wedding_id', $wedding->id)
                ->first()
                ?->pivot;

            $this->assertNotNull($pivot, "Iteration {$i}: Guest should be linked to wedding");
            $this->assertEquals('guest', $pivot->role);
            $this->assertEquals([], $pivot->permissions);

            // Cleanup
            $wedding->users()->detach();
            $guest->delete();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Property 8: Guest creation requires name
     * @test
     */
    public function guest_creation_rejects_empty_name(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        for ($i = 0; $i < 100; $i++) {
            try {
                $this->userManagementService->createGuest($couple, $wedding, [
                    'name' => '',
                    'email' => fake()->unique()->safeEmail(),
                ]);
                $this->fail("Iteration {$i}: Should have thrown ValidationException for empty name");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('name', $e->errors());
            }
        }

        // Cleanup
        $couple->weddings()->detach();
        $couple->delete();
        $wedding->delete();
    }

    /**
     * Property 8: Guest creation requires email
     * @test
     */
    public function guest_creation_rejects_empty_email(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        for ($i = 0; $i < 100; $i++) {
            try {
                $this->userManagementService->createGuest($couple, $wedding, [
                    'name' => fake()->name(),
                    'email' => '',
                ]);
                $this->fail("Iteration {$i}: Should have thrown ValidationException for empty email");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('email', $e->errors());
            }
        }

        // Cleanup
        $couple->weddings()->detach();
        $couple->delete();
        $wedding->delete();
    }

    /**
     * Property 13: Created_by is set correctly
     * @test
     */
    public function created_by_is_set_for_created_users(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            // Create organizer
            $organizer = $this->userManagementService->createOrganizer(
                $couple,
                $wedding,
                [
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                ],
                []
            );

            $this->assertEquals(
                $couple->id,
                $organizer->created_by,
                "Iteration {$i}: Organizer's created_by should be couple's ID"
            );

            // Create guest
            $guest = $this->userManagementService->createGuest($couple, $wedding, [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
            ]);

            $this->assertEquals(
                $couple->id,
                $guest->created_by,
                "Iteration {$i}: Guest's created_by should be couple's ID"
            );

            // Cleanup
            $wedding->users()->detach();
            $organizer->delete();
            $guest->delete();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Test: Couple criado internamente não deve passar pelo onboarding novamente.
     * @test
     */
    public function created_couple_has_onboarding_completed_and_current_wedding_context(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $creator = User::factory()->create(['role' => 'couple']);
            $creator->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            $createdCouple = $this->userManagementService->createCouple($creator, $wedding, [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
            ]);

            $createdCouple->refresh();

            $this->assertTrue(
                $createdCouple->hasCompletedOnboarding(),
                "Iteration {$i}: created couple should have onboarding completed"
            );
            $this->assertSame(
                $wedding->id,
                $createdCouple->current_wedding_id,
                "Iteration {$i}: created couple should have current_wedding_id set"
            );

            $wedding->users()->detach();
            $createdCouple->delete();
            $creator->delete();
            $wedding->delete();
        }
    }

    /**
     * Test: Couple criado via Usuários não deve ser redirecionado ao onboarding.
     * @test
     */
    public function created_couple_can_pass_onboarding_middleware(): void
    {
        $middleware = new EnsureOnboardingComplete();

        for ($i = 0; $i < 50; $i++) {
            $wedding = Wedding::create(['title' => "Wedding Middleware {$i}"]);
            $creator = User::factory()->create(['role' => 'couple']);
            $creator->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            $createdCouple = $this->userManagementService->createCouple($creator, $wedding, [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
            ]);

            $request = Request::create('/admin/dashboard', 'GET');
            $request->setUserResolver(fn () => $createdCouple);
            $request->setRouteResolver(function () {
                $route = new Route('GET', '/admin/dashboard', static fn () => 'ok');
                $route->name('filament.admin.pages.dashboard');
                return $route;
            });

            $response = $middleware->handle(
                $request,
                static fn () => new Response('ok', 200)
            );

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Iteration {$i}: created couple should not be redirected to onboarding"
            );

            $wedding->users()->detach();
            $createdCouple->delete();
            $creator->delete();
            $wedding->delete();
        }
    }

    /**
     * Test: Only couple can create organizers
     * @test
     */
    public function only_couple_can_create_organizers(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        // Organizer cannot create organizer
        $organizer = User::factory()->create(['role' => 'organizer']);
        $organizer->weddings()->attach($wedding->id, ['role' => 'organizer', 'permissions' => ['users']]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->userManagementService->createOrganizer($organizer, $wedding, [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ], []);
    }

    /**
     * Test: Organizer with permission can create guests
     * @test
     */
    public function organizer_with_permission_can_create_guests(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        $organizer = User::factory()->create(['role' => 'organizer']);
        $organizer->weddings()->attach($wedding->id, ['role' => 'organizer', 'permissions' => ['users']]);

        $guest = $this->userManagementService->createGuest($organizer, $wedding, [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ]);

        $this->assertEquals('guest', $guest->role);
        $this->assertEquals($organizer->id, $guest->created_by);
    }

    /**
     * Test: Organizer without permission cannot create guests
     * @test
     */
    public function organizer_without_permission_cannot_create_guests(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        $organizer = User::factory()->create(['role' => 'organizer']);
        $organizer->weddings()->attach($wedding->id, ['role' => 'organizer', 'permissions' => []]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->userManagementService->createGuest($organizer, $wedding, [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    /**
     * Test: Only admin can create admin
     * @test
     */
    public function only_admin_can_create_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $newAdmin = $this->userManagementService->createAdmin($admin, [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => fake()->password(8, 20),
        ]);

        $this->assertEquals('admin', $newAdmin->role);
        $this->assertEquals($admin->id, $newAdmin->created_by);
    }

    /**
     * Test: Non-admin cannot create admin
     * @test
     */
    public function non_admin_cannot_create_admin(): void
    {
        $couple = User::factory()->create(['role' => 'couple']);

        $this->expectException(AccessDeniedHttpException::class);
        $this->userManagementService->createAdmin($couple, [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => fake()->password(8, 20),
        ]);
    }
}
