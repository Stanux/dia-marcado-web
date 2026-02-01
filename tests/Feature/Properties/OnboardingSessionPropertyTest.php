<?php

namespace Tests\Feature\Properties;

use App\Filament\Pages\Onboarding;
use App\Listeners\ClearOnboardingSession;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testes de propriedade para persistência de sessão do onboarding.
 * 
 * Propriedade 10: Persistência de Estado do Onboarding
 * Propriedade 11: Reset de Onboarding Após Logout
 */
class OnboardingSessionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Propriedade 10: Dados do onboarding devem persistir na sessão entre navegações.
     * 
     * Valida: Requisitos 1.3, 8.1
     */
    #[Test]
    public function onboarding_data_persists_in_session(): void
    {
        $testData = [
            'wedding_date' => '2026-06-15',
            'partner_name' => 'Maria Silva',
            'partner_email' => 'maria@example.com',
            'venue_name' => 'Espaço Jardim',
            'venue_address' => 'Rua das Flores, 123',
            'venue_neighborhood' => 'Centro',
            'venue_city' => 'São Paulo',
            'venue_state' => 'SP',
            'venue_phone' => '11999999999',
            'plan' => 'basic',
        ];

        // Salvar dados na sessão
        Onboarding::saveToSession($testData);

        // Verificar que dados foram salvos
        $savedData = Onboarding::getFromSession();
        
        $this->assertEquals($testData['wedding_date'], $savedData['wedding_date']);
        $this->assertEquals($testData['partner_name'], $savedData['partner_name']);
        $this->assertEquals($testData['partner_email'], $savedData['partner_email']);
        $this->assertEquals($testData['venue_name'], $savedData['venue_name']);
        $this->assertEquals($testData['plan'], $savedData['plan']);
    }

    /**
     * Propriedade 10: Dados parciais devem ser preservados na sessão.
     * 
     * Valida: Requisitos 1.3, 8.1
     */
    #[Test]
    public function partial_onboarding_data_persists_in_session(): void
    {
        // Simular preenchimento apenas do step 1
        $step1Data = [
            'wedding_date' => '2026-12-25',
            'partner_name' => '',
            'partner_email' => '',
        ];

        Onboarding::saveToSession($step1Data);

        // Simular navegação para step 2 e adicionar mais dados
        $step2Data = array_merge(Onboarding::getFromSession(), [
            'venue_name' => 'Fazenda Bela Vista',
            'venue_city' => 'Campinas',
        ]);

        Onboarding::saveToSession($step2Data);

        // Verificar que todos os dados estão preservados
        $savedData = Onboarding::getFromSession();
        
        $this->assertEquals('2026-12-25', $savedData['wedding_date']);
        $this->assertEquals('Fazenda Bela Vista', $savedData['venue_name']);
        $this->assertEquals('Campinas', $savedData['venue_city']);
    }

    /**
     * Propriedade 10: Sessão vazia deve retornar array vazio.
     * 
     * Valida: Requisitos 1.3, 8.1
     */
    #[Test]
    public function empty_session_returns_empty_array(): void
    {
        // Garantir que não há dados na sessão
        Onboarding::clearSession();

        $data = Onboarding::getFromSession();

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    /**
     * Propriedade 11: Dados do onboarding devem ser limpos após logout.
     * 
     * Valida: Requisito 8.3
     */
    #[Test]
    public function onboarding_session_is_cleared_on_logout(): void
    {
        $user = User::factory()->create();

        // Salvar dados na sessão
        $testData = [
            'wedding_date' => '2026-08-20',
            'partner_name' => 'João Santos',
            'partner_email' => 'joao@example.com',
        ];
        Onboarding::saveToSession($testData);

        // Verificar que dados existem
        $this->assertNotEmpty(Onboarding::getFromSession());

        // Simular evento de logout
        $listener = new ClearOnboardingSession();
        $listener->handle(new Logout('web', $user));

        // Verificar que dados foram limpos
        $this->assertEmpty(Onboarding::getFromSession());
    }

    /**
     * Propriedade 11: Múltiplos logouts não devem causar erro.
     * 
     * Valida: Requisito 8.3
     */
    #[Test]
    public function multiple_logouts_do_not_cause_error(): void
    {
        $user = User::factory()->create();
        $listener = new ClearOnboardingSession();

        // Primeiro logout com dados
        Onboarding::saveToSession(['wedding_date' => '2026-01-01']);
        $listener->handle(new Logout('web', $user));

        // Segundo logout sem dados (já limpo)
        $listener->handle(new Logout('web', $user));

        // Terceiro logout
        $listener->handle(new Logout('web', $user));

        // Não deve lançar exceção e sessão deve estar vazia
        $this->assertEmpty(Onboarding::getFromSession());
    }

    /**
     * Propriedade 10: Atualização de dados deve sobrescrever valores anteriores.
     * 
     * Valida: Requisitos 1.3, 8.2
     */
    #[Test]
    public function session_data_can_be_updated(): void
    {
        // Dados iniciais
        Onboarding::saveToSession([
            'wedding_date' => '2026-03-15',
            'plan' => 'basic',
        ]);

        // Atualizar dados
        Onboarding::saveToSession([
            'wedding_date' => '2026-04-20',
            'plan' => 'premium',
        ]);

        $savedData = Onboarding::getFromSession();

        $this->assertEquals('2026-04-20', $savedData['wedding_date']);
        $this->assertEquals('premium', $savedData['plan']);
    }
}
