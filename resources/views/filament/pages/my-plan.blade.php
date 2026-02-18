<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Salvar Assinatura
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <x-filament-actions::modals />
</x-filament-panels::page>
