<x-filament-panels::page>
    <div class="max-w-xl mx-auto">
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">💒</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Bem-vindo ao Wedding SaaS!
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                Para começar, crie seu primeiro casamento. Você poderá adicionar organizadores e convidados depois.
            </p>
        </div>

        <x-filament-panels::form wire:submit="create">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full mt-4">
                Criar Casamento
            </x-filament::button>
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
