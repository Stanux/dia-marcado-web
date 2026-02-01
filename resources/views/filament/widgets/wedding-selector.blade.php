<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Casamento Atual
        </x-slot>

        <x-slot name="description">
            Selecione o casamento que deseja gerenciar
        </x-slot>

        @php
            $weddings = $this->getWeddings();
            $currentWedding = $this->getCurrentWedding();
        @endphp

        @if(count($weddings) > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($weddings as $id => $title)
                    <button
                        wire:click="selectWedding('{{ $id }}')"
                        @class([
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                            'bg-primary-500 text-white' => $selectedWeddingId === $id,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $selectedWeddingId !== $id,
                        ])
                    >
                        {{ $title }}
                    </button>
                @endforeach
            </div>

            @if($currentWedding)
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Gerenciando:</span> {{ $currentWedding->title }}
                    </p>
                    @if($currentWedding->wedding_date)
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                            <span class="font-medium">Data:</span> {{ $currentWedding->wedding_date->format('d/m/Y') }}
                        </p>
                    @endif
                </div>
            @endif
        @else
            <div class="text-center py-4">
                <p class="text-gray-500 dark:text-gray-400">
                    Você não tem acesso a nenhum casamento.
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
