<x-filament::page>
    @php($plans = $this->getPlans())
    @php($tasks = $this->getTimelineTasks())
    @php($canCreatePlan = \App\Filament\Resources\WeddingPlanResource::canCreate())

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

        <div class="relative">
            <div class="absolute left-4 top-0 h-full w-px bg-gray-200"></div>

            <div class="space-y-6">
                @forelse ($tasks as $task)
                    <div class="relative flex gap-4">
                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">
                            {{ $task->start_date?->format('d/m') ?? '--' }}
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
                            @endphp
                            @php
                                $priorityLabel = match ($task->priority) {
                                    'low' => 'Baixa',
                                    'high' => 'Alta',
                                    default => 'Média',
                                };
                            @endphp
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-500">
                                <span>Status: {{ $statusLabel }}</span>
                                <span>Prioridade: {{ $priorityLabel }}</span>
                                @if ($task->category)
                                    <span>Categoria: {{ $task->category->name }}</span>
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
