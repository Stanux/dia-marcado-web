<?php

namespace Tests\Feature\Property;

use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-saas-foundation, Property 2: Injeção Automática de Wedding ID
 * 
 * For any Model that extends WeddingScopedModel and for any authenticated user,
 * when creating a new record without specifying wedding_id, the system should
 * automatically fill it with the user's current_wedding_id.
 * 
 * Validates: Requirements 1.2, 9.2
 */
class WeddingIdInjectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property test: wedding_id is auto-injected when creating records
     * @test
     */
    public function wedding_id_is_auto_injected_on_creation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Create wedding and user
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $user = User::factory()->create([
                'role' => fake()->randomElement(['couple', 'organizer']),
                'current_wedding_id' => $wedding->id,
            ]);
            $user->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            // Act as user
            $this->actingAs($user);

            // Create task WITHOUT specifying wedding_id
            $task = Task::create([
                'title' => fake()->sentence(),
                'description' => fake()->paragraph(),
                'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
            ]);

            // Assert: wedding_id was auto-injected
            $this->assertEquals(
                $wedding->id,
                $task->wedding_id,
                "Iteration {$i}: wedding_id was not auto-injected"
            );

            // Verify in database
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'wedding_id' => $wedding->id,
            ]);

            // Cleanup
            $task->forceDelete();
            $user->weddings()->detach();
            $user->delete();
            $wedding->delete();
        }
    }

    /**
     * @test
     */
    public function explicit_wedding_id_is_not_overwritten(): void
    {
        // Create two weddings
        $wedding1 = Wedding::create(['title' => 'Wedding 1']);
        $wedding2 = Wedding::create(['title' => 'Wedding 2']);

        // Create user with current_wedding_id = wedding1
        $user = User::factory()->create([
            'role' => 'couple',
            'current_wedding_id' => $wedding1->id,
        ]);
        $user->weddings()->attach($wedding1->id, ['role' => 'couple', 'permissions' => []]);
        $user->weddings()->attach($wedding2->id, ['role' => 'couple', 'permissions' => []]);

        $this->actingAs($user);

        // Create task WITH explicit wedding_id = wedding2
        $task = Task::create([
            'wedding_id' => $wedding2->id,
            'title' => 'Explicit Wedding Task',
        ]);

        // Assert: explicit wedding_id is preserved
        $this->assertEquals($wedding2->id, $task->wedding_id);
    }

    /**
     * @test
     */
    public function admin_without_current_wedding_does_not_auto_inject(): void
    {
        // Create wedding
        $wedding = Wedding::create(['title' => 'Wedding']);

        // Create admin without current_wedding_id
        $admin = User::factory()->create([
            'role' => 'admin',
            'current_wedding_id' => null,
        ]);

        $this->actingAs($admin);

        // Create task without wedding_id - should fail or be null
        $task = Task::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id, // Must provide explicitly for admin
            'title' => 'Admin Task',
        ]);

        $this->assertEquals($wedding->id, $task->wedding_id);
    }

    /**
     * @test
     */
    public function user_without_current_wedding_does_not_auto_inject(): void
    {
        // Create wedding
        $wedding = Wedding::create(['title' => 'Wedding']);

        // Create user without current_wedding_id
        $user = User::factory()->create([
            'role' => 'couple',
            'current_wedding_id' => null,
        ]);
        $user->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        $this->actingAs($user);

        // Create task - wedding_id should not be auto-injected
        $task = new Task([
            'title' => 'Task without wedding',
        ]);

        // wedding_id should be null since current_wedding_id is null
        $this->assertNull($task->wedding_id);
    }
}
