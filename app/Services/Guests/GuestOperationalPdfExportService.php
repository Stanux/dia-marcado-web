<?php

namespace App\Services\Guests;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestOperationalPdfExportService
{
    public function streamDownload(array $dashboardData, string $range, string $rangeLabel): StreamedResponse
    {
        $filename = 'dashboard_operacional_rsvp_' . $range . '_' . now()->format('Y-m-d_His') . '.pdf';
        $pdf = $this->buildPdf($dashboardData, $rangeLabel);

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function buildPdf(array $dashboardData, string $rangeLabel): string
    {
        $lines = $this->buildLines($dashboardData, $rangeLabel);

        return $this->renderPdfDocument($lines);
    }

    private function buildLines(array $dashboardData, string $rangeLabel): array
    {
        $metrics = $dashboardData['metrics'] ?? [];
        $channelBreakdown = $this->asCollection($dashboardData['channel_breakdown'] ?? []);
        $alerts = $this->asCollection($dashboardData['alerts'] ?? []);
        $forecastInsights = $this->asCollection($dashboardData['forecast_insights'] ?? []);

        $lines = [
            'Relatorio Operacional RSVP',
            'Periodo: ' . $rangeLabel,
            'Gerado em: ' . now()->format('d/m/Y H:i:s'),
            '',
            'Metricas gerais',
            'Convites totais: ' . (int) ($metrics['total_invites'] ?? 0),
            'Convites abertos: ' . (int) ($metrics['opened_invites'] ?? 0),
            'Taxa de abertura: ' . $this->formatPercent($metrics['open_rate'] ?? 0),
            'Pendentes de interacao: ' . (int) ($metrics['pending_interaction'] ?? 0),
            'Convidados totais: ' . (int) ($metrics['total_guests'] ?? 0),
            'Confirmados: ' . (int) ($metrics['confirmed_guests'] ?? 0),
            'Talvez: ' . (int) ($metrics['maybe_guests'] ?? 0),
            'Sem resposta: ' . (int) ($metrics['no_response_guests'] ?? 0),
            'Check-ins no periodo: ' . (int) ($metrics['total_checkins'] ?? 0),
            'Check-ins hoje: ' . (int) ($metrics['checkins_today'] ?? 0),
        ];

        $lines[] = '';
        $lines[] = 'Insights de previsao';
        if ($forecastInsights->isEmpty()) {
            $lines[] = '- Sem insights disponiveis para o periodo.';
        } else {
            foreach ($forecastInsights as $insight) {
                $title = (string) ($insight['title'] ?? 'Insight');
                $summary = (string) ($insight['summary'] ?? '');
                $lines[] = '- ' . $title . ': ' . $summary;
            }
        }

        $lines[] = '';
        $lines[] = 'Desempenho por canal';
        if ($channelBreakdown->isEmpty()) {
            $lines[] = '- Sem dados de canal no periodo.';
        } else {
            foreach ($channelBreakdown->take(8) as $row) {
                $channel = $this->channelLabel($row['channel'] ?? null);
                $total = (int) ($row['total'] ?? 0);
                $opened = (int) ($row['opened'] ?? 0);
                $rate = $this->formatPercent($row['open_rate'] ?? 0);
                $uses = (int) ($row['uses'] ?? 0);

                $lines[] = "- {$channel}: {$opened}/{$total} abertos ({$rate}), usos {$uses}";
            }
        }

        $lines[] = '';
        $lines[] = 'Alertas operacionais';
        if ($alerts->isEmpty()) {
            $lines[] = '- Sem alertas no periodo selecionado.';
        } else {
            foreach ($alerts->take(10) as $alert) {
                $title = (string) ($alert['title'] ?? 'Alerta');
                $description = (string) ($alert['description'] ?? '');
                $lines[] = '- ' . $title . ': ' . $description;
            }
        }

        return $lines;
    }

    private function renderPdfDocument(array $lines): string
    {
        $maxLines = 56;

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines - 1);
            $lines[] = '... relatorio resumido por limite de pagina.';
        }

        $content = "BT\n/F1 10 Tf\n40 800 Td\n13 TL\n";

        foreach (array_values($lines) as $index => $line) {
            if ($index > 0) {
                $content .= "T*\n";
            }

            $content .= '(' . $this->escapePdfString((string) $line) . ") Tj\n";
        }

        $content .= 'ET';

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            5 => "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);

        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= 5; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function asCollection(mixed $value): Collection
    {
        if ($value instanceof Collection) {
            return $value;
        }

        return collect($value);
    }

    private function formatPercent(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.') . '%';
    }

    private function channelLabel(?string $channel): string
    {
        return match ($channel) {
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            default => ucfirst($channel ?: 'desconhecido'),
        };
    }

    private function escapePdfString(string $value): string
    {
        $normalized = $this->normalizeText($value);

        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $normalized,
        );
    }

    private function normalizeText(string $value): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $text = $converted === false ? $value : $converted;

        return preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
    }
}
