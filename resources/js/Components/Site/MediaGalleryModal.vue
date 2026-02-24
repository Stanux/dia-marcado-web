<script setup>
/**
 * MediaGalleryModal Component
 * 
 * Modal para selecionar imagens da galeria com suporte a crop
 * quando a imagem excede as dimensões máximas permitidas.
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
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
    mediaType: {
        type: String,
        default: 'image', // image | video | all
    },
    allowCrop: {
        type: Boolean,
        default: true,
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    allowedAlbumTypes: {
        type: Array,
        default: () => [],
    },
    defaultAlbumType: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'select']);
const ALBUM_TYPE_LABELS = {
    pre_casamento: 'Pré-Casamento',
    pos_casamento: 'Pós-Casamento',
    uso_site: 'Uso no Site',
};

// Estado
const albums = ref([]);
const selectedAlbum = ref(null);
const albumMedia = ref([]);
const selectedMediaMap = ref({});
const loading = ref(false);
const error = ref(null);
const uploadInput = ref(null);
const uploading = ref(false);
const uploadProgress = ref(0);
const uploadError = ref(null);
const uploadBatch = ref({
    total: 0,
    current: 0,
    success: 0,
    failed: 0,
    currentName: '',
});
const showCreateAlbumForm = ref(false);
const creatingAlbum = ref(false);
const createAlbumError = ref(null);
const newAlbumName = ref('');
const newAlbumType = ref('');
const videoHoverTimeouts = new Map();

// Crop state
const showCropModal = ref(false);
const imageToCrop = ref(null);
const cropArea = ref({ x: 0, y: 0, width: 0, height: 0 });
const outputScale = ref(100);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const cropImage = ref(null);
const cropWrapper = ref(null);
const imageScale = ref({ x: 1, y: 1 });
const baseImageDisplaySize = ref({ width: 0, height: 0 });
const currentImageDisplaySize = ref({ width: 0, height: 0 });

const isVideoSelection = computed(() => props.mediaType === 'video');
const isAllMediaSelection = computed(() => props.mediaType === 'all');
const isMultiSelect = computed(() => props.multiple === true);
const normalizedAllowedAlbumTypes = computed(() => {
    if (!Array.isArray(props.allowedAlbumTypes) || props.allowedAlbumTypes.length === 0) {
        return [];
    }

    return props.allowedAlbumTypes
        .map((type) => String(type).trim())
        .filter((type) => type.length > 0);
});
const hasAlbumTypeRestriction = computed(() => normalizedAllowedAlbumTypes.value.length > 0);
const allowedAlbumTypesSet = computed(() => new Set(normalizedAllowedAlbumTypes.value));
const filteredAlbums = computed(() => {
    if (!hasAlbumTypeRestriction.value) {
        return albums.value;
    }

    return albums.value.filter((album) => allowedAlbumTypesSet.value.has(album.type));
});
const albumTypeOptions = computed(() => {
    if (hasAlbumTypeRestriction.value) {
        return normalizedAllowedAlbumTypes.value;
    }

    return ['pre_casamento', 'pos_casamento', 'uso_site'];
});
const selectedMediaList = computed(() => Object.values(selectedMediaMap.value));
const selectedMediaCount = computed(() => selectedMediaList.value.length);

const filteredAlbumMedia = computed(() => {
    if (isAllMediaSelection.value) {
        return albumMedia.value;
    }

    if (isVideoSelection.value) {
        return albumMedia.value.filter((media) => media.type === 'video');
    }

    return albumMedia.value.filter((media) => media.type === 'image');
});

const uploadAccept = computed(() => {
    if (isVideoSelection.value) {
        return 'video/mp4,video/quicktime';
    }

    if (isAllMediaSelection.value) {
        return 'image/jpeg,image/jpg,image/png,image/gif,video/mp4,video/quicktime';
    }

    return 'image/jpeg,image/jpg,image/png,image/gif';
});

const emptyAlbumMessage = computed(() => {
    if (isVideoSelection.value) {
        return 'Nenhum vídeo neste álbum';
    }

    if (isAllMediaSelection.value) {
        return 'Nenhuma mídia neste álbum';
    }

    return 'Nenhuma imagem neste álbum';
});

const uploadButtonLabel = computed(() => {
    if (uploading.value) {
        if (uploadBatch.value.total > 1) {
            return `Enviando ${uploadBatch.value.current}/${uploadBatch.value.total}...`;
        }

        return 'Enviando...';
    }

    if (isAllMediaSelection.value) {
        return 'Enviar mídia';
    }

    if (isVideoSelection.value) {
        return 'Enviar vídeo';
    }

    return 'Enviar imagem';
});

const uploadHintText = computed(() => {
    if (isVideoSelection.value) {
        return 'Formatos aceitos: MP4 e QuickTime (até 100MB por arquivo). Você pode selecionar múltiplos arquivos.';
    }

    if (isAllMediaSelection.value) {
        return 'Formatos aceitos: JPEG, PNG, GIF, MP4 e QuickTime (até 100MB por arquivo). Você pode selecionar múltiplos arquivos.';
    }

    return 'Formatos aceitos: JPEG, JPG, PNG e GIF (até 100MB por arquivo). Você pode selecionar múltiplos arquivos.';
});

const uploadProgressText = computed(() => {
    if (!uploading.value) {
        return '';
    }

    if (uploadBatch.value.total > 1) {
        return `Upload ${uploadBatch.value.current}/${uploadBatch.value.total}: ${uploadProgress.value}%`;
    }

    return `Upload: ${uploadProgress.value}%`;
});

const albumItemLabel = computed(() => {
    if (isVideoSelection.value) {
        return 'vídeos';
    }

    if (isAllMediaSelection.value) {
        return 'itens';
    }

    return 'imagens';
});

const shouldShowAlbumImageCover = computed(() => {
    return !isVideoSelection.value && !isAllMediaSelection.value;
});

const getAlbumItemCount = (album) => {
    if (!album) {
        return 0;
    }

    if (isVideoSelection.value) {
        return Number(album.video_count ?? 0);
    }

    if (isAllMediaSelection.value) {
        return Number(album.media_count ?? 0);
    }

    return Number(album.image_count ?? 0);
};

const cropFrameDimensions = computed(() => {
    if (!imageToCrop.value) {
        return {
            width: Math.max(1, props.maxWidth || 1),
            height: Math.max(1, props.maxHeight || 1),
        };
    }

    return {
        width: Math.max(1, Math.min(props.maxWidth || imageToCrop.value.width, imageToCrop.value.width)),
        height: Math.max(1, Math.min(props.maxHeight || imageToCrop.value.height, imageToCrop.value.height)),
    };
});

const clamp = (value, min, max) => Math.max(min, Math.min(value, max));

const minOutputScale = computed(() => {
    if (!imageToCrop.value) {
        return 1;
    }

    const frame = cropFrameDimensions.value;
    const widthLimit = (frame.width / imageToCrop.value.width) * 100;
    const heightLimit = (frame.height / imageToCrop.value.height) * 100;

    return clamp(Math.ceil(Math.max(widthLimit, heightLimit)), 1, 100);
});

const applyImageDisplayScale = (scalePercent) => {
    if (!baseImageDisplaySize.value.width || !baseImageDisplaySize.value.height || !imageToCrop.value) {
        return;
    }

    const ratio = clamp(scalePercent, minOutputScale.value, 100) / 100;
    const scaledWidth = Math.max(1, Math.round(baseImageDisplaySize.value.width * ratio));
    const scaledHeight = Math.max(1, Math.round(baseImageDisplaySize.value.height * ratio));

    currentImageDisplaySize.value = {
        width: scaledWidth,
        height: scaledHeight,
    };

    imageScale.value = {
        x: imageToCrop.value.width / scaledWidth,
        y: imageToCrop.value.height / scaledHeight,
    };
};

const applyBackgroundScale = (scalePercent, preserveCenter = true) => {
    if (!imageToCrop.value) return;

    const normalizedScale = clamp(scalePercent, minOutputScale.value, 100);
    const ratio = normalizedScale / 100;

    const frameWidth = cropFrameDimensions.value.width;
    const frameHeight = cropFrameDimensions.value.height;

    const sourceWidth = Math.min(
        imageToCrop.value.width,
        Math.max(1, Math.round(frameWidth / ratio))
    );
    const sourceHeight = Math.min(
        imageToCrop.value.height,
        Math.max(1, Math.round(frameHeight / ratio))
    );

    const centerX = preserveCenter
        ? cropArea.value.x + (cropArea.value.width / 2)
        : imageToCrop.value.width / 2;
    const centerY = preserveCenter
        ? cropArea.value.y + (cropArea.value.height / 2)
        : imageToCrop.value.height / 2;

    const maxX = Math.max(0, imageToCrop.value.width - sourceWidth);
    const maxY = Math.max(0, imageToCrop.value.height - sourceHeight);

    const nextX = clamp(Math.round(centerX - (sourceWidth / 2)), 0, maxX);
    const nextY = clamp(Math.round(centerY - (sourceHeight / 2)), 0, maxY);

    cropArea.value = {
        x: nextX,
        y: nextY,
        width: sourceWidth,
        height: sourceHeight,
    };
};

const onCropImageLoad = () => {
    if (!cropImage.value || !imageToCrop.value) return;

    if (!baseImageDisplaySize.value.width || !baseImageDisplaySize.value.height) {
        baseImageDisplaySize.value = {
            width: cropImage.value.clientWidth || imageToCrop.value.width,
            height: cropImage.value.clientHeight || imageToCrop.value.height,
        };
    }

    applyImageDisplayScale(outputScale.value);
};

/**
 * Carregar álbuns
 */
