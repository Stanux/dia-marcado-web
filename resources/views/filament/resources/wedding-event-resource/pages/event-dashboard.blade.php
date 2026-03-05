<x-filament-panels::page>
    @php
        $event = $this->getRecord();
        $totals = $this->totals;
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $event->name }}</h2>
                    <p class="text-sm text-gray-500">
                        {{ $event->event_date?->format('d/m/Y') ?? 'Sem data' }}
                        @if ($event->event_time)
                            · {{ substr((string) $event->event_time, 0, 5) }}
                        @endif
                    </p>
                </div>

                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Resources\WeddingEventResource::getUrl('index') }}"
                    color="gray"
                    icon="heroicon-o-arrow-left"
                >
                    Voltar para eventos
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Total no Evento</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $totals['total'] }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Confirmados</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ $totals['confirmed'] }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Pendentes</p>
                <p class="mt-2 text-2xl font-semibold text-amber-700">{{ $totals['pending'] }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Recusados</p>
                <p class="mt-2 text-2xl font-semibold text-rose-700">{{ $totals['declined'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Por Status</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($this->statusBreakdown as $item)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">{{ $item['label'] }}</span>
                            <span class="font-medium text-gray-900">
                                {{ $item['count'] }} ({{ number_format($item['percentage'], 1, ',', '.') }}%)
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Por Lado</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($this->sideBreakdown as $item)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">{{ $item['label'] }}</span>
                            <span class="font-medium text-gray-900">
                                {{ $item['count'] }} ({{ number_format($item['percentage'], 1, ',', '.') }}%)
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Adulto / Criança</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($this->ageBreakdown as $item)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">{{ $item['label'] }}</span>
                            <span class="font-medium text-gray-900">
                                {{ $item['count'] }} ({{ number_format($item['percentage'], 1, ',', '.') }}%)
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

