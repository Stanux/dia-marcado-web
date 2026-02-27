<?php

namespace Tests\Feature\Properties;

use App\Contracts\OnboardingServiceInterface;
use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Models\GuestEvent;
use App\Models\SiteLayout;
use App\Models\User;
use App\Models\Wedding;
use App\Services\OnboardingService;
use App\Services\WeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Feature: user-onboarding, Properties 7, 8: Onboarding Service Properties
 *
 * Property 7: Wedding state after onboarding completion
 * Property 8: Data creation on onboarding completion
 */
class OnboardingServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OnboardingService(
            new WeddingService(),
            $this->app->make(SiteBuilderServiceInterface::class)
        );
    }

    /**
     * Property 8: Data creation on onboarding completion.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function onboarding_creates_all_required_data(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create();

            $data = [
                'wedding_date' => now()->addMonths(rand(1, 24))->format('Y-m-d'),
                'plan' => rand(0, 1) ? 'basic' : 'premium',
                'venue_name' => "Venue {$i}",
                'venue_city' => "City {$i}",
                'venue_state' => 'SP',
            ];

            $wedding = $this->service->complete($user, $data);

            $this->assertInstanceOf(Wedding::class, $wedding, "Wedding should be created (iteration $i)");
            $this->assertDatabaseHas('weddings', ['id' => $wedding->id]);

            $user->refresh();
            $this->assertTrue(
                $user->isCoupleIn($wedding),
                "User should be couple in wedding (iteration $i)"
            );

            $site = SiteLayout::withoutGlobalScopes()
                ->where('wedding_id', $wedding->id)
                ->first();
            $this->assertNotNull($site, "SiteLayout should be created for wedding (iteration $i)");

            $event = GuestEvent::withoutGlobalScopes()
                ->where('wedding_id', $wedding->id)
                ->where('slug', 'casamento')
                ->first();
            $this->assertNotNull($event, "Default RSVP event should be created for wedding (iteration $i)");
            $this->assertSame('Casamento', $event->name, "Default RSVP event name should be Casamento (iteration $i)");
            $this->assertNull(
                $event->event_at,
                "Default RSVP event datetime should be null after onboarding (iteration $i)"
            );

            $this->assertTrue(
                $user->hasCompletedOnboarding(),
                "User should have onboarding completed (iteration $i)"
            );
        }
    }

    /**
     * Property 7: Wedding starts with only one couple member even when partner draft exists.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wedding_has_only_creator_as_couple_after_onboarding_with_partner_name_draft(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create();

            $data = [
                'wedding_date' => now()->addMonths(rand(1, 24))->format('Y-m-d'),
                'plan' => 'basic',
                'partner_name' => "Partner {$i}",
            ];

            $wedding = $this->service->complete($user, $data);

            $coupleMembers = $wedding->couple()->get();
            $this->assertCount(
                1,
                $coupleMembers,
                "Wedding should have exactly 1 couple member after onboarding (iteration $i)"
            );
            $this->assertEquals(
                $user->id,
                $coupleMembers->first()->id,
                "The only couple member should be the creator (iteration $i)"
            );

            $this->assertDatabaseMissing('partner_invites', [
                'wedding_id' => $wedding->id,
            ]);
        }
    }

    /**
     * Property 8: Wedding title generation.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wedding_title_is_generated_correctly(): void
    {
        Notification::fake();

        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->onboardingPending()->create([
                'name' => "Creator Name {$i}",
            ]);

            $dataWithPartnerDraft = [
                'wedding_date' => now()->addMonths(6)->format('Y-m-d'),
                'plan' => 'basic',
                'partner_name' => "Partner Name {$i}",
            ];

            $weddingWithPartnerDraft = $this->service->complete($user, $dataWithPartnerDraft);
            $this->assertStringContainsString(
                'Creator',
                $weddingWithPartnerDraft->title,
                "Wedding title should contain creator first name (iteration $i)"
            );
            $this->assertStringContainsString(
                'Partner',
                $weddingWithPartnerDraft->title,
                "Wedding title should contain partner first name (iteration $i)"
            );

            $user2 = User::factory()->onboardingPending()->create([
                'name' => "Solo Creator {$i}",
            ]);
            $dataWithoutPartner = [
                'wedding_date' => now()->addMonths(6)->format('Y-m-d'),
                'plan' => 'basic',
            ];

            $weddingWithoutPartner = $this->service->complete($user2, $dataWithoutPartner);
            $this->assertStringContainsString(
                'Solo',
                $weddingWithoutPartner->title,
                "Wedding title should contain creator first name when no partner (iteration $i)"
            );
        }
    }

    /**
     * @test
     */
    public function onboarding_updates_creator_name_and_partner_name_draft_when_provided(): void
    {
        Notification::fake();

        $user = User::factory()->onboardingPending()->create([
            'name' => 'Nome Antigo',
        ]);

        $wedding = $this->service->complete($user, [
            'creator_name' => 'Nome Corrigido',
            'partner_name' => 'Nome Rascunho',
            'wedding_date' => now()->addMonths(4)->format('Y-m-d'),
            'plan' => 'basic',
        ]);

        $user->refresh();
        $wedding->refresh();

        $this->assertSame('Nome Corrigido', $user->name);
        $this->assertSame('Nome Rascunho', $wedding->settings['partner_name_draft'] ?? null);
    }

    /**
     * @test
     */
    public function onboarding_with_empty_wedding_date_persists_null_date(): void
    {
        Notification::fake();

        $user = User::factory()->onboardingPending()->create();

        $wedding = $this->service->complete($user, [
            'wedding_date' => '',
            'plan' => 'basic',
        ]);

        $wedding->refresh();

        $this->assertNull($wedding->wedding_date);
    }
}