const loadAlbums = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get('/admin/albums/list');
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
    if (!props.allowCrop || media.type !== 'image') return false;
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
 * Build standard selection payload for single and multiple modes.
 */
const resolveMediaOriginalUrl = (media) => {
    if (!media || typeof media !== 'object') {
        return '';
    }

    return media.original_url || media.originalUrl || media.url || '';
};

const resolveMediaDisplayUrl = (media) => {
    if (!media || typeof media !== 'object') {
        return '';
    }

    return media.display_url
        || media.displayUrl
        || media.variant_1x
        || media.variant1x
        || media.url
        || '';
};

const buildSelectionPayload = (media) => ({
    url: resolveMediaOriginalUrl(media),
    originalUrl: resolveMediaOriginalUrl(media),
    displayUrl: resolveMediaDisplayUrl(media),
    alt: media.alt || media.filename,
    width: media.width,
    height: media.height,
    mediaId: media.id,
    type: media.type || 'image',
    thumbnailUrl: media.thumbnail_url || media.thumbnailUrl || media.url,
    filename: media.filename || '',
    albumId: selectedAlbum.value?.id || null,
});

const isMediaSelected = (mediaId) => Boolean(selectedMediaMap.value[mediaId]);

const toggleMediaSelection = (media) => {
    const key = media?.id;
    if (!key) {
        return;
    }

    if (selectedMediaMap.value[key]) {
        const next = { ...selectedMediaMap.value };
        delete next[key];
        selectedMediaMap.value = next;
        return;
    }

    selectedMediaMap.value = {
        ...selectedMediaMap.value,
        [key]: buildSelectionPayload(media),
    };
};

