<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Services\UserRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Feature: user-role-management, Property 1, 3, 4: User Registration Properties
 * 
 * Property 1: For any valid registration data (name, email, password), the created user must have role "couple".
 * Property 3: For any registration attempt with empty name, email, or password, the system must reject with validation error.
 * Property 4: For any email already in the system, a registration attempt with the same email must return validation error.
 * 
 * Validates: Requirements 1.1, 1.4, 1.5
 */
class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected UserRegistrationService $registrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrationService = new UserRegistrationService();
    }

    /**
     * Property 1: Registration always creates users with role "couple"
     * @test
     */
    public function registration_always_creates_couple_role(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $data = [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => fake()->password(8, 20),
            ];

            $user = $this->registrationService->registerCouple($data);

            $this->assertEquals(
                'couple',
                $user->role,
                "Iteration {$i}: User should have role 'couple', got '{$user->role}'"
            );

            $this->assertEquals($data['name'], $user->name);
            $this->assertEquals($data['email'], $user->email);
            $this->assertNotNull($user->id);
        }
    }

    /**
     * Property 3: Empty name field must be rejected
     * @test
     */
    public function registration_rejects_empty_name(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $data = [
                'name' => '',
                'email' => fake()->unique()->safeEmail(),
                'password' => fake()->password(8, 20),
            ];

            try {
                $this->registrationService->registerCouple($data);
                $this->fail("Iteration {$i}: Should have thrown ValidationException for empty name");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('name', $e->errors());
            }
        }
    }

    /**
     * Property 3: Empty email field must be rejected
     * @test
     */
    public function registration_rejects_empty_email(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $data = [
                'name' => fake()->name(),
                'email' => '',
                'password' => fake()->password(8, 20),
            ];

            try {
                $this->registrationService->registerCouple($data);
                $this->fail("Iteration {$i}: Should have thrown ValidationException for empty email");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('email', $e->errors());
            }
        }
    }

    /**
     * Property 3: Empty password field must be rejected
     * @test
     */
    public function registration_rejects_empty_password(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $data = [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => '',
            ];

            try {
                $this->registrationService->registerCouple($data);
                $this->fail("Iteration {$i}: Should have thrown ValidationException for empty password");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('password', $e->errors());
            }
        }
    }

    /**
     * Property 4: Duplicate email must be rejected
     * @test
     */
    public function registration_rejects_duplicate_email(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $email = fake()->unique()->safeEmail();

            // Create first user
            User::factory()->create(['email' => $email]);

            // Try to register with same email
            $data = [
                'name' => fake()->name(),
                'email' => $email,
                'password' => fake()->password(8, 20),
            ];

            try {
                $this->registrationService->registerCouple($data);
                $this->fail("Iteration {$i}: Should have thrown ValidationException for duplicate email");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('email', $e->errors());
                $this->assertStringContainsString('já está em uso', $e->errors()['email'][0]);
            }
        }
    }
}
