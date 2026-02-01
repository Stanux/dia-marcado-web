<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Models\Wedding;
use App\Services\UserListingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: user-role-management, Properties 14, 15, 16, 17: User Listing Properties
 * 
 * Property 14: For any wedding, the user listing must return all linked users.
 * Property 15: For any type filter, results must contain only users of that type. Organizers only see guests.
 * Property 16: For any search term, results must contain only users whose name or email match.
 * Property 17: For any sort order, results must be in the specified order.
 * 
 * Validates: Requirements 7.1, 7.2, 7.3, 7.5, 7.6
 */
class UserListingTest extends TestCase
{
    use RefreshDatabase;

    protected UserListingService $listingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listingService = new UserListingService();
    }

    /**
     * Property 14: Listing returns all wedding users
     * @test
     */
    public function listing_returns_all_wedding_users(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            // Create random number of users
            $userCount = rand(1, 5);
            $createdUsers = [$couple];

            for ($j = 0; $j < $userCount; $j++) {
                $role = fake()->randomElement(['organizer', 'guest']);
                $user = User::factory()->create(['role' => $role]);
                $user->weddings()->attach($wedding->id, ['role' => $role, 'permissions' => []]);
                $createdUsers[] = $user;
            }

            $result = $this->listingService->getAllUsers($couple, $wedding);

            $this->assertCount(
                count($createdUsers),
                $result,
                "Iteration {$i}: Should return all {$userCount} + 1 users"
            );

            // Cleanup
            foreach ($createdUsers as $user) {
                $user->weddings()->detach();
                $user->delete();
            }
            $wedding->delete();
        }
    }

    /**
     * Property 15: Type filter returns only matching users
     * @test
     */
    public function type_filter_returns_only_matching_users(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            // Create organizers and guests
            $organizerCount = rand(1, 3);
            $guestCount = rand(1, 3);

            for ($j = 0; $j < $organizerCount; $j++) {
                $organizer = User::factory()->create(['role' => 'organizer']);
                $organizer->weddings()->attach($wedding->id, ['role' => 'organizer', 'permissions' => []]);
            }

            for ($j = 0; $j < $guestCount; $j++) {
                $guest = User::factory()->create(['role' => 'guest']);
                $guest->weddings()->attach($wedding->id, ['role' => 'guest', 'permissions' => []]);
            }

            // Filter by organizer
            $organizers = $this->listingService->getAllUsers($couple, $wedding, ['type' => 'organizer']);
            $this->assertCount($organizerCount, $organizers);
            foreach ($organizers as $user) {
                $this->assertEquals('organizer', $user->pivot->role);
            }

            // Filter by guest
            $guests = $this->listingService->getAllUsers($couple, $wedding, ['type' => 'guest']);
            $this->assertCount($guestCount, $guests);
            foreach ($guests as $user) {
                $this->assertEquals('guest', $user->pivot->role);
            }

            // Cleanup
            $wedding->users()->detach();
            User::whereIn('role', ['organizer', 'guest'])->delete();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Property 15: Organizer only sees guests
     * @test
     */
    public function organizer_only_sees_guests(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $wedding = Wedding::create(['title' => "Wedding {$i}"]);
            $couple = User::factory()->create(['role' => 'couple']);
            $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

            // Create organizer viewer
            $viewerOrganizer = User::factory()->create(['role' => 'organizer']);
            $viewerOrganizer->weddings()->attach($wedding->id, ['role' => 'organizer', 'permissions' => ['users']]);

            // Create other organizers and guests
            $otherOrganizer = User::factory()->create(['role' => 'organizer']);
            $otherOrganizer->weddings()->attach($wedding->id, ['role' => 'organizer', 'permissions' => []]);

            $guestCount = rand(1, 3);
            for ($j = 0; $j < $guestCount; $j++) {
                $guest = User::factory()->create(['role' => 'guest']);
                $guest->weddings()->attach($wedding->id, ['role' => 'guest', 'permissions' => []]);
            }

            // Organizer should only see guests
            $result = $this->listingService->getAllUsers($viewerOrganizer, $wedding);

            $this->assertCount($guestCount, $result, "Iteration {$i}: Organizer should only see guests");
            foreach ($result as $user) {
                $this->assertEquals('guest', $user->pivot->role);
            }

            // Cleanup
            $wedding->users()->detach();
            User::whereIn('role', ['organizer', 'guest'])->delete();
            $couple->delete();
            $wedding->delete();
        }
    }

    /**
     * Property 16: Search returns matching users
     * @test
     */
    public function search_returns_matching_users(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple', 'name' => 'Couple User']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        // Create users with specific names
        $searchableNames = ['John Smith', 'Jane Doe', 'Bob Johnson', 'Alice Williams'];
        foreach ($searchableNames as $name) {
            $user = User::factory()->create(['role' => 'guest', 'name' => $name]);
            $user->weddings()->attach($wedding->id, ['role' => 'guest', 'permissions' => []]);
        }

        for ($i = 0; $i < 50; $i++) {
            $searchTerm = fake()->randomElement(['John', 'Jane', 'Bob', 'Alice', 'Smith', 'Doe']);

            $result = $this->listingService->getAllUsers($couple, $wedding, ['search' => $searchTerm]);

            foreach ($result as $user) {
                $matchesName = stripos($user->name, $searchTerm) !== false;
                $matchesEmail = stripos($user->email, $searchTerm) !== false;

                $this->assertTrue(
                    $matchesName || $matchesEmail,
                    "Iteration {$i}: User '{$user->name}' should match search term '{$searchTerm}'"
                );
            }
        }
    }

    /**
     * Property 17: Sort by name works correctly
     * @test
     */
    public function sort_by_name_works_correctly(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple', 'name' => 'ZZZ Couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        // Create users with specific names
        $names = ['Charlie', 'Alice', 'Bob', 'David'];
        foreach ($names as $name) {
            $user = User::factory()->create(['role' => 'guest', 'name' => $name]);
            $user->weddings()->attach($wedding->id, ['role' => 'guest', 'permissions' => []]);
        }

        // Sort ascending
        $resultAsc = $this->listingService->getAllUsers($couple, $wedding, [
            'sort' => 'name',
            'direction' => 'asc',
        ]);

        $previousName = '';
        foreach ($resultAsc as $user) {
            $this->assertGreaterThanOrEqual(
                0,
                strcasecmp($user->name, $previousName),
                "Users should be sorted by name ascending"
            );
            $previousName = $user->name;
        }

        // Sort descending
        $resultDesc = $this->listingService->getAllUsers($couple, $wedding, [
            'sort' => 'name',
            'direction' => 'desc',
        ]);

        $previousName = 'ZZZZZ';
        foreach ($resultDesc as $user) {
            $this->assertLessThanOrEqual(
                0,
                strcasecmp($user->name, $previousName),
                "Users should be sorted by name descending"
            );
            $previousName = $user->name;
        }
    }

    /**
     * Property 17: Sort by created_at works correctly
     * @test
     */
    public function sort_by_created_at_works_correctly(): void
    {
        $wedding = Wedding::create(['title' => 'Test Wedding']);
        $couple = User::factory()->create(['role' => 'couple']);
        $couple->weddings()->attach($wedding->id, ['role' => 'couple', 'permissions' => []]);

        // Create users with delays
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create(['role' => 'guest']);
            $user->weddings()->attach($wedding->id, ['role' => 'guest', 'permissions' => []]);
        }

        // Sort descending (newest first)
        $resultDesc = $this->listingService->getAllUsers($couple, $wedding, [
            'sort' => 'created_at',
            'direction' => 'desc',
        ]);

        $previousDate = now()->addYear();
        foreach ($resultDesc as $user) {
            $this->assertTrue(
                $user->created_at->lessThanOrEqualTo($previousDate),
                "Users should be sorted by created_at descending"
            );
            $previousDate = $user->created_at;
        }

        // Sort ascending (oldest first)
        $resultAsc = $this->listingService->getAllUsers($couple, $wedding, [
            'sort' => 'created_at',
            'direction' => 'asc',
        ]);

        $previousDate = now()->subYear();
        foreach ($resultAsc as $user) {
            $this->assertTrue(
                $user->created_at->greaterThanOrEqualTo($previousDate),
                "Users should be sorted by created_at ascending"
            );
            $previousDate = $user->created_at;
        }
    }
}
