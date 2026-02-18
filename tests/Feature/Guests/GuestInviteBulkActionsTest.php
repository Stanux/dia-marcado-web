<?php

namespace Tests\Feature\Guests;

use App\Models\Guest;
use App\Models\GuestAuditLog;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\GuestMessage;
use App\Models\GuestMessageLog;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestInviteBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function bulk_reissue_reissues_multiple_invites_and_returns_summary(): void
    {
        [$wedding, $household, $guest] = $this->createHouseholdScenario();
        $couple = $this->createCoupleUser($wedding);

        $inviteA = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'revoked',
            'uses_count' => 2,
            'max_uses' => 2,
            'used_at' => now()->subDay(),
            'revoked_at' => now()->subHours(4),
            'revoked_reason' => 'teste',
        ]);

        $inviteB = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
            'uses_count' => 1,
            'max_uses' => 3,
            'used_at' => now()->subDay(),
        ]);

        $oldTokenA = $inviteA->token;
        $oldTokenB = $inviteB->token;

        Sanctum::actingAs($couple);

        $response = $this->postJson('/api/guests/invites/bulk-reissue', [
            'invite_ids' => [$inviteA->id, $inviteB->id],
            'expires_in_days' => 15,
            'max_uses' => 5,
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.reissued', 2);

        $inviteA->refresh();
        $inviteB->refresh();

        $this->assertNotSame($oldTokenA, $inviteA->token);
        $this->assertNotSame($oldTokenB, $inviteB->token);
        $this->assertSame('sent', $inviteA->status);
        $this->assertSame('sent', $inviteB->status);
        $this->assertSame(0, $inviteA->uses_count);
        $this->assertSame(0, $inviteB->uses_count);
        $this->assertNull($inviteA->used_at);
        $this->assertNull($inviteB->used_at);
        $this->assertNull($inviteA->revoked_at);
        $this->assertNull($inviteA->revoked_reason);
        $this->assertSame(5, $inviteA->max_uses);
        $this->assertSame(5, $inviteB->max_uses);

        $this->assertSame(2, GuestAuditLog::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('action', 'guest.invite.reissued')
            ->count());
    }

    #[Test]
    public function bulk_revoke_revokes_valid_invites_and_skips_already_revoked(): void
    {
        [$wedding, $household, $guest] = $this->createHouseholdScenario();
        $couple = $this->createCoupleUser($wedding);

        $inviteA = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $inviteB = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'revoked',
            'revoked_at' => now()->subDay(),
            'revoked_reason' => 'ja revogado',
        ]);

        $inviteC = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'delivered',
        ]);

        Sanctum::actingAs($couple);

        $response = $this->postJson('/api/guests/invites/bulk-revoke', [
            'invite_ids' => [$inviteA->id, $inviteB->id, $inviteC->id],
            'reason' => 'limpeza da lista',
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.revoked', 2)
            ->assertJsonPath('data.already_revoked', 1)
            ->assertJsonPath('data.failed', 0);

        $inviteA->refresh();
        $inviteB->refresh();
        $inviteC->refresh();

        $this->assertSame('revoked', $inviteA->status);
        $this->assertSame('revoked', $inviteC->status);
        $this->assertSame('limpeza da lista', $inviteA->revoked_reason);
        $this->assertSame('limpeza da lista', $inviteC->revoked_reason);

        $this->assertSame('ja revogado', $inviteB->revoked_reason);

        $this->assertSame(2, GuestAuditLog::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->where('action', 'guest.invite.revoked')
            ->count());
    }

    #[Test]
    public function bulk_actions_reject_invites_outside_current_wedding_scope(): void
    {
        [$weddingA, $householdA, $guestA] = $this->createHouseholdScenario();
        [$weddingB, $householdB, $guestB] = $this->createHouseholdScenario();
        $couple = $this->createCoupleUser($weddingA);

        $inviteA = GuestInvite::create([
            'household_id' => $householdA->id,
            'guest_id' => $guestA->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $inviteB = GuestInvite::create([
            'household_id' => $householdB->id,
            'guest_id' => $guestB->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $oldTokenA = $inviteA->token;

        Sanctum::actingAs($couple);

        $response = $this->postJson('/api/guests/invites/bulk-reissue', [
            'invite_ids' => [$inviteA->id, $inviteB->id],
            'expires_in_days' => 10,
        ], [
            'X-Wedding-ID' => $weddingA->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Alguns convites não foram encontrados ou não pertencem ao contexto permitido.');

        $inviteA->refresh();
        $this->assertSame($oldTokenA, $inviteA->token);
        $this->assertSame('sent', $inviteA->status);

        $this->assertSame($weddingB->id, $householdB->wedding_id);
    }

    #[Test]
    public function timeline_endpoint_returns_observability_events_for_invite(): void
    {
        [$wedding, $household, $guest] = $this->createHouseholdScenario();
        $couple = $this->createCoupleUser($wedding);

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'created_by' => $couple->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        GuestAuditLog::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'actor_id' => $couple->id,
            'action' => 'guest.invite.reissued',
            'context' => [
                'invite_id' => $invite->id,
                'channel' => 'email',
                'max_uses' => 2,
            ],
        ]);

        $message = GuestMessage::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'created_by' => $couple->id,
            'channel' => 'email',
            'status' => 'failed',
            'payload' => [
                'invite_id' => $invite->id,
            ],
        ]);

        GuestMessageLog::create([
            'message_id' => $message->id,
            'guest_id' => $guest->id,
            'status' => 'failed',
            'occurred_at' => now()->subMinutes(5),
            'metadata' => [
                'contact' => $guest->email,
                'error' => 'smtp timeout',
            ],
        ]);

        Sanctum::actingAs($couple);

        $response = $this->getJson("/api/guests/invites/{$invite->id}/timeline?limit=10", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.invite_id', $invite->id);

        $titles = collect($response->json('data.events'))->pluck('title')->all();

        $this->assertContains('Convite reemitido', $titles);
        $this->assertContains('Falha no envio', $titles);
    }

    /**
     * @return array{0: Wedding, 1: GuestHousehold, 2: Guest}
     */
    private function createHouseholdScenario(): array
    {
        $wedding = Wedding::factory()->create();

        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Familia teste',
        ]);

        $guest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado teste',
            'email' => 'bulk-' . fake()->unique()->safeEmail(),
            'phone' => '11977776666',
            'status' => 'pending',
        ]);

        return [$wedding, $household, $guest];
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