const clearSelectedMedia = () => {
    selectedMediaMap.value = {};
};

const confirmMultipleSelection = () => {
    if (selectedMediaCount.value === 0) {
        return;
    }

    emit('select', selectedMediaList.value);
    closeModal();
};

/**
 * Selecionar imagem
 */
const selectImage = (media) => {
    if (isMultiSelect.value) {
        toggleMediaSelection(media);
        return;
    }
    
    if (needsCrop(media)) {
        // Iniciar processo de crop
        imageToCrop.value = media;
        initializeCropArea(media);
        showCropModal.value = true;
    } else {
        // Selecionar diretamente
        emit('select', buildSelectionPayload(media));
        closeModal();
    }
};

/**
 * Abrir seletor de arquivo para upload no álbum atual
 */
const openUploadInput = () => {
    if (uploading.value) return;
    uploadInput.value?.click();
};

/**
 * Validar arquivo para upload
 */
const isAllowedFile = (file) => {
    const allowedByType = {
        image: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
        video: ['video/mp4', 'video/quicktime'],
        all: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'],
    };

    const list = allowedByType[props.mediaType] || allowedByType.image;
    return list.includes(file.type);
};

const resetUploadState = () => {
    uploading.value = false;
    uploadProgress.value = 0;
    uploadError.value = null;
    uploadBatch.value = {
        total: 0,
        current: 0,
        success: 0,
        failed: 0,
        currentName: '',
    };
};

const applyUploadedMediaToAlbumState = (uploadedMedia) => {
    if (!selectedAlbum.value) {
        return;
    }

    selectedAlbum.value.media_count = (selectedAlbum.value.media_count || 0) + 1;
    if (uploadedMedia?.type === 'image') {
        selectedAlbum.value.image_count = (selectedAlbum.value.image_count || 0) + 1;
    }
    if (uploadedMedia?.type === 'video') {
        selectedAlbum.value.video_count = (selectedAlbum.value.video_count || 0) + 1;
    }

    albums.value = albums.value.map((album) => (
        album.id === selectedAlbum.value.id
            ? {
                ...album,
                media_count: (album.media_count || 0) + 1,
                image_count: uploadedMedia?.type === 'image'
                    ? (album.image_count || 0) + 1
                    : (album.image_count || 0),
                video_count: uploadedMedia?.type === 'video'
                    ? (album.video_count || 0) + 1
                    : (album.video_count || 0),
                cover_url: album.cover_url || (uploadedMedia?.type === 'image' ? (uploadedMedia.thumbnail_url || uploadedMedia.url) : null),
            }
            : album
    ));
};

