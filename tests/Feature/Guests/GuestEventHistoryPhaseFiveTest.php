<?php

namespace Tests\Feature\Guests;

use App\Models\GuestEvent;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestEventHistoryPhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_event_history_with_create_and_update_entries(): void
    {
        $wedding = Wedding::factory()->create();
        $couple = $this->createCoupleUser($wedding);

        Sanctum::actingAs($couple);

        $event = GuestEvent::create([
            'wedding_id' => $wedding->id,
            'name' => 'Cerimonia',
            'slug' => 'cerimonia',
            'is_active' => true,
            'questions' => [
                ['label' => 'Menu', 'type' => 'select', 'required' => false, 'options' => ['Carne', 'Vegetariano']],
            ],
        ]);

        $event->update([
            'name' => 'Cerimonia Principal',
            'is_active' => false,
            'questions' => [
                ['label' => 'Menu', 'type' => 'select', 'required' => true, 'options' => ['Carne', 'Vegetariano']],
                ['label' => 'Acompanhantes', 'type' => 'number', 'required' => false],
            ],
        ]);

        $response = $this->getJson("/api/guests/events/{$event->id}/history?limit=10", [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.event_id', $event->id);

        $titles = collect($response->json('data.events'))->pluck('title')->all();

        $this->assertContains('Evento criado', $titles);
        $this->assertContains('Evento atualizado', $titles);
    }

    #[Test]
    public function it_denies_history_access_for_event_outside_current_wedding(): void
    {
        $weddingA = Wedding::factory()->create();
        $weddingB = Wedding::factory()->create();

        $coupleA = $this->createCoupleUser($weddingA);

        $eventB = GuestEvent::create([
            'wedding_id' => $weddingB->id,
            'name' => 'Evento Externo',
            'slug' => 'evento-externo',
            'is_active' => true,
        ]);

        Sanctum::actingAs($coupleA);

        $response = $this->getJson("/api/guests/events/{$eventB->id}/history", [
            'X-Wedding-ID' => $weddingA->id,
        ]);

        $response->assertForbidden();
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
