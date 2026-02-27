<?php

namespace Tests\Feature\Properties;

use App\Filament\Pages\Onboarding;
use App\Listeners\ClearOnboardingSession;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testes de propriedade para persistência de sessão do onboarding.
 */
class OnboardingSessionPropertyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function onboarding_data_persists_in_session(): void
    {
        $testData = [
            'creator_name' => 'Ana Souza',
            'partner_name' => 'Bruno Lima',
            'wedding_date' => '2026-06-15',
            'venue_name' => 'Espaço Jardim',
            'venue_address' => 'Rua das Flores, 123',
            'venue_neighborhood' => 'Centro',
            'venue_city' => 'São Paulo',
            'venue_state' => 'SP',
            'venue_phone' => '11999999999',
            'plan' => 'basic',
        ];

        Onboarding::saveToSession($testData);

        $savedData = Onboarding::getFromSession();

        $this->assertEquals($testData['creator_name'], $savedData['creator_name']);
        $this->assertEquals($testData['partner_name'], $savedData['partner_name']);
        $this->assertEquals($testData['wedding_date'], $savedData['wedding_date']);
        $this->assertEquals($testData['venue_name'], $savedData['venue_name']);
        $this->assertEquals($testData['plan'], $savedData['plan']);
    }

    #[Test]
    public function partial_onboarding_data_persists_in_session(): void
    {
        $step1Data = [
            'creator_name' => 'Carla Mendes',
            'partner_name' => '',
            'wedding_date' => '2026-12-25',
        ];

        Onboarding::saveToSession($step1Data);

        $step2Data = array_merge(Onboarding::getFromSession(), [
            'venue_name' => 'Fazenda Bela Vista',
            'venue_city' => 'Campinas',
        ]);

        Onboarding::saveToSession($step2Data);

        $savedData = Onboarding::getFromSession();

        $this->assertEquals('Carla Mendes', $savedData['creator_name']);
        $this->assertEquals('2026-12-25', $savedData['wedding_date']);
        $this->assertEquals('Fazenda Bela Vista', $savedData['venue_name']);
        $this->assertEquals('Campinas', $savedData['venue_city']);
    }

    #[Test]
    public function empty_session_returns_empty_array(): void
    {
        Onboarding::clearSession();

        $data = Onboarding::getFromSession();

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    #[Test]
    public function onboarding_session_is_cleared_on_logout(): void
    {
        $user = User::factory()->create();

        $testData = [
            'creator_name' => 'Fernanda Rocha',
            'partner_name' => 'João Santos',
            'wedding_date' => '2026-08-20',
        ];
        Onboarding::saveToSession($testData);

        $this->assertNotEmpty(Onboarding::getFromSession());

        $listener = new ClearOnboardingSession();
        $listener->handle(new Logout('web', $user));

        $this->assertEmpty(Onboarding::getFromSession());
    }

    #[Test]
    public function multiple_logouts_do_not_cause_error(): void
    {
        $user = User::factory()->create();
        $listener = new ClearOnboardingSession();

        Onboarding::saveToSession(['wedding_date' => '2026-01-01']);
        $listener->handle(new Logout('web', $user));

        $listener->handle(new Logout('web', $user));
        $listener->handle(new Logout('web', $user));

        $this->assertEmpty(Onboarding::getFromSession());
    }

    #[Test]
    public function session_data_can_be_updated(): void
    {
        Onboarding::saveToSession([
            'creator_name' => 'Juliana Alves',
            'wedding_date' => '2026-03-15',
            'plan' => 'basic',
        ]);

        Onboarding::saveToSession([
            'creator_name' => 'Juliana A. Alves',
            'wedding_date' => '2026-04-20',
            'plan' => 'premium',
        ]);

        $savedData = Onboarding::getFromSession();

        $this->assertEquals('Juliana A. Alves', $savedData['creator_name']);
        $this->assertEquals('2026-04-20', $savedData['wedding_date']);
        $this->assertEquals('premium', $savedData['plan']);
    }
}
