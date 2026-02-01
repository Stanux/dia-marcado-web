<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">💍</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Bem-vindo ao Dia Marcado!
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                Vamos configurar seu casamento em apenas 3 passos simples.
            </p>
        </div>

        <x-filament-panels::form wire:submit="complete">
            {{ $this->form }}
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
