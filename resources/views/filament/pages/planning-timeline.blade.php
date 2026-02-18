<x-filament::page>
    @php
        $plans = $this->getPlans();
        $tasks = $this->getTimelineTasks();
        $canCreatePlan = \App\Filament\Resources\WeddingPlanResource::canCreate();
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Timeline do Planejamento</h2>
                <p class="text-sm text-gray-500">Visualize tarefas ordenadas por data de início.</p>
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

        <div class="relative pl-6">
            <div class="absolute left-12 top-0 h-full w-px bg-gray-200"></div>

            <div class="space-y-6">
                @forelse ($tasks as $task)
                    <div class="relative flex items-center gap-4">
                        <div
                            class="relative z-10 flex shrink-0 items-center justify-center rounded-full border-2 border-primary-700 bg-primary-600 text-white shadow-sm ring-2 ring-white"
                            style="width: 3.4rem; height: 3.4rem; min-width: 3.4rem; min-height: 3.4rem;"
                        >
                            <span style="white-space: nowrap; padding: 0 0.3rem; letter-spacing: 0; font-size: 7px; font-weight: 600; line-height: 1;">{{ $task->start_date?->format('d/m') ?? '--' }}</span>
                        </div>
                        <div class="flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $task->title }}</h3>
                                <span class="text-xs text-gray-500">{{ $task->start_date?->format('d/m/Y') }} → {{ $task->due_date?->format('d/m/Y') }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">{{ $task->description }}</p>
                            @php
                                $statusLabel = match ($task->effective_status) {
                                    'overdue' => 'Atrasada',
                                    'pending' => 'Pendente',
                                    'in_progress' => 'Em Andamento',
                                    'completed' => 'Concluída',
                                    'cancelled' => 'Cancelada',
                                    default => $task->effective_status,
                                };

                                $statusColor = match ($task->effective_status) {
                                    'overdue' => '#dc2626',
                                    'pending' => '#d97706',
                                    'in_progress' => '#0284c7',
                                    'completed' => '#059669',
                                    'cancelled' => '#475569',
                                    default => '#64748b',
                                };

                                $statusBg = match ($task->effective_status) {
                                    'overdue' => '#fef2f2',
                                    'pending' => '#fffbeb',
                                    'in_progress' => '#f0f9ff',
                                    'completed' => '#ecfdf5',
                                    'cancelled' => '#f8fafc',
                                    default => '#f8fafc',
                                };
                            @endphp
                            @php
                                $priorityLabel = match ($task->priority) {
                                    'low' => 'Baixa',
                                    'high' => 'Alta',
                                    default => 'Média',
                                };

                                $priorityColor = match ($task->priority) {
                                    'low' => '#059669',
                                    'high' => '#dc2626',
                                    default => '#d97706',
                                };

                                $priorityBg = match ($task->priority) {
                                    'low' => '#ecfdf5',
                                    'high' => '#fef2f2',
                                    default => '#fffbeb',
                                };
                            @endphp
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 font-semibold"
                                    style="color: {{ $statusColor }}; border-color: {{ $statusColor }}; background-color: {{ $statusBg }};"
                                >
                                    Status: {{ $statusLabel }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 font-semibold"
                                    style="color: {{ $priorityColor }}; border-color: {{ $priorityColor }}; background-color: {{ $priorityBg }};"
                                >
                                    Prioridade: {{ $priorityLabel }}
                                </span>

                                @if ($task->category)
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 font-semibold"
                                        style="color: #7c3aed; border-color: #7c3aed; background-color: #f5f3ff;"
                                    >
                                        Categoria: {{ $task->category->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Nenhuma tarefa encontrada para este planejamento.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-filament::page>
