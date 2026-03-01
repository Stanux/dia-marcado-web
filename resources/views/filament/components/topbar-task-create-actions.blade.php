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

        <x-filament::button
            type="button"
            color="gray"
            icon="heroicon-o-document-duplicate"
            size="sm"
            x-on:click="window.Livewire.dispatch('topbar-task-create-another')"
        >
            Salvar e criar outro
        </x-filament::button>

        <x-filament::button
            type="button"
            color="danger"
            icon="heroicon-o-check"
            size="sm"
            x-on:click="window.Livewire.dispatch('topbar-task-submit')"
        >
            Salvar Tarefa
        </x-filament::button>
    </div>
</div>
