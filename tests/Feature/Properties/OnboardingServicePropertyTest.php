<?php

namespace Tests\Feature\Properties;

use App\Contracts\OnboardingServiceInterface;
use App\Contracts\PartnerInviteServiceInterface;
use App\Models\GuestEvent;
use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use App\Services\OnboardingService;
use App\Services\PartnerInviteService;
use App\Services\WeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Feature: user-onboarding, Properties 7, 8: Onboarding Service Properties
 * 
 * Property 7: Wedding state before partner acceptance
 * Property 8: Data creation on onboarding completion
 * 
 * Validates: Requirements 3.1.5, 3.2.7, 6.1, 6.2, 6.4, 6.9
 */
class OnboardingServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Bind the service with real dependencies
        $this->app->bind(PartnerInviteServiceInterface::class, PartnerInviteService::class);
        
        $this->service = new OnboardingService(
            new WeddingService(),
            $this->app->make(SiteBuilderServiceInterface::class),
            $this->app->make(PartnerInviteServiceInterface::class)
        );
    }

    /**
     * Property 8: Data creation on onboarding completion
     * 
     * For any valid onboarding data:
     * - A Wedding record should be created
     * - The creator should be linked as couple
     * - A SiteLayout should be created
     * - User should be marked as onboarding complete
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function onboarding_creates_all_required_data(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create();

            $data = [
                'wedding_date' => now()->addMonths(rand(1, 24))->format('Y-m-d'),
                'wedding_time' => sprintf('%02d:%02d', rand(8, 22), rand(0, 1) * 30),
                'plan' => rand(0, 1) ? 'basic' : 'premium',
                'venue_name' => "Venue {$i}",
                'venue_city' => "City {$i}",
                'venue_state' => 'SP',
            ];

            $wedding = $this->service->complete($user, $data);

            // Verify wedding was created
            $this->assertInstanceOf(
                Wedding::class,
                $wedding,
                "Wedding should be created (iteration $i)"
            );
            $this->assertDatabaseHas('weddings', [
                'id' => $wedding->id,
            ]);

            // Verify user is linked as couple
            $user->refresh();
            $this->assertTrue(
                $user->isCoupleIn($wedding),
                "User should be couple in wedding (iteration $i)"
            );

            // Verify site was created
            $site = SiteLayout::withoutGlobalScopes()
                ->where('wedding_id', $wedding->id)
                ->first();
            $this->assertNotNull(
                $site,
                "SiteLayout should be created for wedding (iteration $i)"
            );

            // Verify default RSVP event was created with onboarding datetime
            $event = GuestEvent::withoutGlobalScopes()
                ->where('wedding_id', $wedding->id)
                ->where('slug', 'casamento')
                ->first();
            $this->assertNotNull(
                $event,
                "Default RSVP event should be created for wedding (iteration $i)"
            );
            $this->assertSame(
                'Casamento',
                $event->name,
                "Default RSVP event name should be Casamento (iteration $i)"
            );
            $this->assertSame(
                $data['wedding_date'],
                $event->event_at?->format('Y-m-d'),
                "Default RSVP event date should match onboarding date (iteration $i)"
            );
            $this->assertSame(
                $data['wedding_time'],
                $event->event_at?->format('H:i'),
                "Default RSVP event time should match onboarding time (iteration $i)"
            );

            // Verify onboarding is marked complete
            $this->assertTrue(
                $user->hasCompletedOnboarding(),
                "User should have onboarding completed (iteration $i)"
            );
        }
    }

    /**
     * Property 8 (continued): Onboarding with partner data
     * 
     * When partner data is provided, an invite should be sent.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function onboarding_sends_partner_invite_when_provided(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create();

            $data = [
                'wedding_date' => now()->addMonths(rand(1, 24))->format('Y-m-d'),
                'wedding_time' => '18:00',
                'plan' => 'basic',
                'partner_name' => "Partner {$i}",
                'partner_email' => "partner-{$i}-" . uniqid() . "@example.com",
            ];

            $wedding = $this->service->complete($user, $data);

            // Verify invite was created
            $this->assertDatabaseHas('partner_invites', [
                'wedding_id' => $wedding->id,
                'inviter_id' => $user->id,
                'email' => $data['partner_email'],
                'name' => $data['partner_name'],
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Property 7: Wedding state before partner acceptance
     * 
     * For any wedding created via onboarding with partner invite,
     * the wedding should have only the creator as couple until invite is accepted.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wedding_has_only_creator_as_couple_before_invite_acceptance(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create();

            $data = [
                'wedding_date' => now()->addMonths(rand(1, 24))->format('Y-m-d'),
                'wedding_time' => '18:00',
                'plan' => 'basic',
                'partner_name' => "Partner {$i}",
                'partner_email' => "partner-{$i}-" . uniqid() . "@example.com",
            ];

            $wedding = $this->service->complete($user, $data);

            // Verify only creator is couple
            $coupleMembers = $wedding->couple()->get();
            $this->assertCount(
                1,
                $coupleMembers,
                "Wedding should have exactly 1 couple member before invite acceptance (iteration $i)"
            );
            $this->assertEquals(
                $user->id,
                $coupleMembers->first()->id,
                "The only couple member should be the creator (iteration $i)"
            );
        }
    }

    /**
     * Property 8 (continued): Onboarding without partner data
     * 
     * When no partner data is provided, no invite should be created.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function onboarding_without_partner_creates_no_invite(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create();

            $data = [
                'wedding_date' => now()->addMonths(rand(1, 24))->format('Y-m-d'),
                'wedding_time' => '18:00',
                'plan' => 'basic',
                // No partner data
            ];

            $wedding = $this->service->complete($user, $data);

            // Verify no invite was created
            $this->assertDatabaseMissing('partner_invites', [
                'wedding_id' => $wedding->id,
            ]);
        }
    }

    /**
     * Property 8 (continued): Wedding title generation
     * 
     * Wedding title should be generated based on names.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wedding_title_is_generated_correctly(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create([
                'name' => "Creator Name {$i}",
            ]);

            // Test with partner
            $dataWithPartner = [
                'wedding_date' => now()->addMonths(6)->format('Y-m-d'),
                'wedding_time' => '18:00',
                'plan' => 'basic',
                'partner_name' => "Partner Name {$i}",
                'partner_email' => "partner-{$i}-" . uniqid() . "@example.com",
            ];

            $weddingWithPartner = $this->service->complete($user, $dataWithPartner);
            $this->assertStringContainsString(
                'Creator',
                $weddingWithPartner->title,
                "Wedding title should contain creator's first name (iteration $i)"
            );
            $this->assertStringContainsString(
                'Partner',
                $weddingWithPartner->title,
                "Wedding title should contain partner's first name (iteration $i)"
            );

            // Test without partner
            $user2 = User::factory()->onboardingPending()->create([
                'name' => "Solo Creator {$i}",
            ]);
            $dataWithoutPartner = [
                'wedding_date' => now()->addMonths(6)->format('Y-m-d'),
                'wedding_time' => '18:00',
                'plan' => 'basic',
            ];

            $weddingWithoutPartner = $this->service->complete($user2, $dataWithoutPartner);
            $this->assertStringContainsString(
                'Solo',
                $weddingWithoutPartner->title,
                "Wedding title should contain creator's first name when no partner (iteration $i)"
            );
        }
    }
}
