<x-filament::page>
    <div class="space-y-6">
        <x-filament::section heading="Modelo de importação">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-gray-600">
                    Baixe o modelo em Excel para gerar o arquivo com os campos esperados.
                </div>
                <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadTemplate">
                    Baixar modelo (.xlsx)
                </x-filament::button>
            </div>
        </x-filament::section>

        {{ $this->form }}

        @if (count($this->preview))
            <x-filament::section heading="Pré-visualização">
                <div class="overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left">
                                @foreach ($this->headers as $header)
                                    <th class="px-3 py-2 text-gray-600">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->preview as $row)
                                <tr class="border-t">
                                    @foreach ($this->headers as $index => $header)
                                        <td class="px-3 py-2 text-gray-800">{{ $row[$index] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if (!empty($this->stats))
            <x-filament::section heading="Resumo da análise">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-sm text-gray-500">Total</div>
                        <div class="text-xl font-semibold">{{ $this->stats['total'] }}</div>
                    </div>
                    <div class="rounded-lg bg-green-50 p-4">
                        <div class="text-sm text-green-700">Válidos</div>
                        <div class="text-xl font-semibold">{{ $this->stats['valid'] }}</div>
                    </div>
                    <div class="rounded-lg bg-yellow-50 p-4">
                        <div class="text-sm text-yellow-700">Duplicados</div>
                        <div class="text-xl font-semibold">{{ $this->stats['duplicates'] }}</div>
                    </div>
                    <div class="rounded-lg bg-red-50 p-4">
                        <div class="text-sm text-red-700">Inválidos</div>
                        <div class="text-xl font-semibold">{{ $this->stats['invalid'] }}</div>
                    </div>
                </div>

                @if (!empty($this->dedupe))
                    <div class="mt-4">
                        <div class="font-semibold text-sm text-gray-700">Problemas detectados</div>
                        <ul class="mt-2 list-disc pl-5 text-sm text-gray-600">
                            @foreach ($this->dedupe as $issue)
                                <li>Linha {{ $issue['row'] }}: {{ $issue['issue'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-filament::section>
        @endif

        <div class="flex items-center gap-3">
            <x-filament::button color="gray" wire:click="analyze">
                Analisar
            </x-filament::button>
            <x-filament::button color="primary" wire:click="import">
                Importar
            </x-filament::button>
        </div>
    </div>
</x-filament::page>