/**
 * Upload de arquivo para o álbum selecionado
 */
const uploadToSelectedAlbum = async (file, options = {}) => {
    const { manageLoading = true, refreshMedia = true } = options;

    if (!selectedAlbum.value?.id || !file) {
        return {
            success: false,
            error: 'Álbum inválido para upload.',
        };
    }

    const maxFileSize = 100 * 1024 * 1024; // 100MB

    if (!isAllowedFile(file)) {
        return {
            success: false,
            error: 'Tipo de arquivo não suportado para esta seleção.',
        };
    }

    if (file.size > maxFileSize) {
        return {
            success: false,
            error: 'Arquivo muito grande. O tamanho máximo permitido é 100MB.',
        };
    }

    if (manageLoading) {
        uploading.value = true;
        uploadProgress.value = 0;
        uploadError.value = null;
        uploadBatch.value = {
            total: 1,
            current: 1,
            success: 0,
            failed: 0,
            currentName: file.name,
        };
    }

    try {
        const formData = new FormData();
        formData.append('album_id', selectedAlbum.value.id);
        formData.append('file', file);

        const response = await axios.post('/admin/media/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (!progressEvent.total) return;
                uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            },
        });

        const uploadedMedia = response.data?.media || response.data?.data || response.data;
        if (!uploadedMedia?.id) {
            throw new Error('Resposta inválida ao fazer upload.');
        }

        if (refreshMedia) {
            await loadAlbumMedia(selectedAlbum.value.id);
        }

        applyUploadedMediaToAlbumState(uploadedMedia);

        return {
            success: true,
            media: uploadedMedia,
        };
    } catch (err) {
        console.error('Upload error:', err);
        const errorMessage = err?.response?.data?.message || 'Erro ao fazer upload da mídia.';

        if (manageLoading) {
            uploadError.value = errorMessage;
        }

        return {
            success: false,
            error: errorMessage,
        };
    } finally {
        if (manageLoading) {
            uploading.value = false;
        }
    }
};

/**
 * Tratar arquivo selecionado no input oculto
 */
const handleUploadChange = async (event) => {
    const input = event.target;
    const files = Array.from(input?.files ?? []);

    if (files.length > 0) {
        uploading.value = true;
        uploadProgress.value = 0;
        uploadError.value = null;
        uploadBatch.value = {
            total: files.length,
            current: 0,
            success: 0,
            failed: 0,
            currentName: '',
        };

        const failures = [];

        try {
            for (let index = 0; index < files.length; index += 1) {
                const file = files[index];
                uploadBatch.value.current = index + 1;
                uploadBatch.value.currentName = file.name;
                uploadProgress.value = 0;

                const result = await uploadToSelectedAlbum(file, {
                    manageLoading: false,
                    refreshMedia: false,
                });

                if (result.success) {
                    uploadBatch.value.success += 1;
                } else {
                    uploadBatch.value.failed += 1;
                    failures.push(`${file.name}: ${result.error || 'erro no upload'}`);
                }
            }

            if (uploadBatch.value.success > 0 && selectedAlbum.value?.id) {
                await loadAlbumMedia(selectedAlbum.value.id);
            }

            if (uploadBatch.value.failed > 0) {
                const firstError = failures[0] || 'erro desconhecido';
                uploadError.value = uploadBatch.value.failed === 1
                    ? firstError
                    : `${uploadBatch.value.failed} arquivo(s) falharam. Primeiro erro: ${firstError}`;
            }
        } finally {
            uploading.value = false;
            uploadProgress.value = 0;
        }
    }

    if (input) {
        input.value = '';
    }
};

const getDefaultAlbumType = () => {
    if (props.defaultAlbumType && albumTypeOptions.value.includes(props.defaultAlbumType)) {
        return props.defaultAlbumType;
    }

    return albumTypeOptions.value[0] ?? '';
};

/**
 * Toggle create album form
 */
const toggleCreateAlbumForm = () => {
    showCreateAlbumForm.value = !showCreateAlbumForm.value;
    createAlbumError.value = null;

    if (showCreateAlbumForm.value) {
        newAlbumType.value = getDefaultAlbumType();
    } else {
        newAlbumName.value = '';
        newAlbumType.value = '';
    }
};

/**
 * Create album directly from modal
 */
