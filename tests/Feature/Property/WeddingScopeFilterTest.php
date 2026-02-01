<?php

namespace Tests\Feature\Property;

use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: wedding-saas-foundation, Property 1: Filtro Automático por Wedding ID
 * 
 * For any Model that extends WeddingScopedModel and for any non-Admin authenticated user,
 * all queries executed on that Model should return only records where wedding_id
 * belongs to the user's weddings.
 * 
 * Validates: Requirements 1.1, 1.3
 */
class WeddingScopeFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property test: Non-admin users only see their wedding's data
     * @test
     */
    public function non_admin_users_only_see_their_wedding_data(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Create two weddings
            $wedding1 = Wedding::create(['title' => "Wedding 1 - {$i}"]);
            $wedding2 = Wedding::create(['title' => "Wedding 2 - {$i}"]);

            // Create user with access to wedding1 only
            $user = User::factory()->create(['role' => 'couple']);
            $user->weddings()->attach($wedding1->id, ['role' => 'couple', 'permissions' => []]);

            // Create tasks in both weddings
            $tasksWedding1 = rand(1, 5);
            $tasksWedding2 = rand(1, 5);

            for ($j = 0; $j < $tasksWedding1; $j++) {
                Task::withoutGlobalScopes()->create([
                    'wedding_id' => $wedding1->id,
                    'title' => "Task W1-{$j}",
                ]);
            }

            for ($j = 0; $j < $tasksWedding2; $j++) {
                Task::withoutGlobalScopes()->create([
                    'wedding_id' => $wedding2->id,
                    'title' => "Task W2-{$j}",
                ]);
            }

            // Act as user and query tasks
            $this->actingAs($user);
            $visibleTasks = Task::all();

            // Assert: user only sees tasks from their wedding
            $this->assertCount(
                $tasksWedding1,
                $visibleTasks,
                "Iteration {$i}: Expected {$tasksWedding1} tasks, got {$visibleTasks->count()}"
            );

            foreach ($visibleTasks as $task) {
                $this->assertEquals(
                    $wedding1->id,
                    $task->wedding_id,
                    "Iteration {$i}: Task belongs to wrong wedding"
                );
            }

            // Cleanup for next iteration
            Task::withoutGlobalScopes()->delete();
            $user->weddings()->detach();
            $user->delete();
            $wedding1->delete();
            $wedding2->delete();
        }
    }

    /**
     * @test
     */
    public function admin_users_can_see_all_wedding_data(): void
    {
        // Create multiple weddings with tasks
        $weddings = [];
        $totalTasks = 0;

        for ($i = 0; $i < 3; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $weddings[] = $wedding;

            $taskCount = rand(2, 5);
            $totalTasks += $taskCount;

            for ($j = 0; $j < $taskCount; $j++) {
                Task::withoutGlobalScopes()->create([
                    'wedding_id' => $wedding->id,
                    'title' => "Task {$i}-{$j}",
                ]);
            }
        }

        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);

        // Act as admin and query tasks
        $this->actingAs($admin);
        $visibleTasks = Task::all();

        // Assert: admin sees all tasks
        $this->assertCount($totalTasks, $visibleTasks);
    }

    /**
     * @test
     */
    public function user_with_multiple_weddings_sees_all_their_wedding_data(): void
    {
        // Create three weddings
        $wedding1 = Wedding::create(['title' => 'Wedding 1']);
        $wedding2 = Wedding::create(['title' => 'Wedding 2']);
        $wedding3 = Wedding::create(['title' => 'Wedding 3']);

        // Create user with access to wedding1 and wedding2
        $user = User::factory()->create(['role' => 'organizer']);
        $user->weddings()->attach($wedding1->id, ['role' => 'organizer', 'permissions' => ['tasks']]);
        $user->weddings()->attach($wedding2->id, ['role' => 'organizer', 'permissions' => ['tasks']]);

        // Create tasks in all weddings
        Task::withoutGlobalScopes()->create(['wedding_id' => $wedding1->id, 'title' => 'Task 1']);
        Task::withoutGlobalScopes()->create(['wedding_id' => $wedding1->id, 'title' => 'Task 2']);
        Task::withoutGlobalScopes()->create(['wedding_id' => $wedding2->id, 'title' => 'Task 3']);
        Task::withoutGlobalScopes()->create(['wedding_id' => $wedding3->id, 'title' => 'Task 4']);

        // Act as user
        $this->actingAs($user);
        $visibleTasks = Task::all();

        // Assert: user sees tasks from wedding1 and wedding2 only
        $this->assertCount(3, $visibleTasks);
        
        $visibleWeddingIds = $visibleTasks->pluck('wedding_id')->unique()->toArray();
        $this->assertContains($wedding1->id, $visibleWeddingIds);
        $this->assertContains($wedding2->id, $visibleWeddingIds);
        $this->assertNotContains($wedding3->id, $visibleWeddingIds);
    }

    /**
     * @test
     */
    public function user_without_weddings_sees_no_data(): void
    {
        // Create wedding with tasks
        $wedding = Wedding::create(['title' => 'Wedding']);
        Task::withoutGlobalScopes()->create(['wedding_id' => $wedding->id, 'title' => 'Task 1']);

        // Create user without any wedding access
        $user = User::factory()->create(['role' => 'guest']);

        // Act as user
        $this->actingAs($user);
        $visibleTasks = Task::all();

        // Assert: user sees no tasks
        $this->assertCount(0, $visibleTasks);
    }
}
