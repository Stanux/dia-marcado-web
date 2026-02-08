<div>
    @if($show)
        <!-- Modal Overlay -->
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="close"></div>

                <!-- Modal panel -->
                <div class="inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-lg shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Selecionar Imagem da Galeria</h3>
                            @if($maxWidth || $maxHeight)
                                <p class="text-sm text-gray-500 mt-1">
                                    Dimensões máximas: {{ $maxWidth ?? '∞' }}×{{ $maxHeight ?? '∞' }}px
                                </p>
                            @endif
                        </div>
                        <button wire:click="close" type="button" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                        <!-- Debug info -->
                        <div class="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                            <p>Debug: selectedAlbumId = {{ $selectedAlbumId ?? 'null' }}</p>
                            <p>Albums count: {{ count($albums) }}</p>
                            <p>Media count: {{ count($albumMedia) }}</p>
                        </div>

                        <!-- Loading indicator -->
                        <div wire:loading class="flex justify-center items-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                            <span class="ml-3 text-gray-600">Carregando...</span>
                        </div>

                        <div wire:loading.remove>
                            @if(!$selectedAlbumId)
                                <!-- Albums Grid -->
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    @forelse($albums as $album)
                                        <button
                                            wire:click="selectAlbum('{{ $album['id'] }}')"
                                            type="button"
                                            class="group relative aspect-square rounded-lg overflow-hidden border-2 border-gray-200 hover:border-primary-500 transition-all"
                                        >
                                            @if($album['cover_url'])
                                                <img src="{{ $album['cover_url'] }}" alt="{{ $album['name'] }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3">
                                                <p class="text-white font-medium text-sm truncate">{{ $album['name'] }}</p>
                                                <p class="text-white/80 text-xs">{{ $album['media_count'] }} imagens (ID: {{ $album['id'] }})</p>
                                            </div>
                                        </button>
                                    @empty
                                        <div class="col-span-full text-center py-12 text-gray-500">
                                            Nenhum álbum encontrado
                                        </div>
                                    @endforelse
                                </div>
                            @else
                                <!-- Album Media Grid -->
                                <div class="space-y-4">
                                    <button wire:click="backToAlbums" type="button" class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                        Voltar para álbuns
                                    </button>

                                    <div class="text-sm text-gray-600 mb-2">
                                        Total de imagens: {{ count($albumMedia) }}
                                    </div>

                                    <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                        @forelse($albumMedia as $media)
                                            <button
                                                wire:click="selectImage({{ $media['id'] }})"
                                                type="button"
                                                class="group relative aspect-square rounded-lg overflow-hidden border-2 border-gray-200 hover:border-primary-500 transition-all"
                                            >
                                                <img 
                                                    src="{{ $media['thumbnail_url'] ?? $media['url'] }}" 
                                                    alt="{{ $media['alt'] ?? $media['filename'] }}" 
                                                    class="w-full h-full object-cover"
                                                />
                                                
                                                @if(isset($media['width']) && isset($media['height']))
                                                    @php
                                                        $needsCrop = ($maxWidth && $media['width'] > $maxWidth) || ($maxHeight && $media['height'] > $maxHeight);
                                                    @endphp
                                                    
                                                    @if($needsCrop)
                                                        <div class="absolute top-2 right-2 bg-primary-500 text-white text-xs px-2 py-1 rounded flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" />
                                                            </svg>
                                                            Crop
                                                        </div>
                                                    @endif
                                                    
                                                    <div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs px-2 py-1 rounded">
                                                        {{ $media['width'] }}×{{ $media['height'] }}
                                                    </div>
                                                @endif
                                            </button>
                                        @empty
                                            <div class="col-span-full text-center py-12 text-gray-500">
                                                Nenhuma imagem neste álbum
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showCropModal && $imageToCrop)
        <!-- Crop Modal -->
        <div 
            class="fixed inset-0 z-[60] overflow-y-auto"
            x-data="cropModalComponent()"
            x-init="init({{ json_encode($imageToCrop) }}, {{ json_encode($cropArea) }})"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-90" wire:click="cancelCrop"></div>

                <!-- Modal panel -->
                <div class="inline-block w-full max-w-6xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-lg shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Ajustar Imagem</h3>
                        <button wire:click="cancelCrop" type="button" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4">
                        <p class="text-sm text-gray-600 mb-4 text-center">
                            Arraste a área de seleção para escolher a parte da imagem que deseja usar
                        </p>

                        <div class="flex justify-center items-center min-h-[400px]">
                            <div class="relative inline-block" x-ref="cropWrapper">
                                <img 
                                    x-ref="cropImage"
                                    src="{{ $imageToCrop['url'] ?? '' }}" 
                                    alt="{{ $imageToCrop['alt'] ?? '' }}" 
                                    class="max-w-full max-h-[60vh]"
                                    @load="onImageLoad()"
                                />
                                <div
                                    x-ref="cropOverlay"
                                    class="absolute cursor-move border-2 border-primary-500"
                                    :style="cropOverlayStyle"
                                    @mousedown="startDrag($event)"
                                    style="box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);"
                                >
                                    <div class="absolute inset-0 border-2 border-dashed border-white"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200">
                        <button 
                            wire:click="cancelCrop" 
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button 
                            wire:click="confirmCrop" 
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                        >
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            function cropModalComponent() {
                return {
                    imageData: null,
                    cropArea: { x: 0, y: 0, width: 0, height: 0 },
                    isDragging: false,
                    dragStart: { x: 0, y: 0, cropX: 0, cropY: 0 },
                    imageScale: { x: 1, y: 1 },
                    
                    init(imageData, cropArea) {
                        this.imageData = imageData;
                        this.cropArea = cropArea;
                    },
                    
                    onImageLoad() {
                        const img = this.$refs.cropImage;
                        if (!img || !this.imageData) return;
                        
                        this.imageScale = {
                            x: this.imageData.width / img.clientWidth,
                            y: this.imageData.height / img.clientHeight,
                        };
                    },
                    
                    get cropOverlayStyle() {
                        const displayX = this.cropArea.x / this.imageScale.x;
                        const displayY = this.cropArea.y / this.imageScale.y;
                        const displayWidth = this.cropArea.width / this.imageScale.x;
                        const displayHeight = this.cropArea.height / this.imageScale.y;
                        
                        return `left: ${displayX}px; top: ${displayY}px; width: ${displayWidth}px; height: ${displayHeight}px;`;
                    },
                    
                    startDrag(event) {
                        event.preventDefault();
                        this.isDragging = true;
                        this.dragStart = {
                            mouseX: event.clientX,
                            mouseY: event.clientY,
                            cropX: this.cropArea.x,
                            cropY: this.cropArea.y,
                        };
                        
                        document.addEventListener('mousemove', this.onDrag.bind(this));
                        document.addEventListener('mouseup', this.stopDrag.bind(this));
                    },
                    
                    onDrag(event) {
                        if (!this.isDragging || !this.imageData) return;
                        
                        const deltaX = event.clientX - this.dragStart.mouseX;
                        const deltaY = event.clientY - this.dragStart.mouseY;
                        
                        let newX = this.dragStart.cropX + (deltaX * this.imageScale.x);
                        let newY = this.dragStart.cropY + (deltaY * this.imageScale.y);
                        
                        newX = Math.max(0, Math.min(newX, this.imageData.width - this.cropArea.width));
                        newY = Math.max(0, Math.min(newY, this.imageData.height - this.cropArea.height));
                        
                        this.cropArea.x = newX;
                        this.cropArea.y = newY;
                        
                        @this.set('cropArea', this.cropArea);
                    },
                    
                    stopDrag() {
                        this.isDragging = false;
                        document.removeEventListener('mousemove', this.onDrag);
                        document.removeEventListener('mouseup', this.stopDrag);
                    }
                };
            }
        </script>
        @endpush
    @endif
</div>
