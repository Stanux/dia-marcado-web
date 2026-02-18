<?php

namespace Tests\Unit\Services\Guests;

use App\Models\Guest;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\GuestMessage;
use App\Models\GuestMessageLog;
use App\Models\Wedding;
use App\Services\Guests\InviteIncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InviteIncidentServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_incident_queue_and_channel_breakdown(): void
    {
        [$wedding, $household, $guest] = $this->createScenario();

        $inviteEmail = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $inviteWhatsapp = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);

        $messageA = GuestMessage::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'channel' => 'email',
            'status' => 'failed',
            'payload' => ['invite_id' => $inviteEmail->id],
            'created_at' => now()->subHours(3),
        ]);

        $messageB = GuestMessage::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'channel' => 'email',
            'status' => 'failed',
            'payload' => ['invite_id' => $inviteEmail->id],
            'created_at' => now()->subHours(1),
        ]);

        $messageC = GuestMessage::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'channel' => 'whatsapp',
            'status' => 'failed',
            'payload' => ['invite_id' => $inviteWhatsapp->id],
            'created_at' => now()->subHours(2),
        ]);

        GuestMessageLog::create([
            'message_id' => $messageA->id,
            'guest_id' => $guest->id,
            'status' => 'failed',
            'occurred_at' => now()->subHours(3),
            'metadata' => ['error' => 'smtp down', 'contact' => $guest->email],
        ]);

        GuestMessageLog::create([
            'message_id' => $messageB->id,
            'guest_id' => $guest->id,
            'status' => 'failed',
            'occurred_at' => now()->subHour(),
            'metadata' => ['error' => 'smtp timeout', 'contact' => $guest->email],
        ]);

        GuestMessageLog::create([
            'message_id' => $messageC->id,
            'guest_id' => $guest->id,
            'status' => 'failed',
            'occurred_at' => now()->subHours(2),
            'metadata' => ['error' => 'webhook 500', 'contact' => $guest->phone],
        ]);

        $data = app(InviteIncidentService::class)->forWedding($wedding->id, '30d', 20, 100);

        $this->assertSame(3, $data['failed_messages_total']);
        $this->assertSame(2, $data['failed_invites_total']);
        $this->assertCount(2, $data['failed_channels']);
        $this->assertCount(2, $data['failed_queue']);

        $channels = collect($data['failed_channels'])->keyBy('channel');
        $this->assertSame(2, $channels['email']['failed_messages']);
        $this->assertSame(1, $channels['email']['impacted_invites']);
        $this->assertSame(1, $channels['whatsapp']['failed_messages']);

        $emailQueue = collect($data['failed_queue'])->firstWhere('invite_id', $inviteEmail->id);
        $this->assertNotNull($emailQueue);
        $this->assertSame(2, $emailQueue['failed_attempts']);
        $this->assertStringContainsString('smtp', mb_strtolower($emailQueue['error']));
        $this->assertTrue($emailQueue['can_retry']);
    }

    #[Test]
    public function it_retries_failed_invite_by_id_and_returns_success(): void
    {
        Notification::fake();

        [$wedding, $household, $guest] = $this->createScenario();

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $result = app(InviteIncidentService::class)->retryInviteById($wedding->id, $invite->id, null);

        $this->assertTrue($result['ok']);
        $this->assertSame('sent', $result['type']);
    }

    #[Test]
    public function it_blocks_retry_for_revoked_or_expired_invites(): void
    {
        [$wedding, $household, $guest] = $this->createScenario();

        $revokedInvite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'revoked',
            'revoked_at' => now()->subHour(),
        ]);

        $expiredInvite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
            'expires_at' => now()->subHour(),
        ]);

        $revokedResult = app(InviteIncidentService::class)->retryInviteById($wedding->id, $revokedInvite->id, null);
        $expiredResult = app(InviteIncidentService::class)->retryInviteById($wedding->id, $expiredInvite->id, null);

        $this->assertFalse($revokedResult['ok']);
        $this->assertSame('blocked', $revokedResult['type']);
        $this->assertStringContainsString('revogado', mb_strtolower($revokedResult['message']));

        $this->assertFalse($expiredResult['ok']);
        $this->assertSame('blocked', $expiredResult['type']);
        $this->assertStringContainsString('expirado', mb_strtolower($expiredResult['message']));
    }

    /**
     * @return array{0:Wedding, 1:GuestHousehold, 2:Guest}
     */
    private function createScenario(): array
    {
        $wedding = Wedding::factory()->create();

        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Familia incidente',
        ]);

        $guest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado incidente',
            'email' => 'incident@example.com',
            'phone' => '11999998888',
            'status' => 'pending',
        ]);

        return [$wedding, $household, $guest];
    }
}
