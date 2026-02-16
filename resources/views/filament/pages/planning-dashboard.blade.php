<x-filament::page>
    <style>
        .planning-top-row {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 1024px) {
            .planning-top-row {
                grid-template-columns: 2fr 1fr 1fr;
            }
        }
    </style>
    @php
        $plans = $this->getPlans();
        $summary = $this->getSummary();
        $canCreatePlan = \App\Filament\Resources\WeddingPlanResource::canCreate();
        $planBudget = (float) ($summary['plan_budget'] ?? 0);
        $estimated = (float) ($summary['estimated'] ?? 0);
        $impactTotal = (float) ($summary['impact_total'] ?? 0);
        $percentUsed = $planBudget > 0 ? round(($estimated / $planBudget) * 100, 2) : 0;
        $barWidth = min(100, $percentUsed);
        $health = $percentUsed <= 90 ? 'healthy' : ($percentUsed <= 100 ? 'warning' : 'danger');
        $healthLabel = $health === 'healthy' ? 'Saudável' : ($health === 'warning' ? 'Atenção' : 'Estourado');
        $healthBadgeClass = match ($health) {
            'healthy' => 'bg-emerald-50 text-emerald-700',
            'warning' => 'bg-amber-50 text-amber-700',
            default => 'bg-rose-50 text-rose-700',
        };
        $healthBarClass = match ($health) {
            'healthy' => 'bg-emerald-500',
            'warning' => 'bg-amber-500',
            default => 'bg-rose-500',
        };
        $healthStroke = match ($health) {
            'healthy' => '#10b981',
            'warning' => '#f59e0b',
            default => '#f43f5e',
        };
        $gaugeMax = 150;
        $gaugePercent = $planBudget > 0 ? min($gaugeMax, $percentUsed) : 0;
        $needleAngle = -90 + ($gaugePercent / $gaugeMax) * 180;
        $arcLength = 283;
        $arcFill = $planBudget > 0 ? min(100, $percentUsed) / 100 * $arcLength : 0;
    @endphp

    <div class="space-y-3">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Resumo do Planejamento</h2>
                <p class="text-sm text-gray-500">Acompanhe progresso, orçamento e tarefas críticas.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label class="text-sm font-medium text-gray-700">Planejamento</label>
                    @if ($plans->isNotEmpty())
                        <select wire:model="planId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->title }}</option>
                            @endforeach
                        </select>
                    @else
                        <p class="mt-2 text-sm text-gray-500">Nenhum planejamento criado ainda.</p>
                    @endif
                </div>

                @if ($canCreatePlan)
                    <x-filament::button
                        tag="a"
                        icon="heroicon-o-plus"
                        href="{{ \App\Filament\Resources\WeddingPlanResource::getUrl('create') }}"
                    >
                        Novo Planejamento
                    </x-filament::button>
                @endif
            </div>
        </div>

        <div class="planning-top-row">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Saúde do Orçamento</p>
                        <h3 class="mt-2 text-2xl font-semibold text-gray-900">Orçamento Total</h3>
                        <p class="mt-1 text-sm text-gray-500">Resumo rápido para decisão em 3 segundos</p>
                    </div>

                    <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $healthBadgeClass }}">
                        {{ $healthLabel }}
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-xs text-gray-500">Definido</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">R$ {{ number_format($planBudget, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Estimado</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">R$ {{ number_format($estimated, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Diferença</p>
                        <p class="mt-1 text-lg font-semibold {{ $summary['estimated_vs_plan'] <= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $summary['estimated_vs_plan'] <= 0 ? '-' : '+' }}R$ {{ number_format(abs($summary['estimated_vs_plan']), 2, ',', '.') }}
                            @if ($planBudget > 0)
                                <span class="text-xs text-gray-500">({{ number_format(abs($percentUsed - 100), 2, ',', '.') }}%)</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>0%</span>
                        <span>100%</span>
                    </div>
                    <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full {{ $healthBarClass }}"
                            style="width: {{ $barWidth }}%;"
                        ></div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        @if ($planBudget <= 0)
                            Defina um orçamento do plano para calcular o percentual usado.
                        @elseif ($percentUsed <= 100)
                            Você utilizou {{ number_format($percentUsed, 2, ',', '.') }}% do orçamento.
                        @else
                            ⚠️ Seu planejamento está {{ number_format($percentUsed - 100, 2, ',', '.') }}% acima do orçamento.
                        @endif
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Gauge do Orçamento</h3>
                    <span class="text-xs text-gray-500">Estimado</span>
                </div>
                <div class="mt-6 flex items-center justify-center">
                    <div class="relative h-32 w-56">
                        <svg viewBox="0 0 200 110" class="h-full w-full">
                            <path d="M10 100 A 90 90 0 0 1 190 100" stroke="#e5e7eb" stroke-width="12" fill="none" />
                            <path
                                d="M10 100 A 90 90 0 0 1 190 100"
                                stroke="{{ $healthStroke }}"
                                stroke-width="12"
                                stroke-linecap="round"
                                fill="none"
                                style="stroke-dasharray: {{ $arcFill }} {{ $arcLength }};"
                            />
                        </svg>
                        <div class="absolute left-1/2 top-[52%] h-1 w-24 -translate-x-1/2 origin-left rounded-full bg-gray-800"
                             style="transform: translateX(-50%) rotate({{ $needleAngle }}deg);"></div>
                        <div class="absolute left-1/2 top-[52%] h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gray-800"></div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($percentUsed, 2, ',', '.') }}%</p>
                    <p class="text-xs text-gray-500">do orçamento definido</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Progresso</h3>
                </div>
                <div class="mt-6 text-center">
                    <p class="text-3xl font-semibold text-gray-900">{{ $summary['progress'] }}%</p>
                    <p class="mt-2 text-xs text-gray-500">{{ $summary['completed'] }} de {{ $summary['total'] }} tarefas concluídas</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                 x-data="{
                    open: false,
                    planBudget: {{ $planBudget }},
                    categories: @js($summary['category_simulator'] ?? []),
                    init() {
                        this.categories = this.categories.map(cat => ({
                            ...cat,
                            min: 0,
                            max: cat.estimated,
                            value: cat.estimated,
                            totalBase: cat.actual + cat.estimated,
                        }));
                    },
                    get simulatedEstimated() {
                        return this.categories.reduce((sum, cat) => sum + Number(cat.actual || 0) + Number(cat.value || 0), 0);
                    },
                    get diffVsPlan() {
                        return this.simulatedEstimated - this.planBudget;
                    },
                    get percentUsed() {
                        return this.planBudget > 0 ? (this.simulatedEstimated / this.planBudget) * 100 : 0;
                    },
                    getImpactPercent(category) {
                        const total = Number(category.actual || 0) + Number(category.value || 0);
                        if (this.planBudget > 0) {
                            return (total / this.planBudget) * 100;
                        }
                        return this.simulatedEstimated > 0 ? (total / this.simulatedEstimated) * 100 : 0;
                    },
                    formatCurrency(value) {
                        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
                    },
                    formatPercent(value) {
                        return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0);
                    }
                 }"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Simulador de Orçamento</h3>
                    <x-filament::button size="sm" color="gray" x-on:click="open = !open">
                        <span x-show="!open">Simular</span>
                        <span x-show="open" x-cloak>Fechar</span>
                    </x-filament::button>
                </div>

                <div class="mt-3 space-y-4 border-t border-gray-200 pt-4" x-show="open" x-transition>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
                        <p class="text-xs text-gray-500">Estimado simulado</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900" x-text="formatCurrency(simulatedEstimated)"></p>
                        <p class="mt-1 text-xs" :class="diffVsPlan <= 0 ? 'text-emerald-700' : 'text-rose-700'">
                            <span x-text="diffVsPlan <= 0 ? 'Saldo vs Plano' : 'Estouro vs Plano'"></span>:
                            <span x-text="formatCurrency(Math.abs(diffVsPlan))"></span>
                            <span x-show="planBudget > 0" class="text-gray-500">(<span x-text="formatPercent(Math.abs(percentUsed - 100))"></span>%)</span>
                        </p>
                    </div>

                    <div class="space-y-4">
                        <template x-if="categories.length === 0">
                            <p class="text-sm text-gray-500">Sem categorias para simular.</p>
                        </template>
                        <template x-for="category in categories" :key="category.name">
                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700" x-text="category.name"></span>
                                    <span class="text-gray-500" x-text="formatCurrency(Number(category.actual || 0) + Number(category.value || 0))"></span>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    Realizado: <span class="font-medium" x-text="formatCurrency(category.actual)"></span>
                                    · Estimado ajustável: <span class="font-medium" x-text="formatCurrency(category.value)"></span>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    Impacto: <span class="font-medium" x-text="formatPercent(getImpactPercent(category))"></span>% do orçamento
                                </div>
                                <div class="mt-3 flex h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-2 bg-gray-300"
                                         :style="`width: ${category.totalBase > 0 ? (category.actual / category.totalBase) * 100 : 0}%`"></div>
                                    <div class="h-2 bg-emerald-500"
                                         :style="`width: ${category.totalBase > 0 ? (category.value / category.totalBase) * 100 : 0}%`"></div>
                                </div>
                                <div class="mt-3">
                                    <input
                                        type="range"
                                        class="w-full"
                                        :min="category.min"
                                        :max="category.max"
                                        step="1"
                                        x-model.number="category.value"
                                        :disabled="category.estimated <= 0"
                                        style="accent-color: #10b981;"
                                    />
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    Limite mínimo: <span class="font-medium">R$ 0,00</span>
                                    · Limite máximo (estimado): <span class="font-medium" x-text="formatCurrency(category.max)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-gray-500">
                        Os itens com valores realizados não são alteráveis. O slider ajusta somente a parte estimada.
                    </p>
                </div>
            </div>

        <div class="space-y-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Tarefas Atrasadas</h3>
                <div class="mt-3 space-y-2">
                    @forelse ($summary['overdue'] as $task)
                        <div class="flex items-center justify-between rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">
                            <span>{{ $task->title }}</span>
                            <span>{{ $task->due_date?->format('d/m/Y') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Nenhuma tarefa atrasada.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Próximas Tarefas (7 dias)</h3>
                <div class="mt-3 space-y-2">
                    @forelse ($summary['upcoming'] as $task)
                        <div class="flex items-center justify-between rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-700">
                            <span>{{ $task->title }}</span>
                            <span>{{ $task->due_date?->format('d/m/Y') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Nenhuma tarefa próxima.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
