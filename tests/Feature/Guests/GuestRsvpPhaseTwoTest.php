<?php

namespace Tests\Feature\Guests;

use App\Models\Guest;
use App\Models\GuestAuditLog;
use App\Models\GuestEvent;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestRsvpPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_rsvp_with_token_marks_invite_as_used_and_writes_audit_logs(): void
    {
        [$wedding, $event, $household, $guest] = $this->createGuestScenario();

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
            'max_uses' => 2,
        ]);

        $response = $this->postJson('/api/public/rsvp', [
            'token' => $invite->token,
            'event_id' => $event->id,
            'status' => 'confirmado',
            'guest' => [
                'name' => $guest->name,
                'email' => $guest->email,
            ],
            'responses' => [
                'food' => 'vegetariano',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('guest_rsvps', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'status' => 'confirmed',
        ]);

        $guest->refresh();
        $invite->refresh();

        $this->assertSame(1, $invite->uses_count);
        $this->assertNotNull($invite->used_at);
        $this->assertSame('opened', $invite->status);
        $this->assertSame('confirmed', $guest->status);
        $this->assertSame('confirmed', $guest->overall_rsvp_status);

        $this->assertDatabaseHas('guest_audit_logs', [
            'wedding_id' => $wedding->id,
            'action' => 'guest.invite.used',
        ]);

        $publicAudit = GuestAuditLog::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('action', 'guest.rsvp.public_submitted')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($publicAudit);
        $this->assertSame($guest->id, $publicAudit->context['guest_id'] ?? null);
        $this->assertSame('token', $publicAudit->context['access_mode'] ?? null);
    }

    #[Test]
    public function public_rsvp_rejects_revoked_invite(): void
    {
        [, $event, $household, $guest] = $this->createGuestScenario();

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_reason' => 'envio incorreto',
        ]);

        $response = $this->postJson('/api/public/rsvp', [
            'token' => $invite->token,
            'event_id' => $event->id,
            'status' => 'confirmado',
            'guest' => [
                'name' => $guest->name,
                'email' => $guest->email,
            ],
        ]);

        $response->assertStatus(410)
            ->assertJsonPath('message', 'Convite revogado.');

        $this->assertDatabaseMissing('guest_rsvps', [
            'event_id' => $event->id,
            'guest_id' => $guest->id,
        ]);
    }

    #[Test]
    public function public_rsvp_rejects_invite_when_usage_limit_is_reached(): void
    {
        [, $event, $household, $guest] = $this->createGuestScenario();

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'opened',
            'uses_count' => 1,
            'max_uses' => 1,
            'used_at' => now(),
        ]);

        $response = $this->postJson('/api/public/rsvp', [
            'token' => $invite->token,
            'event_id' => $event->id,
            'status' => 'confirmado',
            'guest' => [
                'name' => $guest->name,
                'email' => $guest->email,
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Convite já atingiu o limite de uso.');

        $this->assertDatabaseMissing('guest_rsvps', [
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'status' => 'confirmed',
        ]);
    }

    #[Test]
    public function authenticated_rsvp_writes_audit_log_and_updates_guest_status(): void
    {
        [$wedding, $event, , $guest] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $response = $this->postJson('/api/guests/rsvp', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'status' => 'talvez',
            'responses' => [
                'drink' => 'suco',
            ],
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'maybe');

        $guest->refresh();

        $this->assertSame('maybe', $guest->status);
        $this->assertSame('maybe', $guest->overall_rsvp_status);

        $this->assertDatabaseHas('guest_audit_logs', [
            'wedding_id' => $wedding->id,
            'actor_id' => $couple->id,
            'action' => 'guest.rsvp.authenticated_submitted',
        ]);
    }

    #[Test]
    public function revoke_endpoint_blocks_future_public_rsvp(): void
    {
        [$wedding, $event, $household, $guest] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        Sanctum::actingAs($couple);

        $revokeResponse = $this->postJson("/api/guests/invites/{$invite->id}/revoke", [
            'reason' => 'solicitado pelo casal',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $revokeResponse->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        $invite->refresh();
        $this->assertNotNull($invite->revoked_at);
        $this->assertSame('solicitado pelo casal', $invite->revoked_reason);

        $this->assertDatabaseHas('guest_audit_logs', [
            'wedding_id' => $wedding->id,
            'actor_id' => $couple->id,
            'action' => 'guest.invite.revoked',
        ]);

        $publicResponse = $this->postJson('/api/public/rsvp', [
            'token' => $invite->token,
            'event_id' => $event->id,
            'status' => 'confirmed',
            'guest' => [
                'name' => $guest->name,
                'email' => $guest->email,
            ],
        ]);

        $publicResponse->assertStatus(410)
            ->assertJsonPath('message', 'Convite revogado.');
    }

    #[Test]
    public function reissue_endpoint_resets_invite_state_and_records_audit(): void
    {
        [$wedding, , $household, $guest] = $this->createGuestScenario();
        $couple = $this->createCoupleUser($wedding);

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'revoked',
            'used_at' => now()->subDay(),
            'uses_count' => 3,
            'max_uses' => 3,
            'revoked_at' => now()->subHour(),
            'revoked_reason' => 'reemitir',
        ]);

        $oldToken = $invite->token;

        Sanctum::actingAs($couple);

        $response = $this->postJson("/api/guests/invites/{$invite->id}/reissue", [
            'expires_in_days' => 10,
            'max_uses' => 5,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'sent');

        $invite->refresh();

        $this->assertNotSame($oldToken, $invite->token);
        $this->assertNull($invite->used_at);
        $this->assertSame(0, $invite->uses_count);
        $this->assertSame(5, $invite->max_uses);
        $this->assertNotNull($invite->expires_at);
        $this->assertNull($invite->revoked_at);
        $this->assertNull($invite->revoked_reason);

        $this->assertDatabaseHas('guest_audit_logs', [
            'wedding_id' => $wedding->id,
            'actor_id' => $couple->id,
            'action' => 'guest.invite.reissued',
        ]);
    }

    /**
     * @return array{0: Wedding, 1: GuestEvent, 2: GuestHousehold, 3: Guest}
     */
    private function createGuestScenario(array $weddingSettings = []): array
    {
        $wedding = Wedding::factory()->create([
            'settings' => array_merge(['rsvp_access' => 'open'], $weddingSettings),
        ]);

        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Família Teste',
        ]);

        $guest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado Teste',
            'email' => 'guest@example.com',
            'phone' => '11988887777',
            'status' => 'pending',
        ]);

        $event = GuestEvent::create([
            'wedding_id' => $wedding->id,
            'name' => 'Cerimônia',
            'slug' => 'cerimonia-' . Str::lower(Str::random(6)),
            'is_active' => true,
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
