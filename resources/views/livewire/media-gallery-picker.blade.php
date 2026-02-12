<div>
    @if($show)
        <!-- Modal Overlay -->
        <div class="fixed inset-0 overflow-y-auto" style="z-index: 9998;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$wire.close()"></div>

                <!-- Modal panel -->
                <div class="relative inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-lg shadow-xl">
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
                        <button @click="$wire.close()" type="button" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
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
                                                <p class="text-white/80 text-xs">{{ $album['media_count'] }} {{ $album['media_count'] == 1 ? 'imagem' : 'imagens' }}</p>
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

                                    <div class="grid gap-3 media-gallery-grid">
                                        @forelse($albumMedia as $media)
                                            <button
                                                wire:click="selectImage('{{ $media['id'] }}', {{ $maxWidth ?? 'null' }}, {{ $maxHeight ?? 'null' }})"
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
            class="fixed inset-0 overflow-y-auto"
            style="z-index: 9999;"
            x-data="cropModalComponent({{ json_encode($imageToCrop) }}, {{ json_encode($cropArea) }}, { maxWidth: {{ $maxWidth ?? 'null' }}, maxHeight: {{ $maxHeight ?? 'null' }} })"
            x-init="init()"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-90" @click="$wire.cancelCrop()"></div>

                <!-- Modal panel -->
                <div class="relative inline-block w-full max-w-6xl my-8 overflow-hidden max-h-[85vh] text-left align-middle transition-all transform bg-white rounded-lg shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Ajustar Imagem</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Arraste a área de seleção para escolher a parte da imagem que deseja usar
                            </p>
                        </div>
                        <button @click="$wire.cancelCrop()" type="button" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 bg-gray-100 max-h-[70vh] overflow-hidden">
                        <!-- Debug info -->
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-xs font-mono space-y-1" x-data>
                            <div><strong>Image Data:</strong> <span x-text="imageData ? `${imageData.width}x${imageData.height}` : 'null'"></span></div>
                            <div><strong>Crop Area:</strong> <span x-text="`${Math.round(cropArea.x)}, ${Math.round(cropArea.y)}, ${Math.round(cropArea.width)}x${Math.round(cropArea.height)}`"></span></div>
                            <div><strong>Image Scale:</strong> <span x-text="`${imageScale.x.toFixed(2)} x ${imageScale.y.toFixed(2)}`"></span></div>
                            <div><strong>Overlay Visible:</strong> <span x-text="imageScale.x > 0 && imageScale.y > 0 ? 'Yes' : 'No'"></span></div>
                        </div>
                        
                        <div class="flex justify-center items-center min-h-[320px] bg-gray-900 rounded-lg p-4 overflow-hidden">
                            <div class="relative inline-block max-h-[60vh]" x-ref="cropWrapper">
                                <img 
                                    x-ref="cropImage"
                                    src="{{ $imageToCrop['url'] ?? '' }}" 
                                    alt="{{ $imageToCrop['alt'] ?? '' }}" 
                                    class="max-w-full max-h-[60vh] w-auto h-auto object-contain select-none"
                                    @load="onImageLoad()"
                                    x-on:error="console.error('Image failed to load:', $event)"
                                    draggable="false"
                                />
                                <div
                                    x-show="imageScale.x > 0 && imageScale.y > 0"
                                    x-ref="cropOverlay"
                                    class="absolute cursor-move border-2 border-white/90 ring-2 ring-primary-500/80 bg-transparent"
                                    :style="cropOverlayStyle"
                                    @mousedown="startDrag($event)"
                                    style="box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.55); background-color: rgba(255, 255, 255, 0.02);"
                                >
                                    <div class="absolute inset-0 border border-white/40 pointer-events-none"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Crop info -->
                        <div class="mt-4 text-center text-sm text-gray-600">
                            <span>Área selecionada: </span>
                            <span class="font-mono" x-text="Math.round(cropArea.width) + ' × ' + Math.round(cropArea.height) + ' px'"></span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200">
                        <button 
                            @click="$wire.cancelCrop()" 
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button 
                            @click="confirmCrop()" 
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                        >
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @endif
</div>