const createAlbum = async () => {
    const name = (newAlbumName.value || '').trim();
    const type = (newAlbumType.value || '').trim();

    if (!name) {
        createAlbumError.value = 'Informe o nome do álbum.';
        return;
    }

    if (!type) {
        createAlbumError.value = 'Selecione o tipo do álbum.';
        return;
    }

    creatingAlbum.value = true;
    createAlbumError.value = null;

    try {
        const response = await axios.post('/admin/albums', {
            name,
            type,
        });

        const payload = response.data?.data ?? response.data;

        if (!payload?.id) {
            throw new Error('Resposta inválida ao criar álbum.');
        }

        const createdAlbum = {
            id: payload.id,
            name: payload.name,
            type: payload.type || 'uso_site',
            description: payload.description || null,
            media_count: payload.media_count || 0,
            image_count: payload.image_count || 0,
            video_count: payload.video_count || 0,
            cover_url: payload.cover_url || null,
            created_at: payload.created_at,
            updated_at: payload.updated_at,
        };

        albums.value = [createdAlbum, ...albums.value];
        showCreateAlbumForm.value = false;
        newAlbumName.value = '';

        await selectAlbum(createdAlbum);
    } catch (err) {
        console.error('Create album error:', err);
        createAlbumError.value = err?.response?.data?.message || 'Erro ao criar álbum.';
    } finally {
        creatingAlbum.value = false;
    }
};

/**
 * Inicializar área de crop
 */
const initializeCropArea = (media) => {
    const targetWidth = Math.max(1, Math.min(props.maxWidth || media.width, media.width));
    const targetHeight = Math.max(1, Math.min(props.maxHeight || media.height, media.height));
    outputScale.value = 100;
    baseImageDisplaySize.value = { width: 0, height: 0 };
    currentImageDisplaySize.value = { width: 0, height: 0 };
    imageScale.value = { x: 1, y: 1 };
    
    cropArea.value = {
        x: Math.max(0, Math.round((media.width - targetWidth) / 2)),
        y: Math.max(0, Math.round((media.height - targetHeight) / 2)),
        width: targetWidth,
        height: targetHeight,
    };

    applyBackgroundScale(100, false);
};

/**
 * Iniciar drag do crop
 */
const startDrag = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = true;

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
    if (!isDragging.value || !imageToCrop.value) return;

    event.preventDefault();

    const deltaX = event.clientX - dragStart.value.mouseX;
    const deltaY = event.clientY - dragStart.value.mouseY;

    let newX = dragStart.value.cropX + (deltaX * imageScale.value.x);
    let newY = dragStart.value.cropY + (deltaY * imageScale.value.y);

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
            output_width: cropFrameDimensions.value.width,
            output_height: cropFrameDimensions.value.height,
        });
        
        const croppedMedia = response.data.data;

        emit('select', buildSelectionPayload({
            ...croppedMedia,
            id: croppedMedia.id,
            type: 'image',
            thumbnail_url: croppedMedia.thumbnail_url ?? croppedMedia.url,
        }));
        
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
    outputScale.value = 100;
    baseImageDisplaySize.value = { width: 0, height: 0 };
    currentImageDisplaySize.value = { width: 0, height: 0 };
    imageScale.value = { x: 1, y: 1 };
};

/**
 * Fechar modal
 */
const closeModal = () => {
    selectedAlbum.value = null;
    albumMedia.value = [];
    clearSelectedMedia();
    showCropModal.value = false;
    imageToCrop.value = null;
    outputScale.value = 100;
    baseImageDisplaySize.value = { width: 0, height: 0 };
    currentImageDisplaySize.value = { width: 0, height: 0 };
    imageScale.value = { x: 1, y: 1 };
    resetUploadState();
    showCreateAlbumForm.value = false;
    creatingAlbum.value = false;
    createAlbumError.value = null;
    newAlbumName.value = '';
    newAlbumType.value = '';
    emit('close');
};

/**
 * Fallback para imagem original quando thumbnail quebrar
 */
const handleThumbnailError = (event, media) => {
    if (!media?.url) {
        return;
    }

    const img = event?.target;
    if (img && img.src !== media.url) {
        img.src = media.url;
    }
};

/**
 * Fallback da capa do álbum para placeholder quando URL estiver quebrada.
 */
const handleAlbumCoverError = (album) => {
    if (!album) {
        return;
    }

    album.cover_url = null;
};

/**
 * Limpar timer de hover de preview do vídeo.
 */
const clearVideoHoverTimeout = (mediaId) => {
    const timeoutId = videoHoverTimeouts.get(mediaId);
    if (timeoutId) {
        window.clearTimeout(timeoutId);
        videoHoverTimeouts.delete(mediaId);
    }
};

