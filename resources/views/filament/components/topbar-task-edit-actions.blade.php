@php
    $routeRecord = request()->route('record');
    $task = $routeRecord instanceof \App\Models\Task
        ? $routeRecord
        : \App\Models\Task::withoutGlobalScopes()->find($routeRecord);

    $canDeleteTask = $task && \App\Filament\Resources\TaskResource::canDelete($task);
@endphp

<div class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8" x-data>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-filament::button
            type="button"
            color="gray"
            size="sm"
            x-on:click="window.Livewire.dispatch('topbar-task-return')"
        >
            Cancelar
        </x-filament::button>

        @if ($canDeleteTask)
            <x-filament::button
                type="button"
                color="danger"
                icon="heroicon-o-trash"
                size="sm"
                x-on:click="window.Livewire.dispatch('topbar-task-delete')"
            >
                Excluir
            </x-filament::button>
        @endif

        <x-filament::button
            type="button"
            color="danger"
            icon="heroicon-o-check"
            size="sm"
            x-on:click="window.Livewire.dispatch('topbar-task-save')"
        >
            Salvar Alterações
        </x-filament::button>
    </div>
</div>