@push('scripts')
<script>
    function cropModalComponent(imageData, cropArea, constraints = {}) {
        console.log('Crop modal init', { imageData, cropArea, constraints });
        
        return {
            imageData: imageData || null,
            cropArea: cropArea ? { ...cropArea } : { x: 0, y: 0, width: 0, height: 0 },
            isDragging: false,
            dragStart: { mouseX: 0, mouseY: 0, cropX: 0, cropY: 0 },
            imageScale: { x: 1, y: 1 },
            maxWidth: constraints.maxWidth ?? null,
            maxHeight: constraints.maxHeight ?? null,
            
            init() {
                // Wait for next tick to ensure image is loaded
                this.$nextTick(() => {
                    this.onImageLoad();
                });
            },
            
            onImageLoad() {
                if (this.maxWidth !== null) {
                    this.maxWidth = Number(this.maxWidth);
                }
                if (this.maxHeight !== null) {
                    this.maxHeight = Number(this.maxHeight);
                }
                if (this.cropArea?.width === null || this.cropArea?.width === undefined) {
                    this.cropArea.width = 0;
                }
                if (this.cropArea?.height === null || this.cropArea?.height === undefined) {
                    this.cropArea.height = 0;
                }
                const img = this.$refs.cropImage;
                if (!img || !this.imageData) {
                    console.log('Image not ready', { img, imageData: this.imageData });
                    return;
                }
                
                // Wait for image to actually load
                if (!img.complete || img.naturalWidth === 0) {
                    console.log('Image not fully loaded, waiting...');
                    img.addEventListener('load', () => this.onImageLoad(), { once: true });
                    return;
                }

                const naturalWidth = img.naturalWidth || img.clientWidth;
                const naturalHeight = img.naturalHeight || img.clientHeight;

                if (!this.imageData.width || !this.imageData.height) {
                    this.imageData.width = naturalWidth;
                    this.imageData.height = naturalHeight;
                }

                const targetWidth = this.maxWidth ?? this.imageData.width;
                const targetHeight = this.maxHeight ?? this.imageData.height;

                if (!this.cropArea || !this.cropArea.width || !this.cropArea.height || this.maxWidth || this.maxHeight) {
                    this.cropArea = {
                        x: Math.max(0, (this.imageData.width - targetWidth) / 2),
                        y: Math.max(0, (this.imageData.height - targetHeight) / 2),
                        width: targetWidth,
                        height: targetHeight,
                    };
                    if (this.$wire) {
                        this.$wire.set('cropArea', this.cropArea);
                    }
                }

                this.cropArea.width = Math.min(this.cropArea.width, this.imageData.width);
                this.cropArea.height = Math.min(this.cropArea.height, this.imageData.height);
                this.cropArea.x = Math.max(0, Math.min(this.cropArea.x, this.imageData.width - this.cropArea.width));
                this.cropArea.y = Math.max(0, Math.min(this.cropArea.y, this.imageData.height - this.cropArea.height));
                
                // Calculate scale between actual image size and displayed size
                this.imageScale = {
                    x: this.imageData.width / (img.clientWidth || naturalWidth),
                    y: this.imageData.height / (img.clientHeight || naturalHeight),
                };
                
                console.log('Image loaded', {
                    actualSize: { w: this.imageData.width, h: this.imageData.height },
                    displaySize: { w: img.clientWidth, h: img.clientHeight },
                    scale: this.imageScale,
                    cropArea: this.cropArea,
                    overlayStyle: this.cropOverlayStyle
                });
            },
            
            get cropOverlayStyle() {
                if (!this.imageScale.x || !this.imageScale.y || !this.cropArea) {
                    return 'display: none;';
                }
                
                const displayX = this.cropArea.x / this.imageScale.x;
                const displayY = this.cropArea.y / this.imageScale.y;
                const displayWidth = this.cropArea.width / this.imageScale.x;
                const displayHeight = this.cropArea.height / this.imageScale.y;
                
                return `left: ${displayX}px; top: ${displayY}px; width: ${displayWidth}px; height: ${displayHeight}px;`;
            },
            
            startDrag(event) {
                event.preventDefault();
                event.stopPropagation();
                
                this.isDragging = true;
                this.dragStart = {
                    mouseX: event.clientX,
                    mouseY: event.clientY,
                    cropX: this.cropArea.x,
                    cropY: this.cropArea.y,
                };
                
                console.log('Start drag', this.dragStart);
                
                const onDragBound = this.onDrag.bind(this);
                const stopDragBound = this.stopDrag.bind(this);
                
                document.addEventListener('mousemove', onDragBound);
                document.addEventListener('mouseup', stopDragBound);
                
                // Store for cleanup
                this._onDragBound = onDragBound;
                this._stopDragBound = stopDragBound;
            },
            
            onDrag(event) {
                if (!this.isDragging || !this.imageData) return;
                
                event.preventDefault();
                
                const deltaX = event.clientX - this.dragStart.mouseX;
                const deltaY = event.clientY - this.dragStart.mouseY;
                
                let newX = this.dragStart.cropX + (deltaX * this.imageScale.x);
                let newY = this.dragStart.cropY + (deltaY * this.imageScale.y);
                
                // Constrain to image bounds
                newX = Math.max(0, Math.min(newX, this.imageData.width - this.cropArea.width));
                newY = Math.max(0, Math.min(newY, this.imageData.height - this.cropArea.height));
                
                this.cropArea.x = newX;
                this.cropArea.y = newY;
            },
            
            stopDrag() {
                if (!this.isDragging) return;
                
                this.isDragging = false;
                
                console.log('Stop drag, final crop area:', this.cropArea);
                
                // Cleanup
                if (this._onDragBound) {
                    document.removeEventListener('mousemove', this._onDragBound);
                }
                if (this._stopDragBound) {
                    document.removeEventListener('mouseup', this._stopDragBound);
                }
            },
            
            confirmCrop() {
                console.log('Confirming crop with area:', this.cropArea);
                
                // Sync final state with Livewire before confirming
                if (this.$wire) {
                    this.$wire.set('cropArea', this.cropArea).then(() => {
                        this.$wire.call('confirmCrop');
                    });
                }
            }
        };
    }
</script>
@endpush

@push('styles')
<style>
    .media-gallery-grid {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }

    @media (max-width: 640px) {
        .media-gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
    }
</style>
@endpush