/**
 * Obter elemento de vídeo dentro do card.
 */
const getVideoElementFromCardEvent = (event) => {
    const card = event?.currentTarget;
    if (!card || typeof card.querySelector !== 'function') {
        return null;
    }

    return card.querySelector('video');
};

/**
 * Iniciar preview do vídeo com atraso de 1 segundo ao passar mouse.
 */
const onVideoHoverEnter = (mediaId, event) => {
    clearVideoHoverTimeout(mediaId);

    const video = getVideoElementFromCardEvent(event);
    if (!video) {
        return;
    }

    const timeoutId = window.setTimeout(() => {
        videoHoverTimeouts.delete(mediaId);

        try {
            video.currentTime = 0;
            const playPromise = video.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => {});
            }
        } catch (_error) {
            // ignore preview autoplay errors
        }
    }, 1000);

    videoHoverTimeouts.set(mediaId, timeoutId);
};

/**
 * Parar preview ao tirar o mouse: pausa e reinicia vídeo.
 */
const onVideoHoverLeave = (mediaId, event) => {
    clearVideoHoverTimeout(mediaId);

    const video = getVideoElementFromCardEvent(event);
    if (!video) {
        return;
    }

    try {
        video.pause();
        video.currentTime = 0;
    } catch (_error) {
        // ignore video reset errors
    }
};

/**
 * Nome amigável para exibição no card da mídia.
 */
const getMediaDisplayName = (media) => {
    return media?.filename || media?.alt || media?.original_name || 'Arquivo sem nome';
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
        clearSelectedMedia();
        selectedAlbum.value = null;
        albumMedia.value = [];
        resetUploadState();
        newAlbumType.value = getDefaultAlbumType();
        loadAlbums();
    }
};

// Watch para mudanças no prop show
watch(() => props.show, handleShow);
watch(outputScale, (value) => {
    const numericValue = Number(value);
    const normalized = Number.isFinite(numericValue)
        ? clamp(Math.round(numericValue), minOutputScale.value, 100)
        : 100;

    if (normalized !== numericValue) {
        outputScale.value = normalized;
        return;
    }

    applyImageDisplayScale(normalized);
    applyBackgroundScale(normalized, true);
});

watch(minOutputScale, (minScale) => {
    if (outputScale.value < minScale) {
        outputScale.value = minScale;
    }
});

// Computed
const dimensionsText = computed(() => {
    if (isMultiSelect.value && isAllMediaSelection.value) return 'Selecione uma ou mais mídias da galeria';
    if (isMultiSelect.value && isVideoSelection.value) return 'Selecione um ou mais vídeos da galeria';
    if (isMultiSelect.value) return 'Selecione uma ou mais imagens da galeria';
    if (isVideoSelection.value) return 'Selecione um vídeo da galeria';
    if (isAllMediaSelection.value) return 'Selecione uma mídia da galeria';
    if (!props.maxWidth && !props.maxHeight) return 'Qualquer tamanho';
    if (props.maxWidth && props.maxHeight) return `Máximo ${props.maxWidth}x${props.maxHeight}px`;
    if (props.maxWidth) return `Largura máxima ${props.maxWidth}px`;
    return `Altura máxima ${props.maxHeight}px`;
});

const cropWrapperStyle = computed(() => {
    if (!currentImageDisplaySize.value.width || !currentImageDisplaySize.value.height) {
        return {};
    }

    return {
        width: `${currentImageDisplaySize.value.width}px`,
        height: `${currentImageDisplaySize.value.height}px`,
    };
});

const cropImageStyle = computed(() => {
    if (!currentImageDisplaySize.value.width || !currentImageDisplaySize.value.height) {
        return {};
    }

    return {
        width: `${currentImageDisplaySize.value.width}px`,
        height: `${currentImageDisplaySize.value.height}px`,
    };
});

