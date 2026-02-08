<div class="media-gallery-blade" style="width: 100% !important;" x-data>
    {{-- CSS Local: Purificação Total --}}
    <style>
        .fi-main-ctn, .fi-page, .fi-content { max-width: none !important; width: 100% !important; margin: 0 !important; }
        .media-gallery-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 1rem;
            width: 100%;
            align-items: start;
            /* padding: 1.5rem; */
        }
        @media (max-width: 1024px) {
            .media-gallery-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Remover o título nativo do Filament Page para evitar duplicidade se necessário, 
           ou apenas garantir que o nosso não seja gigante. 
           Vamos remover o nosso heading H1 e usar o do Filament. */
    </style>

    <div class="media-gallery-grid" x-data="{ dragover: false }">
        {{-- Sidebar: Álbuns --}}
        <div class="w-full">
            <x-filament::section>
                <x-slot name="heading">
                    <span class="flex items-center gap-2 font-black uppercase tracking-tight">
                        <x-filament::icon icon="heroicon-m-folder" class="w-4 h-4" />
                        Álbuns
                    </span>
                </x-slot>
                
                <x-slot name="headerEnd">
                    <x-filament::icon-button
                        wire:click="openCreateModal"
                        icon="heroicon-m-plus"
                        color="success"
                        tooltip="Novo álbum"
                        size="sm"
                    />
                </x-slot>

                <div class="divide-y divide-gray-100 dark:divide-white/5 max-h-[calc(100vh-280px)] overflow-y-auto -mx-2">
                    @forelse($this->albums as $album)
                        <div
                            class="relative transition-all hover:bg-gray-50 dark:hover:bg-white/5 {{ $selectedAlbumId === $album['id'] ? 'bg-primary-50 dark:bg-primary-500/10' : '' }}"
                        >
                            <div class="flex items-center px-4 py-4">
                                <button
                                    wire:click="selectAlbum('{{ $album['id'] }}')"
                                    class="flex-1 min-w-0 text-left outline-none"
                                >
                                    <p class="text-sm font-black text-gray-900 dark:text-gray-100 truncate">
                                        {{ $album['name'] }}
                                    </p>
                                    <p class="text-[10px] uppercase font-bold text-gray-400 mt-0.5">
                                        {{ $album['media_count'] }} {{ $album['media_count'] === 1 ? 'item' : 'itens' }}
                                    </p>
                                </button>
                                
                                <x-filament::dropdown placement="bottom-end">
                                    <x-slot name="trigger">
                                        <x-filament::icon-button
                                            icon="heroicon-m-ellipsis-horizontal"
                                            color="gray"
                                            size="sm"
                                        />
                                    </x-slot>

                                    <x-filament::dropdown.list>
                                        <x-filament::dropdown.list.item
                                            wire:click="openEditModal('{{ $album['id'] }}')"
                                            icon="heroicon-m-pencil-square"
                                        >
                                            Editar Nome
                                        </x-filament::dropdown.list.item>

                                        @if($album['media_count'] === 0)
                                            <x-filament::dropdown.list.item
                                                wire:click="openDeleteModal('{{ $album['id'] }}')"
                                                icon="heroicon-m-trash"
                                                color="danger"
                                            >
                                                Excluir Álbum
                                            </x-filament::dropdown.list.item>
                                        @endif
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400">
                             <p class="text-xs italic">Nenhum álbum criado.</p>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        {{-- Content Area --}}
        <div class="w-full">
            @if($this->selectedAlbum)
                <x-filament::section>
                    <x-slot name="heading">
                        <span class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                            <span class="font-black">{{ $this->selectedAlbum['name'] }}</span>
                        </span>
                    </x-slot>



                    {{-- Upload --}}
                    <div
                        class="relative mb-10 border-4 border-dashed rounded-3xl p-16 text-center transition-all bg-gray-50/30 dark:bg-white/5"
                        :class="{ 'border-primary-500 bg-primary-500/10': dragover, 'border-gray-200 dark:border-white/10': !dragover }"
                        x-on:dragover.prevent="dragover = true"
                        x-on:dragleave.prevent="dragover = false"
                        x-on:drop.prevent="dragover = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                    >
                        <input
                            type="file"
                            x-ref="fileInput"
                            wire:model="uploadFiles"
                            multiple
                            accept="image/*,video/*"
                            class="hidden"
                        >
                        
                        <div class="flex flex-col items-center gap-6">
                            <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="w-16 h-16 text-gray-300" />
                            <div class="space-y-2 p-6">
                                <h3 class="text-xl font-black">Arraste seus arquivos</h3>
                                <p class="text-sm text-gray-500">
                                    Ou 
                                    <button type="button" x-on:click="$refs.fileInput.click()" class="text-primary-600 font-bold hover:underline">
                                        clique para selecionar
                                    </button>
                                </p>
                            </div>
                        </div>

                        @if($isUploading)
                            <div class="mt-8">
                                <x-filament::loading-indicator class="h-10 w-10 text-primary-600 mx-auto" />
                            </div>
                        @endif
                    </div>

                    {{-- Grid Controls --}}
                    <div class="flex justify-end gap-2 mb-4 px-2">
                            <x-filament::icon-button
                                wire:click="setGridSize('small')"
                                icon="heroicon-s-rectangle-stack"
                                :color="$gridSize === 'small' ? 'primary' : 'gray'"
                                size="sm"
                                tooltip="Visualização Compacta"
                            />
                            <x-filament::icon-button
                                wire:click="setGridSize('medium')"
                                icon="heroicon-s-squares-2x2"
                                :color="$gridSize === 'medium' ? 'primary' : 'gray'"
                                size="sm"
                                tooltip="Visualização Padrão"
                            />
                            <x-filament::icon-button
                                wire:click="setGridSize('large')"
                                icon="heroicon-m-view-columns"
                                :color="$gridSize === 'large' ? 'primary' : 'gray'"
                                size="sm"
                                tooltip="Visualização Detalhada"
                            />
                    </div>

                    {{-- Selection Actions --}}
                    @if(count($selectedMediaIds) > 0)
                        <div class="mb-8 p-6 bg-white dark:bg-primary-600 rounded-3xl shadow-2xl flex flex-wrap items-center justify-between gap-6 ring-1 ring-gray-200 dark:ring-white/10">
                            <div class="flex items-center gap-8">
                                <div class="px-5 py-2 bg-primary-50 dark:bg-white/10 rounded-2xl">
                                    <span class="text-3xl font-black text-primary-600 dark:text-white leading-none">{{ count($selectedMediaIds) }}</span>
                                    <span class="text-[10px] text-gray-500 dark:text-white/60 uppercase font-black ml-2 tracking-tighter">Itens</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <button wire:click="selectAllMedia" class="text-sm font-black text-gray-600 hover:text-primary-600 dark:text-white dark:hover:text-primary-100 transition-all">Todos</button>
                                    <button wire:click="clearSelection" class="text-sm font-black text-gray-400 hover:text-gray-900 dark:text-white/50 dark:hover:text-white transition-all">Limpar</button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-filament::button
                                    wire:click="openMoveModal"
                                    color="gray"
                                    icon="heroicon-m-folder-arrow-down"
                                    class="!bg-gray-100 dark:!bg-white/10 !border-none !text-gray-900 dark:!text-white active:scale-95 transition-transform"
                                >
                                    Mover
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="openDeleteMediaModal"
                                    color="danger"
                                    icon="heroicon-m-trash"
                                    class="shadow-xl active:scale-95 transition-transform"
                                >
                                    Excluir
                                </x-filament::button>
                            </div>
                        </div>
                    @endif

                    {{-- Grid --}}
                    @if(count($this->selectedAlbum['media']) > 0)
                        <div class="grid gap-4 {{ $gridSize === 'small' ? 'grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10' : ($gridSize === 'medium' ? 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6' : 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4')}} pt-6">
                            @foreach($this->selectedAlbum['media'] as $media)
                                <div
                                    wire:key="{{ $media['id'] }}"
                                    class="relative group rounded-3xl overflow-hidden bg-gray-100 dark:bg-white/5 cursor-pointer border-4 transition-all duration-300 {{ in_array($media['id'], $selectedMediaIds) ? 'border-primary-500 scale-95 shadow-2xl' : 'border-transparent hover:border-black/5 dark:hover:border-white/10' }}"
                                    style="aspect-ratio: {{ $gridSize === 'large' ? '3/4' : '1/1' }};"
                                    wire:click="toggleMediaSelection('{{ $media['id'] }}')"
                                >
                                    @if($media['type'] === 'image')
                                        <img src="{{ $media['thumbnail_url'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                            <x-filament::icon icon="heroicon-o-play" class="w-12 h-12 text-gray-400" />
                                        </div>
                                    @endif

                                    <div class="absolute inset-0 bg-black/5 hover:bg-black/0 transition-colors"></div>
                                    
                                    <div class="absolute z-10" style="top: 1rem; right: 1rem; left: auto;">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center border-2 border-white shadow-lg {{ in_array($media['id'], $selectedMediaIds) ? 'bg-primary-500 text-white' : 'bg-black/20 text-transparent' }}">
                                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                                        </div>
                                    </div>

                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-4 translate-y-full group-hover:translate-y-0 transition-transform">
                                        <p class="text-[10px] text-white font-black truncate">{{ $media['filename'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-40 text-center opacity-40">
                            <x-filament::icon icon="heroicon-o-photo" class="w-20 h-20 mb-6" />
                            <h3 class="text-xl font-black text-gray-900 dark:text-gray-100">Nenhuma mídia encontrada</h3>
                        </div>
                    @endif
                </x-filament::section>
            @else
                <x-filament::section class="h-full min-h-[500px] flex items-center justify-center">
                    <div class="text-center space-y-8 py-20">
                         <div class="w-32 h-32 bg-primary-500/10 rounded-full flex items-center justify-center mx-auto ring-8 ring-primary-500/5">
                             <x-filament::icon icon="heroicon-o-folder-open" class="w-16 h-16 text-primary-500" />
                         </div>
                         <div class="space-y-2">
                             <h2 class="text-3xl font-black">Selecione um álbum</h2>
                             <p class="text-gray-400 max-w-sm mx-auto font-medium tracking-tight">Escolha uma categoria à esquerda para gerenciar as lembranças do evento.</p>
                         </div>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>

    {{-- Modals --}}
    
    {{-- Create/Edit --}}
    <x-filament::modal
        id="create-edit-album-modal"
        :visible="$showCreateModal || $showEditModal"
        :heading="$showCreateModal ? 'Novo Álbum' : 'Editar Álbum'"
        width="lg"
        on-close="$wire.set('showCreateModal', false); $wire.set('showEditModal', false);"
    >
        <form wire:submit="{{ $showCreateModal ? 'createAlbum' : 'updateAlbum' }}" class="space-y-8 py-6">
            <div class="space-y-3">
                <label class="text-xs font-black uppercase tracking-widest text-gray-400">Nome Oficial do Álbum</label>
                <x-filament::input
                    type="text"
                    wire:model="albumName"
                    placeholder="Ex: Momentos da Cerimônia"
                    required
                    class="!py-4 !text-lg !font-bold"
                />
            </div>
            
            <div class="space-y-3">
                <label class="text-xs font-black uppercase tracking-widest text-gray-400">Classificação</label>
                <x-filament::input.select wire:model="albumType" required class="!py-4 overflow-hidden">
                    @foreach($this->albumTypes as $type)
                        <option value="{{ $type['slug'] }}">{{ $type['name'] }}</option>
                    @endforeach
                </x-filament::input.select>
            </div>
            
            <div class="flex justify-end gap-4 border-t border-gray-100 dark:border-white/5 pt-4">
                <x-filament::button
                    color="gray"
                    wire:click="{{ $showCreateModal ? 'closeCreateModal' : 'closeEditModal' }}"
                    variant="ghost"
                    class="font-black translate-y-[2px]"
                >
                    Cancelar
                </x-filament::button>
                <x-filament::button type="submit" size="xl" class="shadow-2xl">
                    {{ $showCreateModal ? 'Criar Álbum' : 'Salvar Mudanças' }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>

    {{-- Delete --}}
    <x-filament::modal
        id="delete-album-modal"
        :visible="$showDeleteModal"
        heading="Excluir Permanentemente"
        width="md"
        on-close="$wire.set('showDeleteModal', false)"
        alignment="center"
        icon="heroicon-o-trash"
        icon-color="danger"
    >
        <div class="text-center py-6 space-y-6">
            <div class="bg-rose-50 dark:bg-rose-500/10 p-6 rounded-3xl border border-rose-100 dark:border-rose-500/20">
                <p class="text-rose-900 dark:text-rose-400 font-bold leading-relaxed">
                    Atenção: Ao confirmar, todas as mídias deste álbum serão <strong>destruídas</strong> e não poderão ser recuperadas.
                </p>
            </div>
            <div class="flex justify-center gap-4">
                <x-filament::button color="gray" wire:click="closeDeleteModal" variant="ghost" class="font-black">Voltar</x-filament::button>
                <x-filament::button color="danger" wire:click="deleteAlbum" size="xl" class="shadow-2xl">Confirmar Exclusão</x-filament::button>
            </div>
        </div>
    </x-filament::modal>

    {{-- Delete Media --}}
    <x-filament::modal
        id="delete-media-modal"
        :visible="$showDeleteMediaModal"
        heading="Excluir Mídias Permanentemente"
        width="md"
        on-close="$wire.set('showDeleteMediaModal', false)"
        alignment="center"
        icon="heroicon-o-trash"
        icon-color="danger"
    >
        <div class="text-center py-6 space-y-6">
            <div class="bg-rose-50 dark:bg-rose-500/10 p-6 rounded-3xl border border-rose-100 dark:border-rose-500/20">
                <p class="text-rose-900 dark:text-rose-400 font-bold leading-relaxed">
                    Atenção: Ao confirmar, {{ count($selectedMediaIds) }} mídia(s) será(ão) <strong>destruída(s)</strong> e não poderá(ão) ser recuperada(s).
                </p>
            </div>
            <div class="flex justify-center gap-4">
                <x-filament::button color="gray" wire:click="closeDeleteMediaModal" variant="ghost" class="font-black">Voltar</x-filament::button>
                <x-filament::button color="danger" wire:click="deleteSelectedMedia" size="xl" class="shadow-2xl">Confirmar Exclusão</x-filament::button>
            </div>
        </div>
    </x-filament::modal>

    {{-- Move --}}
    <x-filament::modal
        id="move-media-modal"
        :visible="$showMoveModal"
        :heading="'Mover ' . count($selectedMediaIds) . ' Itens'"
        width="lg"
        on-close="$wire.set('showMoveModal', false)"
    >
        <div class="space-y-2 py-6">
            <p class="text-sm font-bold text-gray-400 mb-4 px-2">Selecione o destino estratégico:</p>
            <div class="grid gap-2 overflow-y-auto max-h-[50vh] pr-2">
                @foreach($this->otherAlbums as $album)
                    <button
                        wire:click="moveMediaToAlbum('{{ $album['id'] }}')"
                        class="w-full flex items-center justify-between p-6 rounded-3xl border border-gray-100 dark:border-white/5 hover:border-primary-500 hover:bg-primary-500/5 transition-all group"
                    >
                        <div class="text-left">
                            <p class="text-lg font-black group-hover:text-primary-500">{{ $album['name'] }}</p>
                            <p class="text-xs text-gray-400 font-bold">{{ $album['media_count'] }} itens</p>
                        </div>
                        <x-filament::icon icon="heroicon-m-chevron-right" class="w-6 h-6 text-gray-200 group-hover:text-primary-500" />
                    </button>
                @endforeach
            </div>
        </div>
    </x-filament::modal>
</div>
