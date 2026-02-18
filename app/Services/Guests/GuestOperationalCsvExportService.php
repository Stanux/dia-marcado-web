<?php

namespace App\Services\Guests;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestOperationalCsvExportService
{
    public function streamDownload(array $dashboardData, string $range, string $rangeLabel): StreamedResponse
    {
        $filename = 'dashboard_operacional_rsvp_' . $range . '_' . now()->format('Y-m-d_His') . '.csv';

        $metrics = $dashboardData['metrics'] ?? [];
        $channelBreakdown = $this->asCollection($dashboardData['channel_breakdown'] ?? []);
        $rsvpBreakdown = $this->asCollection($dashboardData['rsvp_status_breakdown'] ?? []);
        $checkinBreakdown = $this->asCollection($dashboardData['checkin_breakdown'] ?? []);
        $recentInvites = $this->asCollection($dashboardData['recent_invites'] ?? []);
        $recentCheckins = $this->asCollection($dashboardData['recent_checkins'] ?? []);
        $forecastInsights = $this->asCollection($dashboardData['forecast_insights'] ?? []);
        $alerts = $this->asCollection($dashboardData['alerts'] ?? []);

        return response()->streamDownload(function () use (
            $rangeLabel,
            $metrics,
            $channelBreakdown,
            $rsvpBreakdown,
            $checkinBreakdown,
            $recentInvites,
            $recentCheckins,
            $forecastInsights,
            $alerts
        ): void {
            $file = fopen('php://output', 'w');

            if (!$file) {
                return;
            }

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['Relatorio Operacional RSVP'], ';');
            fputcsv($file, ['Periodo', $rangeLabel], ';');
            fputcsv($file, ['Gerado em', now()->format('d/m/Y H:i:s')], ';');
            fputcsv($file, [], ';');

            fputcsv($file, ['Metricas Gerais'], ';');
            fputcsv($file, ['Indicador', 'Valor'], ';');
            fputcsv($file, ['Total de convites', (int) ($metrics['total_invites'] ?? 0)], ';');
            fputcsv($file, ['Convites abertos', (int) ($metrics['opened_invites'] ?? 0)], ';');
            fputcsv($file, ['Taxa de abertura', $this->formatPercent($metrics['open_rate'] ?? 0)], ';');
            fputcsv($file, ['Taxa de revogacao', $this->formatPercent($metrics['revoked_rate'] ?? 0)], ';');
            fputcsv($file, ['Convites ativos', (int) ($metrics['active_invites'] ?? 0)], ';');
            fputcsv($file, ['Convites expirados', (int) ($metrics['expired_invites'] ?? 0)], ';');
            fputcsv($file, ['Convites revogados', (int) ($metrics['revoked_invites'] ?? 0)], ';');
            fputcsv($file, ['Pendentes de interacao', (int) ($metrics['pending_interaction'] ?? 0)], ';');
            fputcsv($file, ['Usos de convite', (int) ($metrics['total_invite_uses'] ?? 0)], ';');
            fputcsv($file, ['Total de convidados', (int) ($metrics['total_guests'] ?? 0)], ';');
            fputcsv($file, ['Confirmados', (int) ($metrics['confirmed_guests'] ?? 0)], ';');
            fputcsv($file, ['Talvez', (int) ($metrics['maybe_guests'] ?? 0)], ';');
            fputcsv($file, ['Recusados', (int) ($metrics['declined_guests'] ?? 0)], ';');
            fputcsv($file, ['Sem resposta', (int) ($metrics['no_response_guests'] ?? 0)], ';');
            fputcsv($file, ['Total de eventos', (int) ($metrics['total_events'] ?? 0)], ';');
            fputcsv($file, ['Eventos ativos', (int) ($metrics['active_events'] ?? 0)], ';');
            fputcsv($file, ['Check-ins (periodo)', (int) ($metrics['total_checkins'] ?? 0)], ';');
            fputcsv($file, ['Convidados com check-in', (int) ($metrics['unique_checked_in_guests'] ?? 0)], ';');
            fputcsv($file, ['Convidados sem check-in', (int) ($metrics['pending_checkin_guests'] ?? 0)], ';');
            fputcsv($file, ['Check-ins hoje', (int) ($metrics['checkins_today'] ?? 0)], ';');
            fputcsv($file, [], ';');

            fputcsv($file, ['Insights de Previsao'], ';');
            fputcsv($file, ['Nivel', 'Titulo', 'Resumo'], ';');
            foreach ($forecastInsights as $insight) {
                fputcsv($file, [
                    strtoupper((string) ($insight['level'] ?? 'info')),
                    $insight['title'] ?? '',
                    $insight['summary'] ?? '',
                ], ';');
            }
            fputcsv($file, [], ';');

            fputcsv($file, ['Desempenho por Canal'], ';');
            fputcsv($file, ['Canal', 'Convites', 'Abertos', 'Taxa', 'Usos'], ';');
            foreach ($channelBreakdown as $row) {
                fputcsv($file, [
                    $this->channelLabel($row['channel'] ?? null),
                    (int) ($row['total'] ?? 0),
                    (int) ($row['opened'] ?? 0),
                    $this->formatPercent($row['open_rate'] ?? 0),
                    (int) ($row['uses'] ?? 0),
                ], ';');
            }
            fputcsv($file, [], ';');

            fputcsv($file, ['Status RSVP por Evento'], ';');
            fputcsv($file, ['Status', 'Total'], ';');
            fputcsv($file, ['Confirmado', (int) ($rsvpBreakdown['confirmed'] ?? 0)], ';');
            fputcsv($file, ['Talvez', (int) ($rsvpBreakdown['maybe'] ?? 0)], ';');
            fputcsv($file, ['Recusado', (int) ($rsvpBreakdown['declined'] ?? 0)], ';');
            fputcsv($file, ['Sem resposta', (int) ($rsvpBreakdown['no_response'] ?? 0)], ';');
            fputcsv($file, [], ';');

            fputcsv($file, ['Check-in por Metodo'], ';');
            fputcsv($file, ['Metodo', 'Quantidade'], ';');
            foreach ($checkinBreakdown as $row) {
                fputcsv($file, [
                    $row['label'] ?? $this->checkinMethodLabel($row['method'] ?? null),
                    (int) ($row['total'] ?? 0),
                ], ';');
            }
            fputcsv($file, [], ';');

            fputcsv($file, ['Ultimos Check-ins'], ';');
            fputcsv($file, ['Horario', 'Convidado', 'Evento', 'Metodo', 'Operador'], ';');
            foreach ($recentCheckins as $checkin) {
                fputcsv($file, [
                    $this->formatDateTime($checkin['checked_in_at'] ?? null),
                    $checkin['guest'] ?? '',
                    $checkin['event'] ?? '',
                    $checkin['method_label'] ?? $this->checkinMethodLabel($checkin['method'] ?? null),
                    $checkin['operator'] ?? '',
                ], ';');
            }
            fputcsv($file, [], ';');

            fputcsv($file, ['Ultimos Convites'], ';');
            fputcsv($file, ['Gerado em', 'Nucleo', 'Convidado', 'Canal', 'Status', 'Usos', 'Expira em'], ';');
            foreach ($recentInvites as $invite) {
                $usesCount = (int) ($invite['uses_count'] ?? 0);
                $maxUses = $invite['max_uses'] ?? null;
                $usesSummary = $maxUses === null || $maxUses === '' ? "{$usesCount}/ilimitado" : "{$usesCount}/{$maxUses}";

                fputcsv($file, [
                    $this->formatDateTime($invite['created_at'] ?? null),
                    $invite['household'] ?? '',
                    ($invite['guest'] ?? '') ?: 'Nucleo',
                    $this->channelLabel($invite['channel'] ?? null),
                    $this->statusLabel($invite['status'] ?? null),
                    $usesSummary,
                    $this->formatDateTime($invite['expires_at'] ?? null, 'Sem expiracao'),
                ], ';');
            }
            fputcsv($file, [], ';');

            fputcsv($file, ['Alertas Operacionais'], ';');
            fputcsv($file, ['Nivel', 'Titulo', 'Descricao'], ';');
            foreach ($alerts as $alert) {
                fputcsv($file, [
                    strtoupper((string) ($alert['level'] ?? 'info')),
                    $alert['title'] ?? '',
                    $alert['description'] ?? '',
                ], ';');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    private function formatDateTime(mixed $value, string $empty = ''): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('d/m/Y H:i');
        }

        if (is_string($value) && trim($value) !== '') {
            return (string) $value;
        }

        return $empty;
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

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'sent' => 'Enviado',
            'delivered' => 'Entregue',
            'opened' => 'Aberto',
            'expired' => 'Expirado',
            'revoked' => 'Revogado',
            default => $status ?: 'Desconhecido',
        };
    }

    private function checkinMethodLabel(?string $method): string
    {
        return match ($method) {
            'qr' => 'QR',
            'manual' => 'Manual',
            default => ucfirst($method ?: 'manual'),
        };
    }
}