const cropOverlayStyle = computed(() => {
    if (!imageToCrop.value || !currentImageDisplaySize.value.width || !currentImageDisplaySize.value.height) {
        return {};
    }

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
    videoHoverTimeouts.forEach((timeoutId) => window.clearTimeout(timeoutId));
    videoHoverTimeouts.clear();
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
                            <div class="albums-create">
                                <button
                                    type="button"
                                    class="button-primary"
                                    :disabled="creatingAlbum"
                                    @click="toggleCreateAlbumForm"
                                >
                                    {{ showCreateAlbumForm ? 'Cancelar criação' : 'Criar álbum' }}
                                </button>

                                <div v-if="showCreateAlbumForm" class="create-album-form">
                                    <select
                                        v-model="newAlbumType"
                                        class="create-album-select"
                                        :disabled="creatingAlbum"
                                    >
                                        <option value="">Selecione o tipo do álbum</option>
                                        <option
                                            v-for="typeSlug in albumTypeOptions"
                                            :key="typeSlug"
                                            :value="typeSlug"
                                        >
                                            {{ ALBUM_TYPE_LABELS[typeSlug] || typeSlug }}
                                        </option>
                                    </select>
                                    <input
                                        v-model="newAlbumName"
                                        type="text"
                                        class="create-album-input"
                                        placeholder="Nome do álbum"
                                        maxlength="255"
                                        :disabled="creatingAlbum"
                                        @keydown.enter.prevent="createAlbum"
                                    />
                                    <button
                                        type="button"
                                        class="button-secondary"
                                        :disabled="creatingAlbum || !newAlbumType || !newAlbumName.trim()"
                                        @click="createAlbum"
                                    >
                                        {{ creatingAlbum ? 'Criando...' : 'Salvar álbum' }}
                                    </button>
                                </div>

                                <p v-if="createAlbumError" class="create-album-error">{{ createAlbumError }}</p>
                            </div>

                            <div
                                v-for="album in filteredAlbums"
                                :key="album.id"
                                @click="selectAlbum(album)"
                                class="album-card"
                            >
                                <div class="album-cover">
                                    <img
                                        v-if="shouldShowAlbumImageCover && album.cover_url"
                                        :src="album.cover_url"
                                        :alt="album.name"
                                        class="album-cover-image"
                                        @error="handleAlbumCoverError(album)"
                                    />
                                    <div v-else class="album-cover-placeholder">
                                        <svg
                                            v-if="isVideoSelection"
                                            class="w-12 h-12"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <svg
                                            v-else-if="isAllMediaSelection"
                                            class="w-12 h-12"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7a3 3 0 013-3h10a3 3 0 013 3v10a3 3 0 01-3 3H7a3 3 0 01-3-3V7z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l1.5 2 2-3 2.5 4" />
                                        </svg>
                                        <svg v-else class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="album-info">
                                    <h3 class="album-name">{{ album.name }}</h3>
                                    <p class="album-count">{{ getAlbumItemCount(album) }} {{ albumItemLabel }}</p>
                                </div>
                            </div>

                            <div v-if="filteredAlbums.length === 0" class="empty-state">
                                <p>Nenhum álbum encontrado</p>
                            </div>
                        </div>

                        <!-- Mídias do Álbum -->
                        <div v-else class="media-view">
                            <input
                                ref="uploadInput"
                                type="file"
                                class="hidden"
                                :accept="uploadAccept"
                                multiple
                                @change="handleUploadChange"
                            />

                            <div class="media-toolbar">
                                <button @click="backToAlbums" class="back-button">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    Voltar para álbuns
                                </button>

                                <button
                                    type="button"
                                    class="button-primary"
                                    :disabled="uploading"
                                    @click="openUploadInput"
                                >
                                    {{ uploadButtonLabel }}
                                </button>
                            </div>

                            <div class="media-toolbar-status">
                                <p class="upload-hint">{{ uploadHintText }}</p>
                                <p v-if="isMultiSelect" class="upload-hint">Selecionadas: {{ selectedMediaCount }}</p>
                                <p v-if="uploading" class="upload-progress">{{ uploadProgressText }}</p>
                                <p v-if="uploadError" class="upload-error">{{ uploadError }}</p>
                            </div>

                            <div class="media-grid">
                                <div
                                    v-for="media in filteredAlbumMedia"
                                    :key="media.id"
                                    @click="selectImage(media)"
                                    class="media-card"
                                    :class="{ 'media-card-selected': isMultiSelect && isMediaSelected(media.id) }"
                                    @mouseenter="media.type === 'video' && onVideoHoverEnter(media.id, $event)"
                                    @mouseleave="media.type === 'video' && onVideoHoverLeave(media.id, $event)"
                                >
                                    <template v-if="media.type === 'video'">
                                        <video
                                            :src="media.url"
                                            :poster="media.thumbnail_url || undefined"
                                            class="media-image"
                                            muted
                                            playsinline
                                            preload="metadata"
                                        ></video>
                                        <div class="video-badge">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Vídeo
                                        </div>
                                    </template>
                                    <img
                                        v-else
                                        :src="media.thumbnail_url || media.url"
                                        :alt="media.alt || media.filename"
                                        class="media-image"
                                        @error="(event) => handleThumbnailError(event, media)"
                                    />
                                    <div v-if="needsCrop(media)" class="crop-badge">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" />
                                        </svg>
                                        Crop
                                    </div>
                                    <div v-if="isMultiSelect && isMediaSelected(media.id)" class="selected-badge">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Selecionado
                                    </div>
                                    <div class="media-meta">
                                        <p class="media-filename" :title="getMediaDisplayName(media)">
                                            {{ getMediaDisplayName(media) }}
                                        </p>
                                        <span class="media-dimensions">{{ media.width || '--' }}x{{ media.height || '--' }}</span>
                                    </div>
                                </div>

                                <div v-if="filteredAlbumMedia.length === 0" class="empty-state">
                                    <p>{{ emptyAlbumMessage }}</p>
                                </div>
                            </div>

                            <div v-if="isMultiSelect" class="media-selection-footer">
                                <button type="button" class="button-secondary" @click="clearSelectedMedia">
                                    Limpar seleção
                                </button>
                                <button
                                    type="button"
                                    class="button-primary"
                                    :disabled="selectedMediaCount === 0"
                                    @click="confirmMultipleSelection"
                                >
                                    Adicionar selecionadas ({{ selectedMediaCount }})
                                </button>
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
                        <div class="crop-container">
                            <div class="crop-image-wrapper" ref="cropWrapper" :style="cropWrapperStyle">
                                <img
                                    ref="cropImage"
                                    :src="imageToCrop.url"
                                    :alt="imageToCrop.alt"
                                    class="crop-image"
                                    :style="cropImageStyle"
                                    @load="onCropImageLoad"
                                    draggable="false"
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
                        <div class="crop-footer-resize">
                            <label for="crop-output-scale" class="crop-footer-resize-label">Redimensionar imagem</label>
                            <input
                                id="crop-output-scale"
                                v-model.number="outputScale"
                                type="range"
                                :min="minOutputScale"
                                max="100"
                                step="1"
                                class="crop-resize-slider crop-footer-resize-slider"
                            />
                        </div>

                        <div class="crop-footer-actions">
                            <button @click="cancelCrop" class="button-secondary">Cancelar</button>
                            <button @click="confirmCrop" class="button-primary" :disabled="loading">
                                {{ loading ? 'Processando...' : 'Confirmar' }}
                            </button>
                        </div>
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
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
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

.albums-create {
    grid-column: 1 / -1;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fafafa;
}

.create-album-form {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.75rem;
    flex-wrap: wrap;
}

.create-album-select {
    flex: 0 1 240px;
    min-width: 210px;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background: white;
}

.create-album-input {
    flex: 1;
    min-width: 220px;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.create-album-error {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #dc2626;
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
    margin-bottom: 0;
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

.media-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.media-toolbar-status {
    margin-bottom: 1rem;
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

.media-card-selected {
    border-color: #b8998a;
    box-shadow: 0 0 0 2px rgba(184, 153, 138, 0.2);
}

.media-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-badge {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    background: rgba(17, 24, 39, 0.8);
    color: white;
    padding: 0.2rem 0.45rem;
    border-radius: 999px;
    font-size: 0.7rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
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

.selected-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(16, 185, 129, 0.9);
    color: white;
    padding: 0.2rem 0.45rem;
    border-radius: 999px;
    font-size: 0.7rem;
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
}

.media-meta {
    position: absolute;
    inset-inline: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.4rem 0.5rem;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.08) 0%, rgba(0, 0, 0, 0.72) 100%);
    color: white;
    pointer-events: none;
}

.media-filename {
    margin: 0;
    min-width: 0;
    flex: 1;
    font-size: 0.72rem;
    line-height: 1.1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-dimensions {
    font-size: 0.7rem;
    line-height: 1rem;
    opacity: 0.95;
    flex-shrink: 0;
}

.crop-instructions {
    text-align: center;
    color: #6b7280;
    margin-bottom: 1rem;
}

.crop-footer-resize {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 280px;
    flex: 1;
}

.crop-footer-resize-label {
    white-space: nowrap;
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
}

.crop-resize-slider {
    width: 100%;
    accent-color: #b8998a;
}

.crop-footer-resize-slider {
    max-width: 300px;
    min-width: 140px;
}

.crop-footer-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.crop-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 420px;
    overflow: auto;
}

.crop-image-wrapper {
    position: relative;
    display: inline-block;
    max-width: 100%;
    max-height: 60vh;
}

.crop-image {
    display: block;
    max-width: 100%;
    max-height: 60vh;
    -webkit-user-drag: none;
    user-select: none;
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

.upload-hint {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.upload-progress {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #8b6b5d;
}

.upload-error {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #dc2626;
}

.media-selection-footer {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.75rem;
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
