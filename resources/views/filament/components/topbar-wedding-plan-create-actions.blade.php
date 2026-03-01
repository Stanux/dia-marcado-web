<div class="border-t border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900 md:hidden md:px-6 lg:px-8" x-data>
    <div class="flex items-center justify-end gap-2">
        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.resources.wedding-plans.index') }}"
            color="gray"
            size="sm"
        >
            Cancelar
        </x-filament::button>

        <x-filament::button
            type="button"
            color="gray"
            icon="heroicon-o-document-duplicate"
            size="sm"
            x-on:click="window.Livewire.dispatch('topbar-wedding-plan-create-another')"
        >
            Salvar e criar outro
        </x-filament::button>

        <x-filament::button
            type="submit"
            form="form"
            color="danger"
            icon="heroicon-o-check"
            size="sm"
        >
            Criar Planejamento
        </x-filament::button>
    </div>
</div>
