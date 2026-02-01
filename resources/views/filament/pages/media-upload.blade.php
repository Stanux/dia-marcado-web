<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Album Selection --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white mb-4">
                    Selecione o Álbum
                </h3>
                <select 
                    wire:model="albumId" 
                    class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-primary-500"
                >
                    <option value="">Selecione um álbum (opcional)</option>
                    @foreach($this->getAlbumOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    As mídias serão associadas a este álbum
                </p>
            </div>
        </div>

        {{-- File Upload Area --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white mb-4">
                    Upload de Arquivos
                </h3>

                {{-- Drag and Drop Zone --}}
                <div 
                    x-data="{ isDragging: false }"
                    x-on:dragover.prevent="isDragging = true"
                    x-on:dragleave.prevent="isDragging = false"
                    x-on:drop.prevent="isDragging = false"
                    x-bind:class="{ 'border-primary-500 bg-primary-50 dark:bg-primary-900/20': isDragging }"
                    class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center transition-colors duration-200 hover:border-gray-400 dark:hover:border-gray-500"
                >
                    <input 
                        type="file" 
                        wire:model="files"
                        multiple
                        accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    >
                    
                    <div class="space-y-2">
                        <x-heroicon-o-cloud-arrow-up class="mx-auto h-12 w-12 text-gray-400" />
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-primary-600 dark:text-primary-400">Clique para selecionar</span>
                            ou arraste arquivos aqui
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-500">
                            JPG, PNG, GIF, WebP, MP4, WebM • Máximo 10MB por arquivo
                        </p>
                    </div>
                </div>

                {{-- Loading indicator --}}
                <div wire:loading wire:target="files" class="mt-4">
                    <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                        <x-filament::loading-indicator class="h-5 w-5" />
                        <span>Carregando arquivos...</span>
                    </div>
                </div>

                {{-- File Preview --}}
                @php
                    $fileList = is_array($files) ? $files : [];
                    $fileCount = 0;
                    $validFiles = [];
                    foreach ($fileList as $idx => $f) {
                        if ($f instanceof \Illuminate\Http\UploadedFile) {
                            $validFiles[$idx] = $f;
                            $fileCount++;
                        }
                    }
                @endphp
                
                @if($fileCount > 0)
                    <div class="mt-6" wire:loading.remove wire:target="files">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $fileCount }} arquivo(s) selecionado(s)
                            </h4>
                            <button 
                                type="button" 
                                wire:click="clearFiles"
                                class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            >
                                Limpar todos
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            @foreach($validFiles as $index => $file)
                                <div class="relative group">
                                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                        @php
                                            $mimeType = $file->getMimeType() ?? '';
                                            $isImage = str_starts_with($mimeType, 'image/');
                                        @endphp
                                        @if($isImage)
                                            <img 
                                                src="{{ $file->temporaryUrl() }}" 
                                                alt="{{ $file->getClientOriginalName() }}"
                                                class="w-full h-full object-cover"
                                            >
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <x-heroicon-o-film class="h-8 w-8 text-gray-400" />
                                            </div>
                                        @endif
                                    </div>
                                    <button 
                                        type="button"
                                        wire:click="removeFile({{ $index }})"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"
                                    >
                                        <x-heroicon-s-x-mark class="h-4 w-4" />
                                    </button>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $file->getClientOriginalName() }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Validation Errors --}}
                @error('files.*')
                    <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    </div>
                @enderror
            </div>
        </div>

        {{-- Upload Button --}}
        <div class="flex items-center gap-4">
            @php
                $btnDisabled = $fileCount === 0;
            @endphp
            <x-filament::button 
                wire:click="upload" 
                wire:loading.attr="disabled"
                wire:target="upload"
                size="lg"
                :disabled="$btnDisabled"
            >
                <span wire:loading.remove wire:target="upload">
                    <x-heroicon-o-arrow-up-tray class="w-5 h-5 mr-2 inline" />
                    Enviar {{ $fileCount }} Arquivo(s)
                </span>
                <span wire:loading wire:target="upload">
                    <x-filament::loading-indicator class="h-5 w-5 mr-2 inline" />
                    Enviando...
                </span>
            </x-filament::button>

            @if($successCount > 0 || $errorCount > 0)
                <div class="text-sm">
                    @if($successCount > 0)
                        <span class="text-green-600 dark:text-green-400">
                            ✓ {{ $successCount }} enviado(s)
                        </span>
                    @endif
                    @if($errorCount > 0)
                        <span class="text-red-600 dark:text-red-400 ml-2">
                            ✗ {{ $errorCount }} erro(s)
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Error List --}}
        @if(count($uploadErrors) > 0)
            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Erros:</h4>
                <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                    @foreach($uploadErrors as $uploadError)
                        <li>{{ $uploadError }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Back Link --}}
        <div class="pt-4">
            <x-filament::link href="{{ route('filament.admin.resources.media.index') }}" color="gray">
                ← Voltar para lista de mídias
            </x-filament::link>
        </div>
    </div>
</x-filament-panels::page>
