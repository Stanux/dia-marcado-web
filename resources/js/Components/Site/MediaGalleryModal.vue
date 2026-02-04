<script setup>
/**
 * MediaGalleryModal Component
 * 
 * Modal para selecionar imagens da galeria com suporte a crop
 * quando a imagem excede as dimensões máximas permitidas.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: Number,
        default: null, // null = sem limite
    },
    maxHeight: {
        type: Number,
        default: null, // null = sem limite
    },
    title: {
        type: String,
        default: 'Selecionar Imagem da Galeria',
    },
});

const emit = defineEmits(['close', 'select']);

// Estado
const albums = ref([]);
const selectedAlbum = ref(null);
const albumMedia = ref([]);
const loading = ref(false);
const error = ref(null);

// Crop state
const showCropModal = ref(false);
const imageToCrop = ref(null);
const cropArea = ref({ x: 0, y: 0, width: 0, height: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const cropImage = ref(null);
const cropWrapper = ref(null);
const imageScale = ref({ x: 1, y: 1 });

/**
 * Quando a imagem de crop carregar, calcular a escala
 */
const onCropImageLoad = () => {
    if (!cropImage.value || !imageToCrop.value) return;
    
    const imgElement = cropImage.value;
    imageScale.value = {
        x: imageToCrop.value.width / imgElement.clientWidth,
        y: imageToCrop.value.height / imgElement.clientHeight,
    };
};

/**
 * Carregar álbuns
 */
const loadAlbums = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get('/admin/albums');
        albums.value = response.data.data || [];
    } catch (err) {
        error.value = 'Erro ao carregar álbuns';
        console.error('Load albums error:', err);
    } finally {
        loading.value = false;
    }
};

/**
 * Carregar mídias de um álbum
 */
const loadAlbumMedia = async (albumId) => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get(`/admin/albums/${albumId}/media`);
        const mediaList = response.data.data || [];
        
        // Carregar dimensões das imagens diretamente do arquivo
        const mediaWithDimensions = await Promise.all(
            mediaList.map(async (media) => {
                if (media.type === 'image' && (!media.width || !media.height)) {
                    try {
                        const dimensions = await getImageDimensions(media.url);
                        return {
                            ...media,
                            width: dimensions.width,
                            height: dimensions.height,
                        };
                    } catch (err) {
                        console.warn('Erro ao obter dimensões da imagem:', err);
                        return media;
                    }
                }
                return media;
            })
        );
        
        albumMedia.value = mediaWithDimensions;
    } catch (err) {
        error.value = 'Erro ao carregar imagens do álbum';
        console.error('Load album media error:', err);
    } finally {
        loading.value = false;
    }
};

/**
 * Obter dimensões de uma imagem a partir da URL
 */
const getImageDimensions = (url) => {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            resolve({
                width: img.naturalWidth,
                height: img.naturalHeight,
            });
        };
        img.onerror = reject;
        img.src = url;
    });
};

/**
 * Selecionar álbum
 */
const selectAlbum = async (album) => {
    selectedAlbum.value = album;
    await loadAlbumMedia(album.id);
};

/**
 * Voltar para lista de álbuns
 */
const backToAlbums = () => {
    selectedAlbum.value = null;
    albumMedia.value = [];
};

/**
 * Verificar se imagem precisa de crop
 */
const needsCrop = (media) => {
    if (!props.maxWidth && !props.maxHeight) return false;
    
    // Se não tiver dimensões, assumir que precisa de crop por segurança
    if (!media.width || !media.height) {
        console.warn('Imagem sem dimensões, assumindo que precisa de crop');
        return true;
    }
    
    const exceedsWidth = props.maxWidth && media.width > props.maxWidth;
    const exceedsHeight = props.maxHeight && media.height > props.maxHeight;
    
    return exceedsWidth || exceedsHeight;
};

/**
 * Selecionar imagem
 */
const selectImage = (media) => {
    console.log('Media selecionada:', media);
    console.log('Max dimensions:', props.maxWidth, props.maxHeight);
    console.log('Needs crop?', needsCrop(media));
    
    if (needsCrop(media)) {
        // Iniciar processo de crop
        imageToCrop.value = media;
        initializeCropArea(media);
        showCropModal.value = true;
    } else {
        // Selecionar diretamente
        emit('select', {
            url: media.url,
            alt: media.alt || media.filename,
            width: media.width,
            height: media.height,
            mediaId: media.id,
        });
        closeModal();
    }
};

/**
 * Inicializar área de crop
 */
const initializeCropArea = (media) => {
    const targetWidth = props.maxWidth || media.width;
    const targetHeight = props.maxHeight || media.height;
    
    // Centralizar o crop
    cropArea.value = {
        x: Math.max(0, (media.width - targetWidth) / 2),
        y: Math.max(0, (media.height - targetHeight) / 2),
        width: targetWidth,
        height: targetHeight,
    };
};

