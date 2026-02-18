<x-filament::page>
    @php
        $dashboard = $this->getDashboardData();
        $metrics = $dashboard['metrics'];
        $channelBreakdown = $dashboard['channel_breakdown'];
        $recentInvites = $dashboard['recent_invites'];
        $alerts = $dashboard['alerts'];
        $forecastInsights = $dashboard['forecast_insights'] ?? collect();
        $rsvpBreakdown = $dashboard['rsvp_status_breakdown'];
        $checkinBreakdown = $dashboard['checkin_breakdown'] ?? collect();
        $recentCheckins = $dashboard['recent_checkins'] ?? collect();
        $incidents = $dashboard['incidents'] ?? [
            'failed_messages_total' => 0,
            'failed_invites_total' => 0,
            'failed_channels' => collect(),
            'failed_queue' => collect(),
        ];
        $failedChannels = $incidents['failed_channels'] ?? collect();
        $failedQueue = $incidents['failed_queue'] ?? collect();
        $rangeOptions = $this->getRangeOptions();
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Operação de Convites e RSVP</h2>
                <p class="text-sm text-gray-500">Monitoramento de abertura, uso de links e status geral de respostas.</p>
            </div>

            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-end">
                <div class="w-full md:w-72">
                    <label class="text-sm font-medium text-gray-700">Período</label>
                    <select wire:model.live="range" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach ($rangeOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <x-filament::button
                    color="success"
                    icon="heroicon-o-arrow-down-tray"
                    wire:click="exportCsv"
                    wire:loading.attr="disabled"
                    wire:target="exportCsv"
                    class="w-full md:w-auto"
                >
                    Exportar CSV
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    icon="heroicon-o-document-arrow-down"
                    wire:click="exportPdf"
                    wire:loading.attr="disabled"
                    wire:target="exportPdf"
                    class="w-full md:w-auto"
                >
                    Exportar PDF
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Convites</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $metrics['total_invites'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $metrics['active_invites'] }} ativos</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Abertura</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($metrics['open_rate'], 2, ',', '.') }}%</p>
                <p class="mt-1 text-xs text-gray-500">{{ $metrics['opened_invites'] }} convite(s) aberto(s)</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Pendentes</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $metrics['pending_interaction'] }}</p>
                <p class="mt-1 text-xs text-gray-500">convites sem interação</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Risco Operacional</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $metrics['revoked_invites'] + $metrics['expired_invites'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $metrics['revoked_invites'] }} revogados · {{ $metrics['expired_invites'] }} expirados</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700">Insights de previsão</h3>
            <p class="mt-1 text-xs text-gray-500">
                Cenários de presença e prioridade de follow-up com base no RSVP atual.
            </p>
            <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-3">
                @forelse ($forecastInsights as $insight)
                    @php
                        $meta = $insight['meta'] ?? [];
                    @endphp
                    <div class="rounded-lg border px-3 py-3
                        {{ $insight['level'] === 'warning' ? 'border-amber-200 bg-amber-50' : '' }}
                        {{ $insight['level'] === 'success' ? 'border-emerald-200 bg-emerald-50' : '' }}
                        {{ $insight['level'] === 'info' ? 'border-blue-200 bg-blue-50' : '' }}">
                        <p class="text-sm font-semibold
                            {{ $insight['level'] === 'warning' ? 'text-amber-800' : '' }}
                            {{ $insight['level'] === 'success' ? 'text-emerald-800' : '' }}
                            {{ $insight['level'] === 'info' ? 'text-blue-800' : '' }}">
                            {{ $insight['title'] }}
                        </p>
                        <p class="mt-1 text-xs
                            {{ $insight['level'] === 'warning' ? 'text-amber-700' : '' }}
                            {{ $insight['level'] === 'success' ? 'text-emerald-700' : '' }}
                            {{ $insight['level'] === 'info' ? 'text-blue-700' : '' }}">
                            {{ $insight['summary'] }}
                        </p>
                        @if (isset($meta['conservative'], $meta['baseline'], $meta['optimistic']))
                            <p class="mt-2 text-xs text-gray-600">
                                Conservador {{ $meta['conservative'] }} · Base {{ $meta['baseline'] }} · Otimista {{ $meta['optimistic'] }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-500 lg:col-span-3">
                        Sem dados suficientes para gerar previsões no período selecionado.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Cobertura RSVP</h3>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Convidados</span>
                        <span class="font-medium text-gray-900">{{ $metrics['total_guests'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Confirmados</span>
                        <span class="font-medium text-emerald-700">{{ $metrics['confirmed_guests'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Talvez</span>
                        <span class="font-medium text-amber-700">{{ $metrics['maybe_guests'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Recusados</span>
                        <span class="font-medium text-rose-700">{{ $metrics['declined_guests'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Sem resposta</span>
                        <span class="font-medium text-gray-700">{{ $metrics['no_response_guests'] }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Eventos</h3>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Total de eventos RSVP</span>
                        <span class="font-medium text-gray-900">{{ $metrics['total_events'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Eventos ativos</span>
                        <span class="font-medium text-gray-900">{{ $metrics['active_events'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Usos de convite</span>
                        <span class="font-medium text-gray-900">{{ $metrics['total_invite_uses'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Taxa de revogação</span>
                        <span class="font-medium text-gray-900">{{ number_format($metrics['revoked_rate'], 2, ',', '.') }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Check-ins (período)</span>
                        <span class="font-medium text-gray-900">{{ $metrics['total_checkins'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Convidados com check-in</span>
                        <span class="font-medium text-gray-900">{{ $metrics['unique_checked_in_guests'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Status dos RSVP por evento</h3>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Confirmado</span>
                        <span class="font-medium text-emerald-700">{{ $rsvpBreakdown['confirmed'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Talvez</span>
                        <span class="font-medium text-amber-700">{{ $rsvpBreakdown['maybe'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Recusado</span>
                        <span class="font-medium text-rose-700">{{ $rsvpBreakdown['declined'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Sem resposta</span>
                        <span class="font-medium text-gray-700">{{ $rsvpBreakdown['no_response'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Desempenho por canal</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="pb-2 pr-4">Canal</th>
                                <th class="pb-2 pr-4">Convites</th>
                                <th class="pb-2 pr-4">Abertos</th>
                                <th class="pb-2 pr-4">Taxa</th>
                                <th class="pb-2">Usos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($channelBreakdown as $channel)
                                <tr>
                                    <td class="py-2 pr-4">
                                        {{ match ($channel['channel']) {
                                            'email' => 'Email',
                                            'whatsapp' => 'WhatsApp',
                                            'sms' => 'SMS',
                                            default => ucfirst($channel['channel']),
                                        } }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $channel['total'] }}</td>
                                    <td class="py-2 pr-4">{{ $channel['opened'] }}</td>
                                    <td class="py-2 pr-4">{{ number_format($channel['open_rate'], 2, ',', '.') }}%</td>
                                    <td class="py-2">{{ $channel['uses'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-3 text-sm text-gray-500">Sem dados de convites no período selecionado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Alertas operacionais</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($alerts as $alert)
                        <div class="rounded-lg border px-3 py-2
                            {{ $alert['level'] === 'warning' ? 'border-amber-200 bg-amber-50' : '' }}
                            {{ $alert['level'] === 'danger' ? 'border-rose-200 bg-rose-50' : '' }}
                            {{ $alert['level'] === 'info' ? 'border-blue-200 bg-blue-50' : '' }}">
                            <p class="text-sm font-semibold
                                {{ $alert['level'] === 'warning' ? 'text-amber-800' : '' }}
                                {{ $alert['level'] === 'danger' ? 'text-rose-800' : '' }}
                                {{ $alert['level'] === 'info' ? 'text-blue-800' : '' }}">
                                {{ $alert['title'] }}
                            </p>
                            <p class="mt-1 text-xs
                                {{ $alert['level'] === 'warning' ? 'text-amber-700' : '' }}
                                {{ $alert['level'] === 'danger' ? 'text-rose-700' : '' }}
                                {{ $alert['level'] === 'info' ? 'text-blue-700' : '' }}">
                                {{ $alert['description'] }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sem alertas no momento.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Check-in por método</h3>
                <p class="mt-1 text-xs text-gray-500">
                    {{ $metrics['checkins_today'] ?? 0 }} check-in(s) hoje ·
                    {{ $metrics['pending_checkin_guests'] ?? 0 }} convidado(s) sem check-in
                </p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="pb-2 pr-4">Método</th>
                                <th class="pb-2">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($checkinBreakdown as $method)
                                <tr>
                                    <td class="py-2 pr-4">{{ $method['label'] }}</td>
                                    <td class="py-2">{{ $method['total'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-3 text-sm text-gray-500">Sem check-ins no período selecionado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Últimos check-ins</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="pb-2 pr-4">Horário</th>
                                <th class="pb-2 pr-4">Convidado</th>
                                <th class="pb-2 pr-4">Evento</th>
                                <th class="pb-2 pr-4">Método</th>
                                <th class="pb-2">Operador</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($recentCheckins as $checkin)
                                <tr>
                                    <td class="py-2 pr-4">{{ $checkin['checked_in_at']?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="py-2 pr-4">{{ $checkin['guest'] }}</td>
                                    <td class="py-2 pr-4">{{ $checkin['event'] }}</td>
                                    <td class="py-2 pr-4">{{ $checkin['method_label'] }}</td>
                                    <td class="py-2">{{ $checkin['operator'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-3 text-sm text-gray-500">Nenhum check-in registrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">Painel de incidentes de envio</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $incidents['failed_messages_total'] ?? 0 }} falha(s) de envio ·
                            {{ $incidents['failed_invites_total'] ?? 0 }} convite(s) impactado(s)
                        </p>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="pb-2 pr-4">Canal</th>
                                <th class="pb-2 pr-4">Falhas</th>
                                <th class="pb-2 pr-4">Convites</th>
                                <th class="pb-2 pr-4">Última falha</th>
                                <th class="pb-2">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($failedChannels as $channel)
                                <tr>
                                    <td class="py-2 pr-4">
                                        {{ match ($channel['channel']) {
                                            'email' => 'Email',
                                            'whatsapp' => 'WhatsApp',
                                            'sms' => 'SMS',
                                            default => ucfirst($channel['channel']),
                                        } }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $channel['failed_messages'] }}</td>
                                    <td class="py-2 pr-4">{{ $channel['impacted_invites'] }}</td>
                                    <td class="py-2 pr-4">{{ $channel['last_failure_at']?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="py-2">
                                        <x-filament::button
                                            size="xs"
                                            color="warning"
                                            wire:click="retryChannelFailures('{{ $channel['channel'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="retryChannelFailures"
                                        >
                                            Reenviar canal
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-3 text-sm text-gray-500">Sem incidentes de envio no período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Fila de falhas</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="pb-2 pr-4">Falha em</th>
                                <th class="pb-2 pr-4">Convidado</th>
                                <th class="pb-2 pr-4">Canal</th>
                                <th class="pb-2 pr-4">Tentativas</th>
                                <th class="pb-2 pr-4">Erro</th>
                                <th class="pb-2">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($failedQueue as $incident)
                                <tr>
                                    <td class="py-2 pr-4">{{ $incident['failed_at']?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="py-2 pr-4">
                                        <div class="font-medium text-gray-900">{{ $incident['guest'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $incident['household'] }}</div>
                                    </td>
                                    <td class="py-2 pr-4">
                                        {{ match ($incident['channel']) {
                                            'email' => 'Email',
                                            'whatsapp' => 'WhatsApp',
                                            'sms' => 'SMS',
                                            default => ucfirst($incident['channel']),
                                        } }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $incident['failed_attempts'] }}</td>
                                    <td class="py-2 pr-4">{{ \Illuminate\Support\Str::limit($incident['error'], 45) }}</td>
                                    <td class="py-2">
                                        @if ($incident['can_retry'])
                                            <x-filament::button
                                                size="xs"
                                                color="warning"
                                                wire:click="retryFailedInvite('{{ $incident['invite_id'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="retryFailedInvite"
                                            >
                                                Reenviar
                                            </x-filament::button>
                                        @else
                                            <span class="text-xs text-gray-500">{{ $incident['retry_block_reason'] ?? 'Indisponível' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-3 text-sm text-gray-500">Nenhuma falha pendente para reenvio.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700">Últimos convites</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="pb-2 pr-4">Gerado em</th>
                            <th class="pb-2 pr-4">Núcleo</th>
                            <th class="pb-2 pr-4">Convidado</th>
                            <th class="pb-2 pr-4">Canal</th>
                            <th class="pb-2 pr-4">Status</th>
                            <th class="pb-2 pr-4">Usos</th>
                            <th class="pb-2">Expira em</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($recentInvites as $invite)
                            <tr>
                                <td class="py-2 pr-4">{{ $invite['created_at']?->format('d/m/Y H:i') }}</td>
                                <td class="py-2 pr-4">{{ $invite['household'] }}</td>
                                <td class="py-2 pr-4">{{ $invite['guest'] ?: 'Núcleo' }}</td>
                                <td class="py-2 pr-4">
                                    {{ match ($invite['channel']) {
                                        'email' => 'Email',
                                        'whatsapp' => 'WhatsApp',
                                        'sms' => 'SMS',
                                        default => ucfirst($invite['channel']),
                                    } }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ match ($invite['status']) {
                                        'sent' => 'Enviado',
                                        'delivered' => 'Entregue',
                                        'opened' => 'Aberto',
                                        'expired' => 'Expirado',
                                        'revoked' => 'Revogado',
                                        default => $invite['status'],
                                    } }}
                                </td>
                                <td class="py-2 pr-4">
                                    {{ $invite['uses_count'] }}/{{ $invite['max_uses'] ?? 'ilimitado' }}
                                </td>
                                <td class="py-2">{{ $invite['expires_at']?->format('d/m/Y H:i') ?? 'Sem expiração' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-3 text-sm text-gray-500">Ainda não há convites registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament::page>
