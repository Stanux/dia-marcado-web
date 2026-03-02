@php
    $routeRecord = request()->route('record');
    $event = $routeRecord instanceof \App\Models\WeddingEvent
        ? $routeRecord
        : \App\Models\WeddingEvent::withoutGlobalScopes()->find($routeRecord);

    $canDeleteWeddingEvent = $event && \App\Filament\Resources\WeddingEventResource::canDelete($event);
@endphp

<div class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8" x-data>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.resources.events.index') }}"
            color="gray"
            size="sm"
        >
            Cancelar
        </x-filament::button>

        @if ($canDeleteWeddingEvent)
            <x-filament::button
                type="button"
                color="danger"
                icon="heroicon-o-trash"
                size="sm"
                x-on:click="window.Livewire.dispatch('topbar-wedding-event-delete')"
            >
                Excluir
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
