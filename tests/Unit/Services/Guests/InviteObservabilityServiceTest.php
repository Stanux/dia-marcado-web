<?php

namespace Tests\Unit\Services\Guests;

use App\Models\Guest;
use App\Models\GuestAuditLog;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\GuestMessage;
use App\Models\GuestMessageLog;
use App\Models\Wedding;
use App\Services\Guests\InviteObservabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InviteObservabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_timeline_for_invite_with_audit_and_message_events(): void
    {
        [$wedding, $household, $guest] = $this->createScenario();

        $invite = GuestInvite::create([
            'household_id' => $household->id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
            'created_at' => now()->subHours(3),
        ]);

        $reissuedLog = GuestAuditLog::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'action' => 'guest.invite.reissued',
            'context' => [
                'invite_id' => $invite->id,
                'channel' => 'email',
                'max_uses' => 3,
            ],
        ]);
        $reissuedLog->timestamps = false;
        $reissuedLog->created_at = now()->subHours(2);
        $reissuedLog->save();

        $message = GuestMessage::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
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
            'occurred_at' => now()->subHour(),
            'metadata' => [
                'contact' => $guest->email,
                'error' => 'smtp timeout',
            ],
        ]);

        $usedLog = GuestAuditLog::withoutGlobalScopes()->create([
            'wedding_id' => $wedding->id,
            'action' => 'guest.invite.used',
            'context' => [
                'invite_id' => $invite->id,
                'uses_count' => 1,
            ],
        ]);
        $usedLog->timestamps = false;
        $usedLog->created_at = now()->subMinutes(10);
        $usedLog->save();

        $timeline = app(InviteObservabilityService::class)->timelineForInvite($invite, 20);

        $this->assertGreaterThanOrEqual(4, $timeline->count());
        $titles = $timeline->pluck('title')->all();
        $this->assertContains('Convite criado', $titles);
        $this->assertContains('Convite utilizado', $titles);
        $this->assertTrue($timeline->contains(fn (array $event): bool => $event['title'] === 'Convite reemitido'));
        $this->assertTrue($timeline->contains(fn (array $event): bool => $event['title'] === 'Falha no envio'));

        $timelineText = app(InviteObservabilityService::class)->timelineText($invite, 20);
        $this->assertStringContainsString('Convite utilizado', $timelineText);
        $this->assertStringContainsString('Falha no envio', $timelineText);
        $this->assertStringContainsString('smtp timeout', $timelineText);
    }

    /**
     * @return array{0:Wedding, 1:GuestHousehold, 2:Guest}
     */
    private function createScenario(): array
    {
        $wedding = Wedding::factory()->create();

        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Familia timeline',
        ]);

        $guest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado timeline',
            'email' => 'timeline@example.com',
            'status' => 'pending',
        ]);

        return [$wedding, $household, $guest];
    }
}
