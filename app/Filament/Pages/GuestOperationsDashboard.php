<?php

namespace App\Filament\Pages;

use App\Models\Wedding;
use App\Services\Guests\GuestOperationalCsvExportService;
use App\Services\Guests\GuestOperationalMetricsService;
use App\Services\Guests\GuestOperationalPdfExportService;
use App\Services\Guests\InviteIncidentService;
use App\Services\PermissionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestOperationsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Dashboard RSVP';

    protected static ?string $navigationGroup = 'CONVIDADOS';

    protected static ?string $title = 'Dashboard Operacional RSVP';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'guest-operations-dashboard';

    protected static string $view = 'filament.pages.guest-operations-dashboard';

    public string $range = '30d';

    public function getRangeOptions(): array
    {
        return [
            '7d' => 'Últimos 7 dias',
            '30d' => 'Últimos 30 dias',
            '90d' => 'Últimos 90 dias',
            'all' => 'Todo período',
        ];
    }

    public function getDashboardData(): array
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            return [
                'metrics' => [
                    'total_invites' => 0,
                    'opened_invites' => 0,
                    'open_rate' => 0,
                    'revoked_rate' => 0,
                    'active_invites' => 0,
                    'expired_invites' => 0,
                    'revoked_invites' => 0,
                    'pending_interaction' => 0,
                    'total_invite_uses' => 0,
                    'total_events' => 0,
                    'active_events' => 0,
                    'total_guests' => 0,
                    'confirmed_guests' => 0,
                    'declined_guests' => 0,
                    'maybe_guests' => 0,
                    'no_response_guests' => 0,
                    'total_checkins' => 0,
                    'unique_checked_in_guests' => 0,
                    'pending_checkin_guests' => 0,
                    'checkins_today' => 0,
                ],
                'channel_breakdown' => collect(),
                'rsvp_status_breakdown' => collect(),
                'checkin_breakdown' => collect(),
                'recent_invites' => collect(),
                'recent_checkins' => collect(),
                'forecast_insights' => collect(),
                'alerts' => collect(),
                'incidents' => [
                    'failed_messages_total' => 0,
                    'failed_invites_total' => 0,
                    'failed_channels' => collect(),
                    'failed_queue' => collect(),
                ],
                'range_start' => null,
            ];
        }

        $dashboard = app(GuestOperationalMetricsService::class)->forWedding($weddingId, $this->range);
        $dashboard['incidents'] = app(InviteIncidentService::class)->forWedding($weddingId, $this->range);

        return $dashboard;
    }

    public function exportCsv(): ?StreamedResponse
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            Notification::make()
                ->title('Casamento nao selecionado')
                ->warning()
                ->body('Selecione um casamento para exportar os dados.')
                ->send();

            return null;
        }

        $dashboardData = app(GuestOperationalMetricsService::class)->forWedding($weddingId, $this->range);

        return app(GuestOperationalCsvExportService::class)->streamDownload(
            dashboardData: $dashboardData,
            range: $this->range,
            rangeLabel: $this->getRangeOptions()[$this->range] ?? $this->range,
        );
    }

    public function exportPdf(): ?StreamedResponse
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            Notification::make()
                ->title('Casamento nao selecionado')
                ->warning()
                ->body('Selecione um casamento para exportar os dados.')
                ->send();

            return null;
        }

        $dashboardData = app(GuestOperationalMetricsService::class)->forWedding($weddingId, $this->range);

        return app(GuestOperationalPdfExportService::class)->streamDownload(
            dashboardData: $dashboardData,
            range: $this->range,
            rangeLabel: $this->getRangeOptions()[$this->range] ?? $this->range,
        );
    }

    public function retryFailedInvite(string $inviteId): void
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            Notification::make()
                ->title('Casamento nao selecionado')
                ->warning()
                ->body('Selecione um casamento para reenviar convites.')
                ->send();

            return;
        }

        $result = app(InviteIncidentService::class)->retryInviteById(
            weddingId: $weddingId,
            inviteId: $inviteId,
            actorId: auth()->id(),
        );

        $notification = Notification::make()
            ->title($result['ok'] ? 'Convite reenviado' : 'Reenvio indisponível')
            ->body($result['message']);

        if ($result['ok']) {
            $notification->success();
        } else {
            $notification->warning();
        }

        $notification->send();
    }

    public function retryChannelFailures(string $channel): void
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            Notification::make()
                ->title('Casamento nao selecionado')
                ->warning()
                ->body('Selecione um casamento para reenviar falhas.')
                ->send();

            return;
        }

        $summary = app(InviteIncidentService::class)->retryFailedByChannel(
            weddingId: $weddingId,
            channel: $channel,
            range: $this->range,
            limit: 20,
            actorId: auth()->id(),
        );

        $notification = Notification::make()
            ->title('Reenvio por canal concluído')
            ->body(
                $this->channelLabel($channel) . ": {$summary['sent']} reenviado(s), " .
                    "{$summary['blocked']} bloqueado(s), {$summary['failed']} falha(s), {$summary['not_found']} não encontrado(s)."
            );

        if ($summary['sent'] > 0 && $summary['failed'] === 0) {
            $notification->success();
        } else {
            $notification->warning();
        }

        $notification->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $weddingId = session('filament_wedding_id') ?? $user?->current_wedding_id;

        if (!$user || !$weddingId) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $wedding = Wedding::find($weddingId);

        return $wedding && app(PermissionService::class)->canAccess($user, 'guests', $wedding);
    }

    private function getWeddingId(): ?string
    {
        return session('filament_wedding_id') ?? auth()->user()?->current_wedding_id;
    }

    private function channelLabel(string $channel): string
    {
        return match ($channel) {
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            default => ucfirst($channel),
        };
    }
}
