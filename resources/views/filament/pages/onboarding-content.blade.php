<div class="fi-simple-layout flex min-h-screen flex-col px-2 py-1.5 sm:px-4 sm:py-2 lg:px-6">
    <div class="flex-1 flex flex-col items-center justify-start">
        <div class="w-full max-w-screen-2xl">
            {{-- Welcome Header --}}
            <div class="relative text-center mb-2 sm:mb-3 lg:mb-4">
                <form action="{{ filament()->getLogoutUrl() }}" method="post" class="mb-2 flex justify-end sm:mb-0 sm:absolute sm:right-0 sm:top-0">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" />
                        <span>Sair</span>
                    </button>
                </form>
                <h1 class="text-xl leading-tight font-bold text-gray-900 dark:text-white mb-1 sm:text-2xl md:text-3xl">
                    Bem-vindo ao Dia Marcado!
                </h1>
                <!-- <p class="text-base md:text-lg text-gray-600 dark:text-gray-400">
                    Vamos configurar seu casamento em apenas alguns passos simples.
                </p> -->
            </div>

            {{-- Wizard Form --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl p-0 sm:p-5 lg:p-6">
                <form wire:submit="complete">
                    {{ $this->form }}
                </form>
            </div>

            {{-- Footer --}}
            <!-- <div class="text-center mt-3 lg:mt-4 text-sm text-gray-500 dark:text-gray-400">
                <p>Precisa de ajuda? Entre em contato conosco.</p>
            </div> -->
        </div>
    </div>
</div>
