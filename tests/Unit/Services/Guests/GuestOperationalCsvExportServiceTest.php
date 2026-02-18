<?php

namespace Tests\Unit\Services\Guests;

use App\Services\Guests\GuestOperationalCsvExportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class GuestOperationalCsvExportServiceTest extends TestCase
{
    #[Test]
    public function it_streams_dashboard_data_as_csv(): void
    {
        $service = app(GuestOperationalCsvExportService::class);

        $dashboardData = [
            'metrics' => [
                'total_invites' => 10,
                'opened_invites' => 6,
                'open_rate' => 60,
                'revoked_rate' => 10,
                'active_invites' => 7,
                'expired_invites' => 2,
                'revoked_invites' => 1,
                'pending_interaction' => 3,
                'total_invite_uses' => 8,
                'total_guests' => 12,
                'confirmed_guests' => 7,
                'maybe_guests' => 2,
                'declined_guests' => 1,
                'no_response_guests' => 2,
                'total_events' => 3,
                'active_events' => 2,
                'total_checkins' => 5,
                'unique_checked_in_guests' => 4,
                'pending_checkin_guests' => 8,
                'checkins_today' => 2,
            ],
            'channel_breakdown' => collect([
                ['channel' => 'email', 'total' => 6, 'opened' => 4, 'open_rate' => 66.67, 'uses' => 5],
                ['channel' => 'whatsapp', 'total' => 4, 'opened' => 2, 'open_rate' => 50, 'uses' => 3],
            ]),
            'rsvp_status_breakdown' => collect([
                'confirmed' => 7,
                'maybe' => 2,
                'declined' => 1,
                'no_response' => 2,
            ]),
            'checkin_breakdown' => collect([
                ['method' => 'qr', 'label' => 'QR', 'total' => 3],
                ['method' => 'manual', 'label' => 'Manual', 'total' => 2],
            ]),
            'recent_checkins' => collect([
                [
                    'checked_in_at' => Carbon::parse('2026-02-10 18:30:00'),
                    'guest' => 'Ana',
                    'event' => 'Cerimonia',
                    'method' => 'qr',
                    'method_label' => 'QR',
                    'operator' => 'Equipe Porta A',
                ],
            ]),
            'recent_invites' => collect([
                [
                    'created_at' => Carbon::parse('2026-02-10 12:00:00'),
                    'household' => 'Familia Lima',
                    'guest' => 'Ana',
                    'channel' => 'email',
                    'status' => 'opened',
                    'uses_count' => 1,
                    'max_uses' => 2,
                    'expires_at' => Carbon::parse('2026-02-20 12:00:00'),
                ],
            ]),
            'forecast_insights' => collect([
                [
                    'level' => 'info',
                    'title' => 'Cobertura de respostas',
                    'summary' => '8 de 12 convidados responderam RSVP (66,67%).',
                ],
                [
                    'level' => 'warning',
                    'title' => 'Prioridade de follow-up',
                    'summary' => '2 convidados sem resposta e 3 convites sem interação.',
                ],
            ]),
            'alerts' => collect([
                [
                    'level' => 'warning',
                    'title' => 'Taxa baixa',
                    'description' => 'Abertura abaixo do esperado.',
                ],
            ]),
        ];

        $response = $service->streamDownload(
            dashboardData: $dashboardData,
            range: '30d',
            rangeLabel: 'Ultimos 30 dias',
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('dashboard_operacional_rsvp_30d_', (string) $response->headers->get('content-disposition'));

        $csv = $this->captureStreamedResponse($response);

        $this->assertStringContainsString('Relatorio Operacional RSVP', $csv);
        $this->assertStringContainsString('Periodo;', $csv);
        $this->assertStringContainsString('Ultimos 30 dias', $csv);
        $this->assertStringContainsString('Total de convites', $csv);
        $this->assertStringContainsString('66,67%', $csv);
        $this->assertStringContainsString('Check-ins (periodo)', $csv);
        $this->assertStringContainsString('Check-in por Metodo', $csv);
        $this->assertStringContainsString('Ultimos Check-ins', $csv);
        $this->assertStringContainsString('Insights de Previsao', $csv);
        $this->assertStringContainsString('Cobertura de respostas', $csv);
        $this->assertStringContainsString('Equipe Porta A', $csv);
        $this->assertStringContainsString('Familia Lima', $csv);
        $this->assertStringContainsString('Ana', $csv);
        $this->assertStringContainsString('1/2', $csv);
        $this->assertStringContainsString('WARNING', $csv);
        $this->assertStringContainsString('Taxa baixa', $csv);
        $this->assertStringContainsString('Abertura abaixo do esperado.', $csv);
    }

    #[Test]
    public function it_handles_empty_collections_without_errors(): void
    {
        $service = app(GuestOperationalCsvExportService::class);

        $response = $service->streamDownload(
            dashboardData: [
                'metrics' => [],
                'channel_breakdown' => new Collection(),
                'rsvp_status_breakdown' => new Collection(),
                'checkin_breakdown' => new Collection(),
                'recent_invites' => new Collection(),
                'recent_checkins' => new Collection(),
                'forecast_insights' => new Collection(),
                'alerts' => new Collection(),
            ],
            range: 'all',
            rangeLabel: 'Todo periodo',
        );

        $csv = $this->captureStreamedResponse($response);

        $this->assertStringContainsString('Periodo;', $csv);
        $this->assertStringContainsString('Todo periodo', $csv);
        $this->assertStringContainsString('Total de convites', $csv);
        $this->assertStringContainsString('Status RSVP por Evento', $csv);
        $this->assertStringContainsString('Check-in por Metodo', $csv);
        $this->assertStringContainsString('Insights de Previsao', $csv);
        $this->assertStringContainsString('Alertas Operacionais', $csv);
    }

    private function captureStreamedResponse(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}
