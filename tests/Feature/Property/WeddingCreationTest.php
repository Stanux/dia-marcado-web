<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Services\WeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: user-role-management, Property 2: Noivos Vinculado ao Casamento Criado
 * 
 * For any user of type Noivos who creates a wedding, the system must automatically
 * create a link in the pivot table with role "couple".
 * 
 * Validates: Requirements 1.2, 1.3
 */
class WeddingCreationTest extends TestCase
{
    use RefreshDatabase;

    protected WeddingService $weddingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weddingService = new WeddingService();
    }

    /**
     * Property 2: Creator is automatically linked as couple
     * @test
     */
    public function creator_is_automatically_linked_as_couple(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $creator = User::factory()->create(['role' => 'couple']);

            $weddingData = [
                'title' => fake()->sentence(3),
                'wedding_date' => fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
            ];

            $wedding = $this->weddingService->createWedding($creator, $weddingData);

            // Verify wedding was created
            $this->assertNotNull($wedding->id);
            $this->assertEquals($weddingData['title'], $wedding->title);

            // Verify creator is linked as couple
            $pivot = $creator->weddings()
                ->where('wedding_id', $wedding->id)
                ->first()
                ?->pivot;

            $this->assertNotNull(
                $pivot,
                "Iteration {$i}: Creator should be linked to wedding"
            );

            $this->assertEquals(
                'couple',
                $pivot->role,
                "Iteration {$i}: Creator should have role 'couple' in pivot"
            );

            // Verify current_wedding_id is set
            $creator->refresh();
            $this->assertEquals(
                $wedding->id,
                $creator->current_wedding_id,
                "Iteration {$i}: Creator's current_wedding_id should be set"
            );

            // Cleanup
            $wedding->users()->detach();
            $wedding->delete();
            $creator->delete();
        }
    }

    /**
     * Property 2: Wedding has exactly one couple member after creation
     * @test
     */
    public function wedding_has_one_couple_after_creation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $creator = User::factory()->create(['role' => 'couple']);

            $wedding = $this->weddingService->createWedding($creator, [
                'title' => fake()->sentence(3),
            ]);

            $coupleCount = $wedding->couple()->count();

            $this->assertEquals(
                1,
                $coupleCount,
                "Iteration {$i}: Wedding should have exactly 1 couple member, got {$coupleCount}"
            );

            // Cleanup
            $wedding->users()->detach();
            $wedding->delete();
            $creator->delete();
        }
    }

    /**
     * Property 2: Creator can access the wedding they created
     * @test
     */
    public function creator_can_access_created_wedding(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $creator = User::factory()->create(['role' => 'couple']);

            $wedding = $this->weddingService->createWedding($creator, [
                'title' => fake()->sentence(3),
            ]);

            $this->assertTrue(
                $creator->hasAccessTo($wedding),
                "Iteration {$i}: Creator should have access to their wedding"
            );

            $this->assertTrue(
                $creator->isCoupleIn($wedding),
                "Iteration {$i}: Creator should be couple in their wedding"
            );

            // Cleanup
            $wedding->users()->detach();
            $wedding->delete();
            $creator->delete();
        }
    }
}
