<?php

namespace Tests\Feature\Properties;

use App\Contracts\PartnerInviteServiceInterface;
use App\Models\PartnerInvite;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\ExistingUserInviteNotification;
use App\Notifications\NewUserInviteNotification;
use App\Services\PartnerInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Feature: user-onboarding, Properties 4, 5, 6: Partner Invite Properties
 * 
 * Property 4: Invite type based on email existence
 * Property 5: Email content contains inviter name
 * Property 6: Partner linking after acceptance
 * 
 * Validates: Requirements 3.1.1, 3.1.2, 3.1.4, 3.2.1, 3.2.3, 3.2.5
 */
class PartnerInvitePropertyTest extends TestCase
{
    use RefreshDatabase;

    private PartnerInviteServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PartnerInviteService();
    }

    /**
     * Property 4: Invite type based on email existence
     * 
     * For any valid partner email:
     * - If email doesn't exist in platform, invite should be for new user
     * - If email exists in platform, invite should be for existing user
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function invite_type_is_determined_by_email_existence(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            // Create wedding and inviter
            $wedding = Wedding::factory()->create();
            $inviter = User::factory()->onboardingCompleted()->create();
            $wedding->users()->attach($inviter->id, ['role' => 'couple', 'permissions' => []]);

            // Test with non-existing email
            $newEmail = "new-user-{$i}-" . uniqid() . "@example.com";
            $invite = $this->service->sendInvite($wedding, $inviter, $newEmail, "New User {$i}");

            $this->assertTrue(
                $invite->isForNewUser(),
                "Invite for non-existing email should be for new user (iteration $i)"
            );
            $this->assertNull(
                $invite->existing_user_id,
                "existing_user_id should be null for new user invite (iteration $i)"
            );

            // Test with existing email
            $existingUser = User::factory()->create();
            $invite2 = $this->service->sendInvite($wedding, $inviter, $existingUser->email, $existingUser->name);

            $this->assertTrue(
                $invite2->isForExistingUser(),
                "Invite for existing email should be for existing user (iteration $i)"
            );
            $this->assertEquals(
                $existingUser->id,
                $invite2->existing_user_id,
                "existing_user_id should match the existing user (iteration $i)"
            );
        }
    }

    /**
     * Property 4 (continued): Existing user with previous wedding
     * 
     * If the existing user is already linked to another wedding as couple,
     * the invite should record the previous wedding.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function invite_records_previous_wedding_for_existing_user(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            // Create existing user with a wedding
            $previousWedding = Wedding::factory()->create();
            $existingUser = User::factory()->onboardingCompleted()->create([
                'current_wedding_id' => $previousWedding->id,
            ]);
            $previousWedding->users()->attach($existingUser->id, ['role' => 'couple', 'permissions' => []]);

            // Create new wedding and inviter
            $newWedding = Wedding::factory()->create();
            $inviter = User::factory()->onboardingCompleted()->create();
            $newWedding->users()->attach($inviter->id, ['role' => 'couple', 'permissions' => []]);

            // Send invite
            $invite = $this->service->sendInvite($newWedding, $inviter, $existingUser->email, $existingUser->name);

            $this->assertEquals(
                $previousWedding->id,
                $invite->previous_wedding_id,
                "previous_wedding_id should be set for user with existing wedding (iteration $i)"
            );
        }
    }

    /**
     * Property 5: Email content contains inviter name
     * 
     * For any invite sent, the notification should contain the inviter's name.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function invite_notification_contains_inviter_name(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $wedding = Wedding::factory()->create();
            $inviter = User::factory()->onboardingCompleted()->create([
                'name' => "Inviter Name {$i}",
            ]);
            $wedding->users()->attach($inviter->id, ['role' => 'couple', 'permissions' => []]);

            // Test new user notification
            $newEmail = "new-{$i}-" . uniqid() . "@example.com";
            $this->service->sendInvite($wedding, $inviter, $newEmail, "Partner {$i}");

            Notification::assertSentOnDemand(
                NewUserInviteNotification::class,
                function ($notification) use ($inviter) {
                    return $notification->inviter->name === $inviter->name;
                }
            );

            // Test existing user notification
            $existingUser = User::factory()->create();
            $this->service->sendInvite($wedding, $inviter, $existingUser->email, $existingUser->name);

            Notification::assertSentTo(
                $existingUser,
                ExistingUserInviteNotification::class,
                function ($notification) use ($inviter) {
                    return $notification->inviter->name === $inviter->name;
                }
            );
        }
    }

    /**
     * Property 6: Partner linking after acceptance
     * 
     * For any accepted invite:
     * - User should be linked to wedding as couple
     * - If user had previous wedding, should be unlinked from it
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partner_is_linked_after_accepting_invite(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            // Create wedding and inviter
            $wedding = Wedding::factory()->create();
            $inviter = User::factory()->onboardingCompleted()->create();
            $wedding->users()->attach($inviter->id, ['role' => 'couple', 'permissions' => []]);

            // Create new user to accept invite
            $newUser = User::factory()->create([
                'email' => "accept-{$i}-" . uniqid() . "@example.com",
            ]);

            // Create and accept invite
            $invite = PartnerInvite::factory()->forWedding($wedding, $inviter)->create([
                'email' => $newUser->email,
                'name' => $newUser->name,
            ]);

            $this->service->acceptInvite($invite, $newUser);

            // Verify user is linked to wedding as couple
            $newUser->refresh();
            $this->assertTrue(
                $newUser->isCoupleIn($wedding),
                "User should be couple in wedding after accepting invite (iteration $i)"
            );
            $this->assertEquals(
                $wedding->id,
                $newUser->current_wedding_id,
                "User's current wedding should be updated (iteration $i)"
            );
            $this->assertTrue(
                $newUser->hasCompletedOnboarding(),
                "User should have onboarding completed after accepting invite (iteration $i)"
            );
        }
    }

    /**
     * Property 6 (continued): Previous wedding unlinking
     * 
     * If user was linked to another wedding, they should be unlinked after accepting.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function previous_wedding_is_unlinked_after_accepting_invite(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            // Create existing user with previous wedding
            $previousWedding = Wedding::factory()->create();
            $existingUser = User::factory()->onboardingCompleted()->create([
                'current_wedding_id' => $previousWedding->id,
            ]);
            $previousWedding->users()->attach($existingUser->id, ['role' => 'couple', 'permissions' => []]);

            // Create new wedding and inviter
            $newWedding = Wedding::factory()->create();
            $inviter = User::factory()->onboardingCompleted()->create();
            $newWedding->users()->attach($inviter->id, ['role' => 'couple', 'permissions' => []]);

            // Create invite with previous wedding recorded
            $invite = PartnerInvite::factory()->forWedding($newWedding, $inviter)->create([
                'email' => $existingUser->email,
                'name' => $existingUser->name,
                'existing_user_id' => $existingUser->id,
                'previous_wedding_id' => $previousWedding->id,
            ]);

            // Accept invite
            $this->service->acceptInvite($invite, $existingUser);

            // Verify user is unlinked from previous wedding
            $existingUser->refresh();
            $this->assertFalse(
                $existingUser->weddings()->where('wedding_id', $previousWedding->id)->exists(),
                "User should be unlinked from previous wedding (iteration $i)"
            );
            $this->assertTrue(
                $existingUser->isCoupleIn($newWedding),
                "User should be couple in new wedding (iteration $i)"
            );
        }
    }
}
