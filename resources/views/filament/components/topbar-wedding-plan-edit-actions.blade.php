@php
    $routeRecord = request()->route('record');
    $plan = $routeRecord instanceof \App\Models\WeddingPlan
        ? $routeRecord
        : \App\Models\WeddingPlan::withoutGlobalScopes()->find($routeRecord);

    $isAdmin = auth()->user()?->isAdmin() ?? false;

    $createTaskUrl = $plan
        ? \App\Filament\Resources\TaskResource::getUrl('create', [
            'plan' => $plan->getKey(),
            'return_to_plan' => $plan->getKey(),
        ])
        : null;

    $canCreateTask = $plan
        && \App\Filament\Resources\TaskResource::canCreate()
        && (! $plan->isArchived() || $isAdmin);
@endphp

<div
    class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8"
    x-data="{ isTimelineView: false }"
    x-on:topbar-wedding-plan-show-timeline.window="isTimelineView = true"
    x-on:topbar-wedding-plan-show-table.window="isTimelineView = false"
>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.resources.wedding-plans.index') }}"
            color="gray"
            size="sm"
        >
            Cancelar
        </x-filament::button>

        <x-filament::button
            x-cloak
            x-show="!isTimelineView"
            type="button"
            color="gray"
            icon="heroicon-o-calendar-days"
            size="sm"
            x-on:click="window.Livewire.dispatch('topbar-wedding-plan-show-timeline')"
        >
            Timeline
        </x-filament::button>

        <x-filament::button
            x-cloak
            x-show="isTimelineView"
            type="button"
            color="gray"
            icon="heroicon-o-table-cells"
            size="sm"
            x-on:click="window.Livewire.dispatch('topbar-wedding-plan-show-table')"
        >
            Tabela
        </x-filament::button>

        @if ($canCreateTask && $createTaskUrl)
            <x-filament::button
                tag="a"
                href="{{ $createTaskUrl }}"
                color="gray"
                icon="heroicon-o-plus"
                size="sm"
            >
                Criar Tarefa
            </x-filament::button>
        @endif

        <x-filament::button
            type="submit"
            form-id="form"
            color="danger"
            icon="heroicon-o-check"
            size="sm"
        >
            Salvar Alterações
        </x-filament::button>
    </div>
</div>
