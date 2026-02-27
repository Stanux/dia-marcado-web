<x-filament-widgets::widget>
    @php
        $pendingItems = $this->getPendingItems();
        $showNudge = $pendingItems !== [];
        $settingsUrl = $this->getWeddingSettingsUrl();
        $previewUrl = $this->getPreviewEditorUrl();
        $usersCreateUrl = $this->getUsersCreateUrl();
    @endphp

    @if($showNudge)
        <x-filament::section>
            <x-slot name="heading">
                Pendências para Publicação
            </x-slot>

            <x-slot name="description">
                Complete os dados do evento para liberar recursos dinâmicos e publicação.
            </x-slot>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/60 dark:bg-amber-900/20">
                <ul class="space-y-2 text-sm text-amber-900 dark:text-amber-200">
                    @foreach($pendingItems as $item)
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-200 text-[10px] font-bold text-amber-900 dark:bg-amber-700 dark:text-amber-100">!</span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <a
                        href="{{ $settingsUrl }}"
                        class="inline-flex items-center rounded-md bg-amber-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-500"
                    >
                        Ir para Dados do Evento
                    </a>

                    @if($previewUrl)
                        <a
                            href="{{ $previewUrl }}"
                            class="inline-flex items-center rounded-md border border-amber-400 px-3 py-1.5 text-sm font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-500 dark:text-amber-200 dark:hover:bg-amber-900/30"
                        >
                            Ver Preview no Editor
                        </a>
                    @endif

                    <a
                        href="{{ $usersCreateUrl }}"
                        class="inline-flex items-center rounded-md border border-amber-400 px-3 py-1.5 text-sm font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-500 dark:text-amber-200 dark:hover:bg-amber-900/30"
                    >
                        Ir para Usuários
                    </a>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
