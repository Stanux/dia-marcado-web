<?php

namespace Tests\Feature\Guests;

use App\Models\Guest;
use App\Models\GuestCheckin;
use App\Models\GuestEvent;
use App\Models\GuestHousehold;
use App\Models\User;
use App\Models\Wedding;
use App\Services\Guests\GuestCheckinQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestCheckinQrPhaseFourTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_generates_qr_payload_for_guest_in_current_wedding(): void
    {
        [$wedding, , , $guest] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $response = $this->getJson("/api/guests/checkins/qr/{$guest->id}", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.guest_id', $guest->id)
            ->assertJsonPath('data.guest_name', $guest->name);

        $qrContent = (string) $response->json('data.qr_content');

        $this->assertTrue(Str::startsWith($qrContent, 'dmc-checkin:'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    #[Test]
    public function it_scans_qr_and_records_checkin(): void
    {
        [$wedding, $event, , $guest] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $qrContent = (string) $this->getJson("/api/guests/checkins/qr/{$guest->id}", [
            'X-Wedding-ID' => $wedding->id,
        ])->json('data.qr_content');

        $response = $this->postJson('/api/guests/checkins/scan', [
            'qr_code' => $qrContent,
            'event_id' => $event->id,
            'device_id' => 'scanner-gate-1',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.created', true)
            ->assertJsonPath('meta.duplicate', false)
            ->assertJsonPath('data.guest_id', $guest->id)
            ->assertJsonPath('data.event_id', $event->id)
            ->assertJsonPath('data.method', 'qr');

        $this->assertDatabaseHas('guest_checkins', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'method' => 'qr',
            'operator_id' => $couple->id,
            'device_id' => 'scanner-gate-1',
        ]);
    }

    #[Test]
    public function it_returns_duplicate_meta_when_scanning_same_qr_twice_for_same_event(): void
    {
        [$wedding, $event, , $guest] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $qrContent = (string) $this->getJson("/api/guests/checkins/qr/{$guest->id}", [
            'X-Wedding-ID' => $wedding->id,
        ])->json('data.qr_content');

        $this->postJson('/api/guests/checkins/scan', [
            'qr_code' => $qrContent,
            'event_id' => $event->id,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ])->assertOk();

        $response = $this->postJson('/api/guests/checkins/scan', [
            'qr_code' => $qrContent,
            'event_id' => $event->id,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.created', false)
            ->assertJsonPath('meta.duplicate', true);

        $this->assertSame(1, GuestCheckin::query()->count());
    }

    #[Test]
    public function it_rejects_qr_token_from_another_wedding(): void
    {
        [$weddingA, $eventA, , ] = $this->createGuestScenario();
        [, , , $guestB] = $this->createGuestScenario();

        $coupleA = $this->createCoupleUser($weddingA);
        $foreignQr = app(GuestCheckinQrService::class)->forGuest($guestB)['qr_content'];

        Sanctum::actingAs($coupleA);

        $response = $this->postJson('/api/guests/checkins/scan', [
            'qr_code' => $foreignQr,
            'event_id' => $eventA->id,
        ], [
            'X-Wedding-ID' => $weddingA->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Codigo QR nao pertence a este casamento.');

        $this->assertDatabaseCount('guest_checkins', 0);
    }

    #[Test]
    public function it_rejects_invalid_qr_code(): void
    {
        [$wedding, $event] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $response = $this->postJson('/api/guests/checkins/scan', [
            'qr_code' => 'dmc-checkin:invalid-token',
            'event_id' => $event->id,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Codigo QR invalido.');

        $this->assertDatabaseCount('guest_checkins', 0);
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
            'slug' => 'cerimonia-' . Str::lower(Str::random(6)),
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
