<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Salvar Permissões
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}"
                color="gray"
            >
                Voltar
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
