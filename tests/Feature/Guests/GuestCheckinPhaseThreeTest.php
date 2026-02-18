<?php

namespace Tests\Feature\Guests;

use App\Models\Guest;
use App\Models\GuestAuditLog;
use App\Models\GuestCheckin;
use App\Models\GuestEvent;
use App\Models\GuestHousehold;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestCheckinPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_checkin_and_writes_audit_log(): void
    {
        [$wedding, $event, , $guest] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $response = $this->postJson('/api/guests/checkins', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'method' => 'qr',
            'device_id' => 'gate-a',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.created', true)
            ->assertJsonPath('meta.duplicate', false)
            ->assertJsonPath('data.guest_id', $guest->id)
            ->assertJsonPath('data.event_id', $event->id);

        $this->assertDatabaseHas('guest_checkins', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'operator_id' => $couple->id,
            'method' => 'qr',
            'device_id' => 'gate-a',
        ]);

        $this->assertDatabaseHas('guest_audit_logs', [
            'wedding_id' => $wedding->id,
            'actor_id' => $couple->id,
            'action' => 'guest.checkin.recorded',
        ]);
    }

    #[Test]
    public function it_ignores_duplicate_checkin_for_same_guest_and_event(): void
    {
        [$wedding, $event, , $guest] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $this->postJson('/api/guests/checkins', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'method' => 'manual',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ])->assertOk();

        $response = $this->postJson('/api/guests/checkins', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'method' => 'manual',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.created', false)
            ->assertJsonPath('meta.duplicate', true);

        $this->assertSame(1, GuestCheckin::query()->count());

        $this->assertSame(1, GuestAuditLog::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('action', 'guest.checkin.duplicate_ignored')
            ->count());
    }

    #[Test]
    public function it_rejects_checkin_for_inactive_event(): void
    {
        [$wedding, $event, , $guest] = $this->createGuestScenario(eventActive: false);
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $response = $this->postJson('/api/guests/checkins', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'method' => 'qr',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Evento inativo para check-in.');

        $this->assertDatabaseCount('guest_checkins', 0);
    }

    #[Test]
    public function it_lists_recent_checkins_with_operational_summary(): void
    {
        [$wedding, $event, $household, $guestA] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        $guestB = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado B',
            'email' => 'guestb-' . fake()->unique()->safeEmail(),
            'status' => 'pending',
            'overall_rsvp_status' => 'no_response',
        ]);

        GuestCheckin::create([
            'guest_id' => $guestA->id,
            'event_id' => $event->id,
            'operator_id' => $couple->id,
            'method' => 'qr',
            'checked_in_at' => now()->subMinutes(10),
        ]);

        GuestCheckin::create([
            'guest_id' => $guestB->id,
            'operator_id' => $couple->id,
            'method' => 'manual',
            'checked_in_at' => now()->subMinutes(5),
        ]);

        Sanctum::actingAs($couple);

        $response = $this->getJson('/api/guests/checkins?limit=10', [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.total_checkins', 2)
            ->assertJsonPath('summary.unique_checked_in_guests', 2);

        $methods = collect($response->json('summary.by_method'))->keyBy('method');

        $this->assertSame(1, $methods['qr']['total']);
        $this->assertSame(1, $methods['manual']['total']);
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function it_filters_checkin_list_by_method_and_search_term(): void
    {
        [$wedding, $event, $household, $guestA] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        $guestB = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Maria Manual',
            'email' => 'manual-' . fake()->unique()->safeEmail(),
            'status' => 'pending',
            'overall_rsvp_status' => 'no_response',
        ]);

        GuestCheckin::create([
            'guest_id' => $guestA->id,
            'event_id' => $event->id,
            'operator_id' => $couple->id,
            'method' => 'qr',
            'checked_in_at' => now()->subMinutes(10),
        ]);

        GuestCheckin::create([
            'guest_id' => $guestB->id,
            'event_id' => $event->id,
            'operator_id' => $couple->id,
            'method' => 'manual',
            'checked_in_at' => now()->subMinutes(5),
        ]);

        Sanctum::actingAs($couple);

        $response = $this->getJson('/api/guests/checkins?method=manual&search=maria', [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.total_checkins', 1)
            ->assertJsonPath('data.0.guest.name', 'Maria Manual')
            ->assertJsonPath('data.0.method', 'manual');
    }

    /**
     * @return array{0: Wedding, 1: GuestEvent, 2: GuestHousehold, 3: Guest}
     */
    private function createGuestScenario(bool $eventActive = true): array
    {
        $wedding = Wedding::factory()->create();

        $event = GuestEvent::create([
            'wedding_id' => $wedding->id,
            'name' => 'Cerimonia',
            'slug' => 'cerimonia',
            'is_active' => $eventActive,
            'event_at' => now()->addMonth(),
        ]);

        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Familia Teste',
        ]);

        $guest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado A',
            'email' => 'guesta-' . fake()->unique()->safeEmail(),
            'phone' => '11999990000',
            'status' => 'pending',
            'overall_rsvp_status' => 'no_response',
        ]);

        return [$wedding, $event, $household, $guest];
    }

    private function createCoupleUser(Wedding $wedding): User
    {
        $couple = User::factory()->couple()->create([
            'current_wedding_id' => $wedding->id,
        ]);

        $wedding->users()->attach($couple->id, [
            'role' => 'couple',
            'permissions' => [],
        ]);

        return $couple;
    }
}