/**
 * Iniciar drag do crop
 */
const startDrag = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = true;
    
    // Guardar posição inicial do mouse e do crop
    dragStart.value = {
        mouseX: event.clientX,
        mouseY: event.clientY,
        cropX: cropArea.value.x,
        cropY: cropArea.value.y,
    };
};

/**
 * Mover crop
 */
const onDrag = (event) => {
    if (!isDragging.value || !imageToCrop.value || !cropImage.value) return;
    
    event.preventDefault();
    
    // Calcular quanto o mouse se moveu
    const deltaX = event.clientX - dragStart.value.mouseX;
    const deltaY = event.clientY - dragStart.value.mouseY;
    
    // Aplicar o movimento às coordenadas do crop (considerando a escala)
    let newX = dragStart.value.cropX + (deltaX * imageScale.value.x);
    let newY = dragStart.value.cropY + (deltaY * imageScale.value.y);
    
    // Limitar aos bounds da imagem
    newX = Math.max(0, Math.min(newX, imageToCrop.value.width - cropArea.value.width));
    newY = Math.max(0, Math.min(newY, imageToCrop.value.height - cropArea.value.height));
    
    cropArea.value.x = newX;
    cropArea.value.y = newY;
};

/**
 * Finalizar drag
 */
const stopDrag = (event) => {
    if (isDragging.value) {
        event?.preventDefault();
        isDragging.value = false;
    }
};

/**
 * Confirmar crop e criar variação
 */
const confirmCrop = async () => {
    if (!imageToCrop.value) return;
    
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.post(`/admin/media/${imageToCrop.value.id}/crop`, {
            x: Math.round(cropArea.value.x),
            y: Math.round(cropArea.value.y),
            width: Math.round(cropArea.value.width),
            height: Math.round(cropArea.value.height),
        });
        
        const croppedMedia = response.data.data;
        
        emit('select', {
            url: croppedMedia.url,
            alt: croppedMedia.alt || croppedMedia.filename,
            width: croppedMedia.width,
            height: croppedMedia.height,
            mediaId: croppedMedia.id,
        });
        
        closeModal();
    } catch (err) {
        error.value = 'Erro ao criar variação da imagem';
        console.error('Crop error:', err);
    } finally {
        loading.value = false;
    }
};

/**
 * Cancelar crop
 */
const cancelCrop = () => {
    showCropModal.value = false;
    imageToCrop.value = null;
};

/**
 * Fechar modal
 */
const closeModal = () => {
    selectedAlbum.value = null;
    albumMedia.value = [];
    showCropModal.value = false;
    imageToCrop.value = null;
    emit('close');
};

// Carregar álbuns ao montar
onMounted(() => {
    if (props.show) {
        loadAlbums();
    }
});

// Recarregar quando modal abrir
const handleShow = () => {
    if (props.show) {
        loadAlbums();
    }
};

// Watch para mudanças no prop show
import { watch } from 'vue';
watch(() => props.show, handleShow);

// Computed
const dimensionsText = computed(() => {
    if (!props.maxWidth && !props.maxHeight) return 'Qualquer tamanho';
    if (props.maxWidth && props.maxHeight) return `Máximo ${props.maxWidth}x${props.maxHeight}px`;
    if (props.maxWidth) return `Largura máxima ${props.maxWidth}px`;
    return `Altura máxima ${props.maxHeight}px`;
});

/**
 * Calcular estilo do crop overlay (convertendo coordenadas reais para exibição)
 */
const cropOverlayStyle = computed(() => {
    if (!imageToCrop.value || !cropImage.value) return {};
    
    // Converter coordenadas da imagem real para coordenadas de exibição
    const displayX = cropArea.value.x / imageScale.value.x;
    const displayY = cropArea.value.y / imageScale.value.y;
    const displayWidth = cropArea.value.width / imageScale.value.x;
    const displayHeight = cropArea.value.height / imageScale.value.y;
    
    return {
        left: `${displayX}px`,
        top: `${displayY}px`,
        width: `${displayWidth}px`,
        height: `${displayHeight}px`,
    };
});

// Adicionar event listeners globais para drag
onMounted(() => {
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
});

onUnmounted(() => {
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
});
</script>

