<div class="fi-resource-relation-manager flex flex-col gap-y-6">
    <x-filament-panels::resources.tabs />

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-filament::button
            type="button"
            size="sm"
            color="gray"
            :icon="$this->isTimelineView ? 'heroicon-o-table-cells' : 'heroicon-o-calendar-days'"
            wire:click="{{ $this->isTimelineView ? 'showTableView' : 'showTimelineView' }}"
        >
            {{ $this->isTimelineView ? 'Ver tabela' : 'Ver timeline' }}
        </x-filament::button>

        @if ($this->canCreate())
            <x-filament::button
                tag="a"
                size="sm"
                icon="heroicon-o-plus"
                href="{{ \App\Filament\Resources\TaskResource::getUrl('create', [
                    'plan' => $this->getOwnerRecord()->getKey(),
                    'return_to_plan' => $this->getOwnerRecord()->getKey(),
                ]) }}"
            >
                Criar tarefa
            </x-filament::button>
        @endif
    </div>

    @if ($this->isTimelineView)
        @php
            $tasks = $this->getTimelineTasks();
        @endphp

        <div class="relative pl-6">
            <div class="absolute left-12 top-0 h-full w-px bg-gray-200"></div>

            <div class="space-y-6">
                @forelse ($tasks as $task)
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

                    <div class="relative flex items-center gap-4">
                        <div
                            class="relative z-10 flex shrink-0 items-center justify-center rounded-full border-2 border-primary-700 bg-primary-600 text-white shadow-sm ring-2 ring-white"
                            style="width: 3.4rem; height: 3.4rem; min-width: 3.4rem; min-height: 3.4rem;"
                        >
                            <span style="white-space: nowrap; padding: 0 0.3rem; letter-spacing: 0; font-size: 7px; font-weight: 600; line-height: 1;">
                                {{ $task->start_date?->format('d/m') ?? '--' }}
                            </span>
                        </div>

                        <div class="flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $task->title }}</h3>
                                <span class="text-xs text-gray-500">
                                    {{ $task->start_date?->format('d/m/Y') ?? '—' }} -> {{ $task->due_date?->format('d/m/Y') ?? '—' }}
                                </span>
                            </div>

                            <p class="mt-2 text-sm text-gray-600">{{ $task->description ?: 'Sem descrição.' }}</p>

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

                            @if ($this->canEdit($task))
                                <div class="mt-3">
                                    <x-filament::button
                                        tag="a"
                                        size="xs"
                                        color="gray"
                                        icon="heroicon-o-pencil-square"
                                        href="{{ \App\Filament\Resources\TaskResource::getUrl('edit', [
                                            'record' => $task,
                                            'return_to_plan' => $this->getOwnerRecord()->getKey(),
                                        ]) }}"
                                    >
                                        Editar tarefa
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Nenhuma tarefa encontrada para este planejamento.</p>
                @endforelse
            </div>
        </div>
    @else
        {{ $this->table }}
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::unsaved-action-changes-alert />
</div>
