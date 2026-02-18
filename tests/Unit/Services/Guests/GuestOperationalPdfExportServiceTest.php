<?php

namespace Tests\Unit\Services\Guests;

use App\Services\Guests\GuestOperationalPdfExportService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class GuestOperationalPdfExportServiceTest extends TestCase
{
    #[Test]
    public function it_streams_dashboard_data_as_pdf(): void
    {
        $service = app(GuestOperationalPdfExportService::class);

        $response = $service->streamDownload(
            dashboardData: [
                'metrics' => [
                    'total_invites' => 10,
                    'opened_invites' => 6,
                    'open_rate' => 60,
                    'pending_interaction' => 3,
                    'total_guests' => 12,
                    'confirmed_guests' => 7,
                    'maybe_guests' => 2,
                    'no_response_guests' => 3,
                    'total_checkins' => 5,
                    'checkins_today' => 2,
                ],
                'channel_breakdown' => new Collection([
                    ['channel' => 'email', 'total' => 6, 'opened' => 4, 'open_rate' => 66.67, 'uses' => 5],
                ]),
                'forecast_insights' => new Collection([
                    [
                        'title' => 'Cobertura de respostas',
                        'summary' => '8 de 12 convidados responderam RSVP (66,67%).',
                    ],
                    [
                        'title' => 'Projeção de presença',
                        'summary' => 'Cenários estimados: conservador 7, base 8, otimista 10 convidados.',
                    ],
                ]),
                'alerts' => new Collection([
                    [
                        'title' => 'Taxa baixa',
                        'description' => 'Abertura abaixo do esperado.',
                    ],
                ]),
            ],
            range: '30d',
            rangeLabel: 'Ultimos 30 dias',
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('dashboard_operacional_rsvp_30d_', (string) $response->headers->get('content-disposition'));

        $pdf = $this->captureStreamedResponse($response);

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('Relatorio Operacional RSVP', $pdf);
        $this->assertStringContainsString('Cobertura de respostas', $pdf);
        $this->assertStringContainsString('Taxa baixa', $pdf);
        $this->assertStringContainsString('startxref', $pdf);
    }

    private function captureStreamedResponse(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}
