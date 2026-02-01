<div class="fi-simple-layout flex min-h-screen flex-col p-4">
    {{-- Logout Button --}}
    <div class="w-full flex justify-end mb-4">
        <form action="{{ filament()->getLogoutUrl() }}" method="post">
            @csrf
            <button type="submit" class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" />
                <span>Sair</span>
            </button>
        </form>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center">
        <div class="w-full" style="max-width: 80%;">
            {{-- Welcome Header --}}
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">💍</div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Bem-vindo ao Dia Marcado!
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    Vamos configurar seu casamento em apenas alguns passos simples.
                </p>
            </div>

            {{-- Wizard Form --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl p-6 sm:p-8">
                <form wire:submit="complete">
                    {{ $this->form }}
                </form>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-6 text-sm text-gray-500 dark:text-gray-400">
                <p>Precisa de ajuda? Entre em contato conosco.</p>
            </div>
        </div>
    </div>
</div>