<template>
    <!-- Modal Principal -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">
                    <!-- Header -->
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title">{{ title }}</h2>
                            <p class="modal-subtitle">{{ dimensionsText }}</p>
                        </div>
                        <button @click="closeModal" class="close-button">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="modal-body">
                        <!-- Loading -->
                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                            <p>Carregando...</p>
                        </div>

                        <!-- Error -->
                        <div v-else-if="error" class="error-state">
                            <p>{{ error }}</p>
                            <button @click="loadAlbums" class="retry-button">Tentar novamente</button>
                        </div>

                        <!-- Lista de Álbuns -->
                        <div v-else-if="!selectedAlbum" class="albums-grid">
                            <div
                                v-for="album in albums"
                                :key="album.id"
                                @click="selectAlbum(album)"
                                class="album-card"
                            >
                                <div class="album-cover">
                                    <img
                                        v-if="album.cover_url"
                                        :src="album.cover_url"
                                        :alt="album.name"
                                        class="album-cover-image"
                                    />
                                    <div v-else class="album-cover-placeholder">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="album-info">
                                    <h3 class="album-name">{{ album.name }}</h3>
                                    <p class="album-count">{{ album.media_count || 0 }} imagens</p>
                                </div>
                            </div>

                            <div v-if="albums.length === 0" class="empty-state">
                                <p>Nenhum álbum encontrado</p>
                            </div>
                        </div>

                        <!-- Mídias do Álbum -->
                        <div v-else class="media-view">
                            <button @click="backToAlbums" class="back-button">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Voltar para álbuns
                            </button>

                            <div class="media-grid">
                                <div
                                    v-for="media in albumMedia"
                                    :key="media.id"
                                    @click="selectImage(media)"
                                    class="media-card"
                                >
                                    <img :src="media.thumbnail_url || media.url" :alt="media.alt || media.filename" class="media-image" />
                                    <div v-if="needsCrop(media)" class="crop-badge">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" />
                                        </svg>
                                        Crop
                                    </div>
                                    <div class="media-dimensions">{{ media.width }}x{{ media.height }}</div>
                                </div>

                                <div v-if="albumMedia.length === 0" class="empty-state">
                                    <p>Nenhuma imagem neste álbum</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Modal de Crop -->
        <Transition name="modal">
            <div v-if="showCropModal && imageToCrop" class="modal-overlay" @click.self="cancelCrop">
                <div class="modal-container crop-modal">
                    <div class="modal-header">
                        <h2 class="modal-title">Ajustar Imagem</h2>
                        <button @click="cancelCrop" class="close-button">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p class="crop-instructions">
                            Posicione a área de seleção sobre a parte da imagem que deseja usar
                        </p>

                        <div class="crop-container">
                            <div class="crop-image-wrapper" ref="cropWrapper">
                                <img 
                                    ref="cropImage"
                                    :src="imageToCrop.url" 
                                    :alt="imageToCrop.alt" 
                                    class="crop-image"
                                    @load="onCropImageLoad"
                                />
                                <div
                                    class="crop-overlay"
                                    :style="cropOverlayStyle"
                                    @mousedown="startDrag"
                                >
                                    <div class="crop-border"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button @click="cancelCrop" class="button-secondary">Cancelar</button>
                        <button @click="confirmCrop" class="button-primary" :disabled="loading">
                            {{ loading ? 'Processando...' : 'Confirmar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.modal-container {
    background: white;
    border-radius: 0.5rem;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.crop-modal {
    max-width: 1200px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
}

.modal-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.close-button {
    padding: 0.5rem;
    color: #6b7280;
    transition: color 0.2s;
}

.close-button:hover {
    color: #111827;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.loading-state,
.error-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #6b7280;
}

.spinner {
    width: 3rem;
    height: 3rem;
    border: 3px solid #e5e7eb;
    border-top-color: #b8998a;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.albums-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.album-card {
    cursor: pointer;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
    transition: all 0.2s;
}

.album-card:hover {
    border-color: #b8998a;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.album-cover {
    aspect-ratio: 1;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.album-cover-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.album-cover-placeholder {
    color: #9ca3af;
}

.album-info {
    padding: 1rem;
}

.album-name {
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.25rem;
}

.album-count {
    font-size: 0.875rem;
    color: #6b7280;
}

.back-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    transition: color 0.2s;
}

.back-button:hover {
    color: #111827;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.media-card {
    position: relative;
    cursor: pointer;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
    aspect-ratio: 1;
    transition: all 0.2s;
}

.media-card:hover {
    border-color: #b8998a;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.media-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.crop-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: #b8998a;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.media-dimensions {
    position: absolute;
    bottom: 0.5rem;
    left: 0.5rem;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
}

.crop-instructions {
    text-align: center;
    color: #6b7280;
    margin-bottom: 1rem;
}

.crop-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.crop-image-wrapper {
    position: relative;
    display: inline-block;
    max-width: 100%;
}

.crop-image {
    display: block;
    max-width: 100%;
    max-height: 60vh;
}

.crop-overlay {
    position: absolute;
    cursor: move;
    border: 2px solid #b8998a;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
    user-select: none;
}

.crop-border {
    position: absolute;
    inset: 0;
    border: 2px dashed white;
}

.button-primary,
.button-secondary {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    transition: all 0.2s;
}

.button-primary {
    background: #b8998a;
    color: white;
}

.button-primary:hover:not(:disabled) {
    background: #a18072;
}

.button-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.button-secondary {
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.button-secondary:hover {
    background: #f9fafb;
}

.retry-button {
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background: #b8998a;
    color: white;
    border-radius: 0.375rem;
    font-weight: 500;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
