<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Album Selection --}}
        <x-filament::section>
            <x-slot name="heading">
                Selecione o Álbum
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::link 
                    wire:click="toggleCreateAlbum"
                    tag="button"
                    size="sm"
                >
                    @if($showCreateAlbum)
                        Cancelar
                    @else
                        + Criar novo álbum
                    @endif
                </x-filament::link>
            </x-slot>

            @if($showCreateAlbum)
                {{-- Create Album Form --}}
                <div class="space-y-4 p-4 rounded-lg mb-4 bg-gray-50 dark:bg-gray-800/50">
                    <h4 class="text-sm font-medium">Criar Novo Álbum</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">
                                Tipo do Álbum *
                            </label>
                            <select 
                                wire:model="newAlbumType"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                required
                            >
                                <option value="">Selecione o tipo</option>
                                @foreach($this->getAlbumTypeOptions() as $slug => $name)
                                    <option value="{{ $slug }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium mb-1 block">
                                Nome do Álbum *
                            </label>
                            <input 
                                type="text"
                                wire:model="newAlbumName"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Ex: Ensaio Pré-Wedding"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <x-filament::button 
                            wire:click="createAlbum"
                            size="sm"
                            wire:loading.attr="disabled"
                            wire:target="createAlbum"
                        >
                            <span wire:loading.remove wire:target="createAlbum">Criar Álbum</span>
                            <span wire:loading wire:target="createAlbum">Criando...</span>
                        </x-filament::button>
                    </div>
                </div>
            @endif

            <div>
                <select 
                    wire:model="albumId"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    required
                >
                    <option value="">Selecione um álbum *</option>
                    @foreach($this->getAlbumOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <span class="text-danger-600 dark:text-danger-400">*</span> Obrigatório: As mídias devem ser associadas a um álbum
                </p>
            </div>
        </x-filament::section>

        {{-- File Upload Area --}}
        <x-filament::section>
            <x-slot name="heading">
                Upload de Arquivos
            </x-slot>

            {{-- Drag and Drop Zone --}}
            <div 
                class="relative border-2 border-dashed rounded-lg p-4 text-center transition-colors duration-200 border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500"
            >
                <input 
                    type="file" 
                    wire:model.live="files"
                    multiple
                    accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    id="file-upload"
                >
                
                <div class="space-y-1">
                    <x-heroicon-o-cloud-arrow-up class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500" />
                    <div class="text-sm">
                        <label for="file-upload" class="font-semibold cursor-pointer text-primary-600 dark:text-primary-400">
                            Clique para selecionar múltiplos arquivos
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        JPG, PNG, GIF, WebP, MP4, WebM • Máximo 10MB por arquivo
                    </p>
                </div>
            </div>

            {{-- Loading indicator --}}
            <div wire:loading wire:target="files" class="mt-4">
                <div class="flex items-center justify-center gap-2 text-sm">
                    <x-filament::loading-indicator class="h-5 w-5" />
                    <span>Carregando arquivos...</span>
                </div>
            </div>

            {{-- Debug button (temporary) --}}
            @if(app()->environment('local'))
                <div class="mt-2">
                    <x-filament::link 
                        wire:click="debugFiles"
                        tag="button"
                        size="xs"
                        color="gray"
                    >
                        Debug Files ({{ count($this->files ?? []) }})
                    </x-filament::link>
                </div>
            @endif

                {{-- File Preview --}}
                @php
                    $fileCount = 0;
                    $validFiles = [];
                    
                    if (is_array($this->files)) {
                        foreach ($this->files as $idx => $file) {
                            if ($file instanceof \Illuminate\Http\UploadedFile) {
                                $validFiles[$idx] = $file;
                                $fileCount++;
                            }
                        }
                    }
                @endphp
                
                @if($fileCount > 0)
                    <div class="mt-6" wire:loading.remove wire:target="files">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium">
                                {{ $fileCount }} arquivo(s) selecionado(s)
                            </h4>
                            <x-filament::link 
                                wire:click="clearFiles"
                                tag="button"
                                size="sm"
                                color="danger"
                            >
                                Limpar todos
                            </x-filament::link>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            @foreach($validFiles as $index => $file)
                                <div class="relative group">
                                    <div class="aspect-square rounded-lg overflow-hidden border bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                        @php
                                            try {
                                                $mimeType = $file->getMimeType() ?? '';
                                                $isImage = str_starts_with($mimeType, 'image/');
                                            } catch (\Exception $e) {
                                                $isImage = false;
                                            }
                                        @endphp
                                        @if($isImage)
                                            @try
                                                <img 
                                                    src="{{ $file->temporaryUrl() }}" 
                                                    alt="{{ $file->getClientOriginalName() }}"
                                                    class="w-full h-full object-cover"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                >
                                                <div class="w-full h-full flex items-center justify-center" style="display: none;">
                                                    <x-heroicon-o-photo class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                                </div>
                                            @catch (\Exception $e)
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <x-heroicon-o-photo class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                                </div>
                                            @endtry
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <x-heroicon-o-film class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                            </div>
                                        @endif
                                    </div>
                                    <button 
                                        type="button"
                                        wire:click="removeFile({{ $index }})"
                                        class="absolute -top-2 -right-2 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg bg-danger-500 text-white"
                                    >
                                        <x-heroicon-s-x-mark class="h-4 w-4" />
                                    </button>
                                    <p class="mt-1 text-xs truncate text-gray-500 dark:text-gray-400">
                                        {{ $file->getClientOriginalName() }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Validation Errors --}}
                @error('files.*')
                    <x-filament::section 
                        class="mt-4"
                        :color="'danger'"
                    >
                        <p class="text-sm">{{ $message }}</p>
                    </x-filament::section>
                @enderror
        </x-filament::section>

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
                        <span class="text-success-600 dark:text-success-400">
                            ✓ {{ $successCount }} enviado(s)
                        </span>
                    @endif
                    @if($errorCount > 0)
                        <span class="ml-2 text-danger-600 dark:text-danger-400">
                            ✗ {{ $errorCount }} erro(s)
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Error List --}}
        @if(count($uploadErrors) > 0)
            <x-filament::section color="danger">
                <x-slot name="heading">
                    Erros
                </x-slot>
                <ul class="text-sm list-disc list-inside space-y-1">
                    @foreach($uploadErrors as $uploadError)
                        <li>{{ $uploadError }}</li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        {{-- Back Link --}}
        <div class="pt-4">
            <x-filament::link href="{{ route('filament.admin.resources.media.index') }}" color="gray">
                ← Voltar para lista de mídias
            </x-filament::link>
        </div>
    </div>
</x-filament-panels::page>
