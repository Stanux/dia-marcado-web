<?php

namespace App\Services\Guests;

use App\Models\Guest;
use App\Models\GuestCheckin;
use App\Models\GuestEvent;
use App\Models\GuestInvite;
use App\Models\GuestRsvp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class GuestOperationalMetricsService
{
    private ?bool $hasUsesCountColumn = null;
    private ?bool $hasMaxUsesColumn = null;
    private ?bool $hasRevokedAtColumn = null;
    private ?bool $hasOverallRsvpStatusColumn = null;

    public function forWedding(string $weddingId, string $range = '30d'): array
    {
        $rangeStart = $this->resolveRangeStart($range);
        $now = now();

        $invitesQuery = $this->invitesQuery($weddingId);

        if ($rangeStart) {
            $invitesQuery->where('created_at', '>=', $rangeStart);
        }

        $totalInvites = (int) (clone $invitesQuery)->count();
        $openedInvites = (int) (clone $invitesQuery)->where(function (Builder $query): void {
            $query->where('status', 'opened')
                ->orWhereNotNull('used_at');
        })->count();

        $revokedInvites = (int) (clone $invitesQuery)
            ->when(
                $this->hasRevokedAtColumn(),
                fn (Builder $query): Builder => $query->whereNotNull('revoked_at'),
                fn (Builder $query): Builder => $query->where('status', 'revoked'),
            )
            ->count();

        $expiredInvites = (int) (clone $invitesQuery)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->when(
                $this->hasRevokedAtColumn(),
                fn (Builder $query): Builder => $query->whereNull('revoked_at'),
                fn (Builder $query): Builder => $query->where('status', '!=', 'revoked'),
            )
            ->count();

        $activeInvites = (int) (clone $invitesQuery)
            ->when(
                $this->hasRevokedAtColumn(),
                fn (Builder $query): Builder => $query->whereNull('revoked_at'),
                fn (Builder $query): Builder => $query->where('status', '!=', 'revoked'),
            )
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->when($this->hasMaxUsesColumn() && $this->hasUsesCountColumn(), function (Builder $query): Builder {
                return $query->where(function (Builder $availability): void {
                    $availability->whereNull('max_uses')
                        ->orWhereColumn('uses_count', '<', 'max_uses');
                });
            })
            ->count();

        $pendingInteraction = (int) (clone $invitesQuery)
            ->when(
                $this->hasRevokedAtColumn(),
                fn (Builder $query): Builder => $query->whereNull('revoked_at'),
                fn (Builder $query): Builder => $query->where('status', '!=', 'revoked'),
            )
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->when(
                $this->hasUsesCountColumn(),
                fn (Builder $query): Builder => $query->where('uses_count', 0),
                fn (Builder $query): Builder => $query->whereNull('used_at'),
            )
            ->count();

        $totalInviteUses = (int) ($this->hasUsesCountColumn()
            ? (clone $invitesQuery)->sum('uses_count')
            : (clone $invitesQuery)->whereNotNull('used_at')->count());

        $channelBreakdown = (clone $invitesQuery)
            ->selectRaw("
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'opened' OR used_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                " . ($this->hasUsesCountColumn() ? "COALESCE(SUM(uses_count), 0)" : "SUM(CASE WHEN used_at IS NOT NULL THEN 1 ELSE 0 END)") . " as uses
            ")
            ->groupBy('channel')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'channel' => $row->channel ?: 'unknown',
                'total' => (int) $row->total,
                'opened' => (int) $row->opened,
                'uses' => (int) $row->uses,
                'open_rate' => (int) $row->total > 0
                    ? round(((int) $row->opened / (int) $row->total) * 100, 2)
                    : 0,
            ])
            ->values();

        $recentInvites = $this->invitesQuery($weddingId)
            ->with(['household:id,name', 'guest:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (GuestInvite $invite): array => [
                'id' => $invite->id,
                'household' => $invite->household?->name ?: 'Núcleo removido',
                'guest' => $invite->guest?->name,
                'channel' => $invite->channel ?: 'unknown',
                'status' => $invite->status ?: 'unknown',
                'created_at' => $invite->created_at,
                'expires_at' => $invite->expires_at,
                'used_at' => $invite->used_at,
                'uses_count' => (int) ($invite->uses_count ?? 0),
                'max_uses' => $invite->max_uses,
            ])
            ->values();

        $guestQuery = Guest::withoutGlobalScopes()->where('wedding_id', $weddingId);
        $totalGuests = (int) (clone $guestQuery)->count();

        if ($this->hasOverallRsvpStatusColumn()) {
            $guestStatusCounts = (clone $guestQuery)
                ->selectRaw('overall_rsvp_status as status, COUNT(*) as total')
                ->groupBy('overall_rsvp_status')
                ->pluck('total', 'status');

            $confirmedGuests = (int) ($guestStatusCounts['confirmed'] ?? 0);
            $declinedGuests = (int) ($guestStatusCounts['declined'] ?? 0);
            $maybeGuests = (int) ($guestStatusCounts['maybe'] ?? 0);
            $noResponseGuests = (int) ($guestStatusCounts['no_response'] ?? 0);
        } else {
            $guestStatusCounts = (clone $guestQuery)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $confirmedGuests = (int) ($guestStatusCounts['confirmed'] ?? 0);
            $declinedGuests = (int) ($guestStatusCounts['declined'] ?? 0);
            $maybeGuests = (int) ($guestStatusCounts['maybe'] ?? 0);
            $noResponseGuests = (int) ($guestStatusCounts['pending'] ?? 0);
        }

        $checkinsQuery = GuestCheckin::query()
            ->whereHas('guest', fn (Builder $query) => $query->withoutGlobalScopes()->where('wedding_id', $weddingId));

        if ($rangeStart) {
            $checkinsQuery->where('checked_in_at', '>=', $rangeStart);
        }

        $totalCheckins = (int) (clone $checkinsQuery)->count();
        $uniqueCheckedInGuests = (int) (clone $checkinsQuery)->distinct()->count('guest_id');
        $pendingCheckinGuests = max(0, $totalGuests - $uniqueCheckedInGuests);
        $checkinsToday = (int) GuestCheckin::query()
            ->whereHas('guest', fn (Builder $query) => $query->withoutGlobalScopes()->where('wedding_id', $weddingId))
            ->where('checked_in_at', '>=', now()->startOfDay())
            ->count();

        $checkinBreakdown = (clone $checkinsQuery)
            ->selectRaw('method, COUNT(*) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'method' => $row->method ?: 'manual',
                'label' => $this->checkinMethodLabel($row->method ?: 'manual'),
                'total' => (int) $row->total,
            ])
            ->values();

        $recentCheckins = GuestCheckin::query()
            ->whereHas('guest', fn (Builder $query) => $query->withoutGlobalScopes()->where('wedding_id', $weddingId))
            ->with([
                'guest' => fn ($query) => $query->withoutGlobalScopes()->select(['id', 'name', 'wedding_id']),
                'event' => fn ($query) => $query->withoutGlobalScopes()->select(['id', 'name', 'wedding_id']),
                'operator:id,name',
            ])
            ->orderByDesc('checked_in_at')
            ->limit(10)
            ->get()
            ->map(fn (GuestCheckin $checkin): array => [
                'id' => $checkin->id,
                'checked_in_at' => $checkin->checked_in_at,
                'method' => $checkin->method ?: 'manual',
                'method_label' => $this->checkinMethodLabel($checkin->method ?: 'manual'),
                'guest' => $checkin->guest?->name ?: 'Convidado removido',
                'event' => $checkin->event?->name ?: 'Sem evento',
                'operator' => $checkin->operator?->name ?: 'Sem operador',
            ])
            ->values();

        $eventsQuery = GuestEvent::withoutGlobalScopes()->where('wedding_id', $weddingId);
        $totalEvents = (int) (clone $eventsQuery)->count();
        $activeEvents = (int) (clone $eventsQuery)->where('is_active', true)->count();

        $rsvpStatusBreakdown = GuestRsvp::query()
            ->whereHas('guest', fn (Builder $query) => $query->withoutGlobalScopes()->where('wedding_id', $weddingId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $rsvpStatusBreakdown = collect([
            GuestRsvp::STATUS_CONFIRMED => (int) ($rsvpStatusBreakdown[GuestRsvp::STATUS_CONFIRMED] ?? 0),
            GuestRsvp::STATUS_DECLINED => (int) ($rsvpStatusBreakdown[GuestRsvp::STATUS_DECLINED] ?? 0),
            GuestRsvp::STATUS_MAYBE => (int) ($rsvpStatusBreakdown[GuestRsvp::STATUS_MAYBE] ?? 0),
            GuestRsvp::STATUS_NO_RESPONSE => (int) ($rsvpStatusBreakdown[GuestRsvp::STATUS_NO_RESPONSE] ?? 0),
        ]);

        $openRate = $totalInvites > 0 ? round(($openedInvites / $totalInvites) * 100, 2) : 0;
        $revokedRate = $totalInvites > 0 ? round(($revokedInvites / $totalInvites) * 100, 2) : 0;
        $forecastInsights = $this->buildForecastInsights(
            totalGuests: $totalGuests,
            confirmedGuests: $confirmedGuests,
            maybeGuests: $maybeGuests,
            noResponseGuests: $noResponseGuests,
            pendingInteraction: $pendingInteraction,
            openRate: $openRate,
        );

        return [
            'metrics' => [
                'total_invites' => $totalInvites,
                'opened_invites' => $openedInvites,
                'open_rate' => $openRate,
                'revoked_rate' => $revokedRate,
                'active_invites' => $activeInvites,
                'expired_invites' => $expiredInvites,
                'revoked_invites' => $revokedInvites,
                'pending_interaction' => $pendingInteraction,
                'total_invite_uses' => $totalInviteUses,
                'total_events' => $totalEvents,
                'active_events' => $activeEvents,
                'total_guests' => $totalGuests,
                'confirmed_guests' => $confirmedGuests,
                'declined_guests' => $declinedGuests,
                'maybe_guests' => $maybeGuests,
                'no_response_guests' => $noResponseGuests,
                'total_checkins' => $totalCheckins,
                'unique_checked_in_guests' => $uniqueCheckedInGuests,
                'pending_checkin_guests' => $pendingCheckinGuests,
                'checkins_today' => $checkinsToday,
            ],
            'channel_breakdown' => $channelBreakdown,
            'rsvp_status_breakdown' => $rsvpStatusBreakdown,
            'checkin_breakdown' => $checkinBreakdown,
            'recent_checkins' => $recentCheckins,
            'recent_invites' => $recentInvites,
            'forecast_insights' => $forecastInsights,
            'alerts' => $this->buildAlerts(
                totalInvites: $totalInvites,
                openRate: $openRate,
                revokedRate: $revokedRate,
                expiredInvites: $expiredInvites,
                pendingInteraction: $pendingInteraction,
                totalGuests: $totalGuests,
                confirmedGuests: $confirmedGuests,
                noResponseGuests: $noResponseGuests,
                totalCheckins: $totalCheckins,
                uniqueCheckedInGuests: $uniqueCheckedInGuests,
            ),
            'range_start' => $rangeStart,
        ];
    }

    private function buildForecastInsights(
        int $totalGuests,
        int $confirmedGuests,
        int $maybeGuests,
        int $noResponseGuests,
        int $pendingInteraction,
        float $openRate,
    ): Collection {
        if ($totalGuests <= 0) {
            return collect([
                [
                    'level' => 'info',
                    'title' => 'Sem base para previsão',
                    'summary' => 'Cadastre convidados e convites para iniciar os cenários de presença.',
                    'meta' => [
                        'response_rate' => 0.0,
                        'conservative' => 0,
                        'baseline' => 0,
                        'optimistic' => 0,
                        'no_response_guests' => 0,
                    ],
                ],
            ]);
        }

        $respondedGuests = max(0, $totalGuests - $noResponseGuests);
        $responseRate = round(($respondedGuests / $totalGuests) * 100, 2);
        $conservative = min($totalGuests, $confirmedGuests + (int) floor($maybeGuests * 0.25));
        $baseline = min($totalGuests, $confirmedGuests + (int) round($maybeGuests * 0.5) + (int) floor($noResponseGuests * 0.15));
        $optimistic = min($totalGuests, $confirmedGuests + $maybeGuests + (int) floor($noResponseGuests * 0.35));
        $noResponseRate = round(($noResponseGuests / $totalGuests) * 100, 2);

        $coverageLevel = match (true) {
            $responseRate < 50 => 'warning',
            $responseRate < 75 => 'info',
            default => 'success',
        };

        $followUpLevel = match (true) {
            $noResponseRate >= 40 => 'warning',
            $noResponseRate >= 20 || ($pendingInteraction > 0 && $openRate < 30) => 'info',
            default => 'success',
        };

        return collect([
            [
                'level' => $coverageLevel,
                'title' => 'Cobertura de respostas',
                'summary' => "{$respondedGuests} de {$totalGuests} convidados responderam RSVP ({$responseRate}%).",
                'meta' => [
                    'response_rate' => $responseRate,
                    'responded_guests' => $respondedGuests,
                    'total_guests' => $totalGuests,
                ],
            ],
            [
                'level' => 'info',
                'title' => 'Projeção de presença',
                'summary' => "Cenários estimados: conservador {$conservative}, base {$baseline}, otimista {$optimistic} convidados.",
                'meta' => [
                    'conservative' => $conservative,
                    'baseline' => $baseline,
                    'optimistic' => $optimistic,
                ],
            ],
            [
                'level' => $followUpLevel,
                'title' => 'Prioridade de follow-up',
                'summary' => "{$noResponseGuests} sem resposta ({$noResponseRate}%) e {$pendingInteraction} convite(s) sem interação.",
                'meta' => [
                    'no_response_guests' => $noResponseGuests,
                    'no_response_rate' => $noResponseRate,
                    'pending_interaction' => $pendingInteraction,
                    'open_rate' => $openRate,
                ],
            ],
        ])->values();
    }

    private function invitesQuery(string $weddingId): Builder
    {
        return GuestInvite::query()
            ->whereHas('household', fn (Builder $query) => $query->withoutGlobalScopes()->where('wedding_id', $weddingId));
    }

    private function buildAlerts(
        int $totalInvites,
        float $openRate,
        float $revokedRate,
        int $expiredInvites,
        int $pendingInteraction,
        int $totalGuests,
        int $confirmedGuests,
        int $noResponseGuests,
        int $totalCheckins,
        int $uniqueCheckedInGuests,
    ): Collection {
        $alerts = collect();

        if ($totalInvites === 0) {
            $alerts->push([
                'level' => 'info',
                'title' => 'Nenhum convite enviado no período',
                'description' => 'Gere convites para começar a captar confirmações.',
            ]);
        }

        if ($totalInvites > 0 && $openRate < 30) {
            $alerts->push([
                'level' => 'warning',
                'title' => 'Taxa de abertura baixa',
                'description' => "A abertura está em {$openRate}%. Considere reemitir ou trocar canal dos convites.",
            ]);
        }

        if ($expiredInvites > 0) {
            $alerts->push([
                'level' => 'warning',
                'title' => 'Convites expirados',
                'description' => "{$expiredInvites} convite(s) expirado(s). Avalie reemissão para evitar perda de resposta.",
            ]);
        }

        if ($revokedRate >= 20) {
            $alerts->push([
                'level' => 'warning',
                'title' => 'Taxa alta de revogação',
                'description' => "Revogações em {$revokedRate}% do volume. Verifique qualidade da lista e fluxo de envio.",
            ]);
        }

        if ($pendingInteraction > 0) {
            $alerts->push([
                'level' => 'info',
                'title' => 'Convites sem interação',
                'description' => "{$pendingInteraction} convite(s) ativo(s) ainda sem abertura/uso.",
            ]);
        }

        if ($totalGuests > 0 && $confirmedGuests === 0) {
            $alerts->push([
                'level' => 'warning',
                'title' => 'Sem confirmações de presença',
                'description' => 'Ainda não há convidados confirmados. Reforce a comunicação do RSVP.',
            ]);
        } elseif ($noResponseGuests > 0 && $totalGuests > 0) {
            $alerts->push([
                'level' => 'info',
                'title' => 'Convidados sem resposta',
                'description' => "{$noResponseGuests} convidado(s) ainda não responderam RSVP.",
            ]);
        }

        if ($totalGuests > 0 && $uniqueCheckedInGuests === 0 && $totalCheckins === 0) {
            $alerts->push([
                'level' => 'info',
                'title' => 'Nenhum check-in registrado',
                'description' => 'Ainda não há check-ins registrados no período selecionado.',
            ]);
        }

        return $alerts->values();
    }

    private function resolveRangeStart(string $range): ?\Illuminate\Support\Carbon
    {
        return match ($range) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            'all' => null,
            default => now()->subDays(30),
        };
    }

    private function hasUsesCountColumn(): bool
    {
        if ($this->hasUsesCountColumn === null) {
            $this->hasUsesCountColumn = Schema::hasColumn('guest_invites', 'uses_count');
        }

        return $this->hasUsesCountColumn;
    }

    private function hasMaxUsesColumn(): bool
    {
        if ($this->hasMaxUsesColumn === null) {
            $this->hasMaxUsesColumn = Schema::hasColumn('guest_invites', 'max_uses');
        }

        return $this->hasMaxUsesColumn;
    }

    private function hasRevokedAtColumn(): bool
    {
        if ($this->hasRevokedAtColumn === null) {
            $this->hasRevokedAtColumn = Schema::hasColumn('guest_invites', 'revoked_at');
        }

        return $this->hasRevokedAtColumn;
    }

    private function hasOverallRsvpStatusColumn(): bool
    {
        if ($this->hasOverallRsvpStatusColumn === null) {
            $this->hasOverallRsvpStatusColumn = Schema::hasColumn('guests', 'overall_rsvp_status');
        }

        return $this->hasOverallRsvpStatusColumn;
    }

    private function checkinMethodLabel(string $method): string
    {
        return match ($method) {
            'qr' => 'QR',
            'manual' => 'Manual',
            default => ucfirst($method),
        };
    }
}
