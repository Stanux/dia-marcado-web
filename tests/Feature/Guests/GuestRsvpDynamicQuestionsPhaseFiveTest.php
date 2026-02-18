<?php

namespace Tests\Feature\Guests;

use App\Models\Guest;
use App\Models\GuestEvent;
use App\Models\GuestHousehold;
use App\Models\GuestInvite;
use App\Models\GuestRsvp;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestRsvpDynamicQuestionsPhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_rsvp_rejects_missing_required_question(): void
    {
        [$wedding, $event, $guest] = $this->createScenario([
            [
                'label' => 'Documento',
                'type' => 'text',
                'required' => true,
            ],
        ]);

        $invite = GuestInvite::create([
            'household_id' => $guest->household_id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $response = $this->postJson('/api/public/rsvp', [
            'token' => $invite->token,
            'event_id' => $event->id,
            'status' => 'confirmed',
            'guest' => [
                'name' => $guest->name,
                'email' => $guest->email,
            ],
            'responses' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', "A pergunta 'Documento' e obrigatoria.");

        $this->assertDatabaseMissing('guest_rsvps', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
        ]);
    }

    #[Test]
    public function authenticated_rsvp_rejects_invalid_select_option(): void
    {
        [$wedding, $event, $guest] = $this->createScenario([
            [
                'label' => 'Menu',
                'type' => 'select',
                'options' => ['Vegetariano', 'Carne'],
                'required' => true,
            ],
        ]);

        $couple = $this->createCoupleUser($wedding);
        Sanctum::actingAs($couple);

        $response = $this->postJson('/api/guests/rsvp', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'status' => 'confirmed',
            'responses' => [
                'menu' => 'Peixe',
            ],
        ], [
            'X-Wedding-ID' => $wedding->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', "Resposta invalida para a pergunta 'Menu'.");

        $this->assertDatabaseMissing('guest_rsvps', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
        ]);
    }

    #[Test]
    public function public_rsvp_accepts_valid_dynamic_responses_and_normalizes_keys(): void
    {
        [, $event, $guest] = $this->createScenario([
            [
                'label' => 'Acompanhantes',
                'type' => 'number',
                'required' => true,
            ],
        ]);

        $invite = GuestInvite::create([
            'household_id' => $guest->household_id,
            'guest_id' => $guest->id,
            'token' => GuestInvite::generateToken(),
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $response = $this->postJson('/api/public/rsvp', [
            'token' => $invite->token,
            'event_id' => $event->id,
            'status' => 'confirmed',
            'guest' => [
                'name' => $guest->name,
                'email' => $guest->email,
            ],
            'responses' => [
                'Acompanhantes' => '2',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $rsvp = GuestRsvp::query()
            ->where('guest_id', $guest->id)
            ->where('event_id', $event->id)
            ->first();

        $this->assertNotNull($rsvp);
        $this->assertSame('2', $rsvp->responses['acompanhantes'] ?? null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array{0: Wedding, 1: GuestEvent, 2: Guest}
     */
    private function createScenario(array $questions): array
    {
        $wedding = Wedding::factory()->create([
            'settings' => [
                'rsvp_access' => 'open',
            ],
        ]);

        $household = GuestHousehold::create([
            'wedding_id' => $wedding->id,
            'name' => 'Familia Teste',
        ]);

        $guest = Guest::create([
            'wedding_id' => $wedding->id,
            'household_id' => $household->id,
            'name' => 'Convidado Dinamico',
            'email' => 'dynamic-' . fake()->unique()->safeEmail(),
            'status' => 'pending',
            'overall_rsvp_status' => 'no_response',
        ]);

        $event = GuestEvent::create([
            'wedding_id' => $wedding->id,
            'name' => 'Jantar',
            'slug' => 'jantar',
            'is_active' => true,
            'questions' => $questions,
        ]);

        return [$wedding, $event, $guest];
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
