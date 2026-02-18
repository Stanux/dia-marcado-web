<?php

namespace Tests\Unit\Services\Guests;

use App\Models\Guest;
use App\Models\GuestCheckin;
use App\Models\GuestEvent;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\GuestRsvp;
use App\Models\Wedding;
use App\Services\Guests\GuestOperationalMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestOperationalMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private GuestOperationalMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GuestOperationalMetricsService::class);
    }

    #[Test]
    public function it_calculates_invite_metrics_and_channel_breakdown(): void
    {
        $wedding = Wedding::factory()->create();
        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Família Silva',
        ]);

        $guest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado 1',
            'email' => 'guest1@example.com',
            'status' => 'pending',
            'overall_rsvp_status' => 'no_response',
        ]);

        GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
            'uses_count' => 0,
            'max_uses' => 1,
            'expires_at' => now()->addDays(5),
            'created_at' => now()->subDays(2),
        ]);

        GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'opened',
            'uses_count' => 1,
            'max_uses' => 1,
            'used_at' => now()->subDay(),
            'expires_at' => now()->addDays(5),
            'created_at' => now()->subDays(2),
        ]);

        GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'whatsapp',
            'status' => 'revoked',
            'revoked_at' => now()->subHour(),
            'uses_count' => 0,
            'created_at' => now()->subDays(2),
        ]);

        GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'sms',
            'status' => 'sent',
            'uses_count' => 0,
            'expires_at' => now()->subHour(),
            'created_at' => now()->subDays(2),
        ]);

        $dashboard = $this->service->forWedding($wedding->id, '30d');
        $metrics = $dashboard['metrics'];
        $channelBreakdown = collect($dashboard['channel_breakdown'])->keyBy('channel');
        $forecastInsights = collect($dashboard['forecast_insights']);

        $this->assertSame(4, $metrics['total_invites']);
        $this->assertSame(1, $metrics['opened_invites']);
        $this->assertSame(25.0, $metrics['open_rate']);
        $this->assertSame(1, $metrics['active_invites']);
        $this->assertSame(1, $metrics['revoked_invites']);
        $this->assertSame(1, $metrics['expired_invites']);
        $this->assertSame(1, $metrics['pending_interaction']);
        $this->assertSame(1, $metrics['total_invite_uses']);
        $this->assertSame(0, $metrics['total_checkins']);

        $this->assertSame(2, $channelBreakdown['email']['total']);
        $this->assertSame(1, $channelBreakdown['email']['opened']);
        $this->assertSame(50.0, $channelBreakdown['email']['open_rate']);
        $this->assertCount(3, $forecastInsights);
        $this->assertSame('Cobertura de respostas', $forecastInsights[0]['title']);
    }

    #[Test]
    public function it_calculates_guest_and_event_metrics(): void
    {
        $wedding = Wedding::factory()->create();
        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Família Souza',
        ]);

        $confirmedGuest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Confirmado',
            'status' => 'confirmed',
            'overall_rsvp_status' => 'confirmed',
        ]);

        $declinedGuest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Recusado',
            'status' => 'declined',
            'overall_rsvp_status' => 'declined',
        ]);

        $maybeGuest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Talvez',
            'status' => 'maybe',
            'overall_rsvp_status' => 'maybe',
        ]);

        $pendingGuest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Sem resposta',
            'status' => 'pending',
            'overall_rsvp_status' => 'no_response',
        ]);

        $eventA = GuestEvent::create([
            'wedding_id' => $wedding->id,
            'name' => 'Cerimônia',
            'slug' => 'cerimonia',
            'is_active' => true,
        ]);

        GuestEvent::create([
            'wedding_id' => $wedding->id,
            'name' => 'Jantar',
            'slug' => 'jantar',
            'is_active' => false,
        ]);

        GuestRsvp::create([
            'guest_id' => $confirmedGuest->id,
            'event_id' => $eventA->id,
            'status' => 'confirmed',
            'responded_at' => now()->subDay(),
        ]);

        GuestRsvp::create([
            'guest_id' => $declinedGuest->id,
            'event_id' => $eventA->id,
            'status' => 'declined',
            'responded_at' => now()->subDay(),
        ]);

        GuestRsvp::create([
            'guest_id' => $maybeGuest->id,
            'event_id' => $eventA->id,
            'status' => 'maybe',
            'responded_at' => now()->subDay(),
        ]);

        GuestCheckin::create([
            'guest_id' => $confirmedGuest->id,
            'event_id' => $eventA->id,
            'method' => 'qr',
            'checked_in_at' => now()->subHours(2),
        ]);

        GuestCheckin::create([
            'guest_id' => $maybeGuest->id,
            'method' => 'manual',
            'checked_in_at' => now()->subHour(),
        ]);

        $dashboard = $this->service->forWedding($wedding->id, 'all');
        $metrics = $dashboard['metrics'];
        $rsvpBreakdown = $dashboard['rsvp_status_breakdown'];
        $checkinBreakdown = collect($dashboard['checkin_breakdown'])->keyBy('method');
        $forecastInsights = collect($dashboard['forecast_insights'])->keyBy('title');

        $this->assertSame(4, $metrics['total_guests']);
        $this->assertSame(1, $metrics['confirmed_guests']);
        $this->assertSame(1, $metrics['declined_guests']);
        $this->assertSame(1, $metrics['maybe_guests']);
        $this->assertSame(1, $metrics['no_response_guests']);
        $this->assertSame(2, $metrics['total_events']);
        $this->assertSame(1, $metrics['active_events']);
        $this->assertSame(2, $metrics['total_checkins']);
        $this->assertSame(2, $metrics['unique_checked_in_guests']);
        $this->assertSame(2, $metrics['pending_checkin_guests']);

        $this->assertSame(1, $rsvpBreakdown['confirmed']);
        $this->assertSame(1, $rsvpBreakdown['declined']);
        $this->assertSame(1, $rsvpBreakdown['maybe']);
        $this->assertSame(0, $rsvpBreakdown['no_response']);

        $this->assertSame(1, $checkinBreakdown['qr']['total']);
        $this->assertSame(1, $checkinBreakdown['manual']['total']);

        $this->assertSame(75.0, (float) $forecastInsights['Cobertura de respostas']['meta']['response_rate']);
        $this->assertSame(1, $forecastInsights['Projeção de presença']['meta']['conservative']);
        $this->assertSame(2, $forecastInsights['Projeção de presença']['meta']['baseline']);
        $this->assertSame(2, $forecastInsights['Projeção de presença']['meta']['optimistic']);
    }

    #[Test]
    public function it_applies_range_filter_to_invites(): void
    {
        $wedding = Wedding::factory()->create();
        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Família Range',
        ]);

        $oldInvite = GuestInvite::create([
            'household_id' => $household->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $recentInvite = GuestInvite::create([
            'household_id' => $household->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $oldInvite->timestamps = false;
        $oldInvite->created_at = now()->subDays(120);
        $oldInvite->save();

        $recentInvite->timestamps = false;
        $recentInvite->created_at = now()->subDays(3);
        $recentInvite->save();

        $range30 = $this->service->forWedding($wedding->id, '30d');
        $allTime = $this->service->forWedding($wedding->id, 'all');

        $this->assertSame(1, $range30['metrics']['total_invites']);
        $this->assertSame(2, $allTime['metrics']['total_invites']);
    }
}
