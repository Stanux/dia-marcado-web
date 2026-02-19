<script setup>
/**
 * HeroEditor Component
 * 
 * Editor for the Hero section of the wedding site.
 * Supports image/video/gallery media, titles, CTAs, and layout options.
 * 
 * @Requirements: 9.1, 9.2, 9.4, 9.5
 */
import { ref, watch, computed, useAttrs } from 'vue';
import { SECTION_IDS, SECTION_LABELS } from '@/Composables/useSiteEditor';
import TypographyControl from '@/Components/Site/TypographyControl.vue';
import MediaGalleryModal from '@/Components/Site/MediaGalleryModal.vue';
import { useColorField } from '@/Composables/useColorField';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    enabledSections: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['change']);
const attrs = useAttrs();
const { isEyeDropperSupported, normalizeHexColor, pickColorFromScreen } = useColorField();

/**
 * Get available sections for CTA target (only enabled sections, excluding header/footer)
 */
const availableCtaTargets = computed(() => {
    const targets = [];
    Object.keys(SECTION_IDS).forEach(key => {
        // Skip hero itself as a target
        if (key === 'hero') return;
        // Only include if section is enabled
        if (props.enabledSections[key]) {
            targets.push({
                value: `#${SECTION_IDS[key]}`,
                label: SECTION_LABELS[key],
            });
        }
    });
    return targets;
});

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

// Watch for external content changes
watch(() => props.content, (newContent) => {
    localContent.value = JSON.parse(JSON.stringify(newContent));
}, { deep: true });

/**
 * Emit changes to parent
 */
const emitChange = () => {
    emit('change', JSON.parse(JSON.stringify(localContent.value)));
};

/**
 * Update a field and emit change
 */
const updateField = (field, value) => {
    localContent.value[field] = value;
    emitChange();
};

/**
 * Update media field
 */
const updateMedia = (field, value) => {
    if (!localContent.value.media) {
        localContent.value.media = { type: 'image', url: '', alt: '', fallback: '', autoplay: true, loop: true };
    }
    localContent.value.media[field] = value;
    emitChange();
};

/**
 * Update gallery images
 */
const updateGalleryImages = (images) => {
    if (!localContent.value.media) {
        localContent.value.media = { type: 'gallery', images: [] };
    }
    localContent.value.media.images = images;
    emitChange();
};

/**
 * Add image to gallery
 */
const addImageToGallery = (imageData) => {
    if (!localContent.value.media) {
        localContent.value.media = { type: 'gallery', images: [] };
    }
    if (!localContent.value.media.images) {
        localContent.value.media.images = [];
    }
    localContent.value.media.images.push({
        url: imageData.url,
        alt: imageData.alt || '',
    });
    emitChange();
};

/**
 * Remove image from gallery
 */
const removeImageFromGallery = (index) => {
    if (localContent.value.media?.images) {
        localContent.value.media.images.splice(index, 1);
        emitChange();
    }
};

/**
 * Update CTA field
 */
const updateCta = (ctaType, field, value) => {
    if (!localContent.value[ctaType]) {
        localContent.value[ctaType] = { label: '', target: '' };
    }
    localContent.value[ctaType][field] = value;
    emitChange();
};

/**
 * Update style field
 */
const updateStyle = (field, value) => {
    if (!localContent.value.style) {
        localContent.value.style = {};
    }
    localContent.value.style[field] = value;
    emitChange();
};

/**
 * Update overlay field
 */
const updateOverlay = (field, value) => {
    if (!localContent.value.style) {
        localContent.value.style = {};
    }
    if (!localContent.value.style.overlay) {
        localContent.value.style.overlay = { color: '#000000', opacity: 0.3 };
    }
    localContent.value.style.overlay[field] = value;
    emitChange();
};

/**
 * Atualizar tipografia do título
 */
const updateTitleTypography = (field, value) => {
    if (!localContent.value.titleTypography) {
        localContent.value.titleTypography = {
            fontFamily: 'Playfair Display',
            fontColor: '#ffffff',
            fontSize: 56,
            fontWeight: 700,
            fontItalic: false,
            fontUnderline: false,
        };
    }
    
    localContent.value.titleTypography[field] = value;
    emitChange();
};

/**
 * Atualizar tipografia do subtítulo
 */
const updateSubtitleTypography = (field, value) => {
    if (!localContent.value.subtitleTypography) {
        localContent.value.subtitleTypography = {
            fontFamily: 'Montserrat',
            fontColor: '#ffffff',
            fontSize: 20,
            fontWeight: 400,
            fontItalic: false,
            fontUnderline: false,
        };
    }
    
    localContent.value.subtitleTypography[field] = value;
    emitChange();
};

// Computed properties
const media = computed(() => localContent.value.media || { type: 'image', url: '', alt: '', fallback: '', autoplay: true, loop: true });
const ctaPrimary = computed(() => localContent.value.ctaPrimary || { label: '', target: '' });
const ctaSecondary = computed(() => localContent.value.ctaSecondary || { label: '', target: '' });
const style = computed(() => localContent.value.style || {});
const overlay = computed(() => style.value.overlay || { color: '#000000', opacity: 0.3 });
const overlayColorHex = computed(() => normalizeHexColor(overlay.value.color, '#000000'));
const isVideo = computed(() => media.value.type === 'video');
const isGallery = computed(() => media.value.type === 'gallery');
const isImage = computed(() => media.value.type === 'image');
const titleTypography = computed(() => localContent.value.titleTypography || {
    fontFamily: 'Playfair Display',
    fontColor: '#ffffff',
    fontSize: 56,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
});
const subtitleTypography = computed(() => localContent.value.subtitleTypography || {
    fontFamily: 'Montserrat',
    fontColor: '#ffffff',
    fontSize: 20,
    fontWeight: 400,
    fontItalic: false,
    fontUnderline: false,
});

// Modal states
const showImageGallery = ref(false);
const showVideoGallery = ref(false);
const showBannerGallery = ref(false);
const videoSourceType = ref('gallery'); // 'gallery' or 'url'

/**
 * Open image gallery modal
 */
const openImageGallery = () => {
    showImageGallery.value = true;
};

/**
 * Open video gallery modal
 */
const openVideoGallery = () => {
    showVideoGallery.value = true;
};

/**
 * Open banner gallery modal (for adding images to gallery)
 */
const openBannerGallery = () => {
    showBannerGallery.value = true;
};

/**
 * Handle image selection from gallery
 */
const onImageSelected = (imageData) => {
    updateMedia('url', imageData.url);
    updateMedia('alt', imageData.alt || '');
    showImageGallery.value = false;
};

/**
 * Handle video selection from gallery
 */
const onVideoSelected = (videoData) => {
    updateMedia('url', videoData.url);
    showVideoGallery.value = false;
};

/**
 * Handle banner image selection
 */
const onBannerImageSelected = (imageData) => {
    addImageToGallery(imageData);
    showBannerGallery.value = false;
};

const pickOverlayColorFromScreen = () => {
    pickColorFromScreen((hex) => updateOverlay('color', hex));
};
</script>

<template>
    <div v-bind="attrs" class="space-y-6 h-full overflow-y-auto">
        <!-- Media Section -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Mídia</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Mídia</label>
                <div class="media-type-tabs" role="tablist" aria-label="Tipo de Mídia">
                    <button
                        type="button"
                        @click="updateMedia('type', 'image')"
                        class="media-type-tab"
                        :class="{ 'media-type-tab-active': media.type === 'image' }"
                        :aria-selected="media.type === 'image'"
                        role="tab"
                    >
                        Imagem Única
                    </button>
                    <button
                        type="button"
                        @click="updateMedia('type', 'gallery')"
                        class="media-type-tab"
                        :class="{ 'media-type-tab-active': media.type === 'gallery' }"
                        :aria-selected="media.type === 'gallery'"
                        role="tab"
                    >
                        Banner Rotativo
                    </button>
                    <button
                        type="button"
                        @click="updateMedia('type', 'video')"
                        class="media-type-tab"
                        :class="{ 'media-type-tab-active': media.type === 'video' }"
                        :aria-selected="media.type === 'video'"
                        role="tab"
                    >
                        Vídeo
                    </button>
                </div>
            </div>

            <!-- Image Selection -->
            <template v-if="isImage">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Imagem de Fundo</label>
                    <button
                        @click="openImageGallery"
                        class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-md hover:border-wedding-500 transition-colors flex items-center justify-center gap-2 text-gray-600 hover:text-wedding-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Selecionar da Galeria (máx. 1920×1080px)
                    </button>
                    <p class="mt-1 text-xs text-gray-500">Dimensões recomendadas: 1920×1080px. Imagens maiores serão redimensionadas com crop.</p>
                </div>

                <!-- Preview da imagem -->
                <div v-if="media.url" class="p-3 bg-gray-50 rounded-md">
                    <div class="flex items-start gap-3">
                        <img :src="media.url" alt="Preview" class="w-32 h-20 object-cover border border-gray-200 rounded" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">Imagem selecionada</p>
                        </div>
                        <button
                            @click="updateMedia('url', '')"
                            class="p-1 text-red-400 hover:text-red-600"
                            title="Remover"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Texto Alternativo (alt)</label>
                    <input
                        type="text"
                        :value="media.alt || ''"
                        @input="updateMedia('alt', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        placeholder="Ex: Foto dos noivos sorrindo em frente ao altar"
                    />
                    <p class="mt-1 text-xs text-gray-500">Esse texto melhora acessibilidade e SEO para a imagem de fundo.</p>
                </div>
            </template>

            <!-- Gallery Selection -->
            <template v-if="isGallery">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Imagens do Banner Rotativo</label>
                    <button
                        @click="openBannerGallery"
                        class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-md hover:border-wedding-500 transition-colors flex items-center justify-center gap-2 text-gray-600 hover:text-wedding-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Adicionar Imagem (máx. 1920×1080px)
                    </button>
                    <p class="mt-1 text-xs text-gray-500">Adicione múltiplas imagens para criar um banner rotativo</p>
                </div>

                <!-- Gallery Images List -->
                <div v-if="media.images && media.images.length > 0" class="space-y-2">
                    <div
                        v-for="(image, index) in media.images"
                        :key="index"
                        class="p-3 bg-gray-50 rounded-md flex items-center gap-3"
                    >
                        <img :src="image.url" :alt="image.alt" class="w-24 h-16 object-cover border border-gray-200 rounded" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">Imagem {{ index + 1 }}</p>
                        </div>
                        <button
                            @click="removeImageFromGallery(index)"
                            class="p-1 text-red-400 hover:text-red-600"
                            title="Remover"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div v-else class="p-4 bg-gray-50 rounded-md text-center text-sm text-gray-500">
                    Nenhuma imagem adicionada ao banner rotativo
                </div>
            </template>

            <!-- Video Selection -->
            <template v-if="isVideo">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fonte do Vídeo</label>
                    <div class="flex gap-3 mb-3">
                        <button
                            @click="videoSourceType = 'gallery'"
                            :class="[
                                'flex-1 px-4 py-2 border-2 rounded-md font-medium transition-all',
                                videoSourceType === 'gallery'
                                    ? 'border-wedding-500 bg-wedding-50 text-wedding-700'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
                            ]"
                        >
                            Da Galeria
                        </button>
                        <button
                            @click="videoSourceType = 'url'"
                            :class="[
                                'flex-1 px-4 py-2 border-2 rounded-md font-medium transition-all',
                                videoSourceType === 'url'
                                    ? 'border-wedding-500 bg-wedding-50 text-wedding-700'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
                            ]"
                        >
                            URL Externa
                        </button>
                    </div>
                </div>

                <!-- Video from Gallery -->
                <div v-if="videoSourceType === 'gallery'">
                    <button
                        @click="openVideoGallery"
                        class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-md hover:border-wedding-500 transition-colors flex items-center justify-center gap-2 text-gray-600 hover:text-wedding-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Selecionar Vídeo da Galeria
                    </button>
                    <p class="mt-1 text-xs text-gray-500">Escolha um vídeo já enviado para sua galeria</p>
                </div>

                <!-- Video from URL -->
                <div v-else>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL do Vídeo</label>
                    <input
                        type="text"
                        :value="media.url"
                        @input="updateMedia('url', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        placeholder="https://youtube.com/... ou https://vimeo.com/..."
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Suporta YouTube, Vimeo ou URL direta de vídeo (.mp4, .webm, .ogg)
                    </p>
                </div>

                <!-- Video Preview -->
                <div v-if="media.url" class="p-3 bg-gray-50 rounded-md">
                    <div class="flex items-start gap-3">
                        <div class="w-32 h-20 bg-gray-200 border border-gray-300 rounded flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">Vídeo selecionado</p>
                            <p class="text-xs text-gray-500 truncate">{{ media.url }}</p>
                        </div>
                        <button
                            @click="updateMedia('url', '')"
                            class="p-1 text-red-400 hover:text-red-600"
                            title="Remover"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            :checked="media.autoplay"
                            @change="updateMedia('autoplay', $event.target.checked)"
                            class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 text-sm text-gray-700">Autoplay (desktop)</label>
                    </div>
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            :checked="media.loop"
                            @change="updateMedia('loop', $event.target.checked)"
                            class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 text-sm text-gray-700">Loop</label>
                    </div>
                </div>
            </template>
        </div>

        <!-- Text Content -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Textos</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título Principal</label>
                <input
                    type="text"
                    :value="localContent.title"
                    @input="updateField('title', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Ex: Vamos nos casar!"
                />
            </div>

            <!-- Tipografia do Título -->
            <TypographyControl
                :font-family="titleTypography.fontFamily"
                :font-color="titleTypography.fontColor"
                :font-size="titleTypography.fontSize"
                :font-weight="titleTypography.fontWeight"
                :font-italic="titleTypography.fontItalic"
                :font-underline="titleTypography.fontUnderline"
                @update:font-family="updateTitleTypography('fontFamily', $event)"
                @update:font-color="updateTitleTypography('fontColor', $event)"
                @update:font-size="updateTitleTypography('fontSize', $event)"
                @update:font-weight="updateTitleTypography('fontWeight', $event)"
                @update:font-italic="updateTitleTypography('fontItalic', $event)"
                @update:font-underline="updateTitleTypography('fontUnderline', $event)"
                label="Tipografia do Título"
            />

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subtítulo</label>
                <textarea
                    :value="localContent.subtitle"
                    @input="updateField('subtitle', $event.target.value)"
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Ex: Junte-se a nós neste dia especial"
                ></textarea>
            </div>

            <!-- Tipografia do Subtítulo -->
            <TypographyControl
                :font-family="subtitleTypography.fontFamily"
                :font-color="subtitleTypography.fontColor"
                :font-size="subtitleTypography.fontSize"
                :font-weight="subtitleTypography.fontWeight"
                :font-italic="subtitleTypography.fontItalic"
                :font-underline="subtitleTypography.fontUnderline"
                @update:font-family="updateSubtitleTypography('fontFamily', $event)"
                @update:font-color="updateSubtitleTypography('fontColor', $event)"
                @update:font-size="updateSubtitleTypography('fontSize', $event)"
                @update:font-weight="updateSubtitleTypography('fontWeight', $event)"
                @update:font-italic="updateSubtitleTypography('fontItalic', $event)"
                @update:font-underline="updateSubtitleTypography('fontUnderline', $event)"
                label="Tipografia do Subtítulo"
            />
        </div>

        <!-- CTAs -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Botões de Ação (CTA)</h3>
            
            <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                <span class="text-sm font-medium text-gray-700">CTA Primário</span>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Rótulo</label>
                        <input
                            type="text"
                            :value="ctaPrimary.label"
                            @input="updateCta('ctaPrimary', 'label', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="Ex: Confirmar Presença"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Destino</label>
                        <select
                            :value="ctaPrimary.target"
                            @change="updateCta('ctaPrimary', 'target', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        >
                            <option value="">Selecione uma seção</option>
                            <option 
                                v-for="target in availableCtaTargets" 
                                :key="target.value" 
                                :value="target.value"
                            >
                                {{ target.label }}
                            </option>
                        </select>
                        <p v-if="availableCtaTargets.length === 0" class="mt-1 text-xs text-amber-600">
                            Ative outras seções para vincular
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                <span class="text-sm font-medium text-gray-700">CTA Secundário</span>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Rótulo</label>
                        <input
                            type="text"
                            :value="ctaSecondary.label"
                            @input="updateCta('ctaSecondary', 'label', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="Ex: Ver Fotos"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Destino</label>
                        <select
                            :value="ctaSecondary.target"
                            @change="updateCta('ctaSecondary', 'target', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        >
                            <option value="">Selecione uma seção</option>
                            <option 
                                v-for="target in availableCtaTargets" 
                                :key="target.value" 
                                :value="target.value"
                            >
                                {{ target.label }}
                            </option>
                        </select>
                        <p v-if="availableCtaTargets.length === 0" class="mt-1 text-xs text-amber-600">
                            Ative outras seções para vincular
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layout -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Layout</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Layout</label>
                <select
                    :value="localContent.layout"
                    @change="updateField('layout', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="full-bleed">Ocupar a largura total da tela.</option>
                    <option value="boxed">Ocupar o centro da tela com margens laterais e cantos arredondados.</option>
                </select>
            </div>
        </div>

        <!-- Style Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>
            
            <!-- Overlay -->
            <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                <span class="text-sm font-medium text-gray-700">Overlay</span>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cor</label>
                        <div class="flex items-center space-x-2">
                            <input
                                type="color"
                                :value="overlayColorHex"
                                @input="updateOverlay('color', $event.target.value)"
                                @change="updateOverlay('color', $event.target.value)"
                                class="h-8 w-12 border border-gray-300 rounded cursor-pointer"
                            />
                            <button
                                v-if="isEyeDropperSupported"
                                type="button"
                                @click="pickOverlayColorFromScreen"
                                class="h-8 w-8 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                                title="Capturar cor da tela"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                                </svg>
                            </button>
                            <input
                                type="text"
                                :value="overlay.color"
                                @input="updateOverlay('color', $event.target.value)"
                                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded-md"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Opacidade</label>
                        <div class="flex items-center space-x-2">
                            <input
                                type="range"
                                min="0"
                                max="1"
                                step="0.1"
                                :value="overlay.opacity"
                                @input="updateOverlay('opacity', parseFloat($event.target.value))"
                                class="flex-1"
                            />
                            <span class="text-sm text-gray-600 w-10">{{ (overlay.opacity * 100).toFixed(0) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alinhamento do Texto</label>
                <select
                    :value="style.textAlign"
                    @change="updateStyle('textAlign', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="left">Esquerda</option>
                    <option value="center">Centro</option>
                    <option value="right">Direita</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Animação</label>
                    <select
                        :value="style.animation"
                        @change="updateStyle('animation', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="none">Nenhuma</option>
                        <option value="fade">Fade</option>
                        <option value="slide">Slide</option>
                        <option value="zoom">Zoom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duração (ms)</label>
                    <input
                        type="number"
                        :value="style.animationDuration"
                        @input="updateStyle('animationDuration', parseInt($event.target.value))"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        placeholder="500"
                        min="0"
                        step="100"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Media Gallery Modals -->
    <MediaGalleryModal
        :show="showImageGallery"
        :max-width="1920"
        :max-height="1080"
        :allow-crop="true"
        title="Selecionar Imagem de Fundo"
        @close="showImageGallery = false"
        @select="onImageSelected"
    />

    <MediaGalleryModal
        :show="showBannerGallery"
        :max-width="1920"
        :max-height="1080"
        :allow-crop="true"
        title="Adicionar Imagem ao Banner"
        @close="showBannerGallery = false"
        @select="onBannerImageSelected"
    />

    <MediaGalleryModal
        :show="showVideoGallery"
        :max-width="1920"
        :max-height="1080"
        :media-type="'video'"
        title="Selecionar Vídeo"
        @close="showVideoGallery = false"
        @select="onVideoSelected"
    />
</template>

<style scoped>
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #b8998a;
}
.focus\:border-wedding-500:focus {
    border-color: #b8998a;
}
.text-wedding-600 {
    color: #a18072;
}
.text-wedding-700 {
    color: #8b6b5d;
}
.border-wedding-500 {
    border-color: #b8998a;
}
.bg-wedding-50 {
    background-color: #f5f1ee;
}
.hover\:border-wedding-500:hover {
    border-color: #b8998a;
}
.hover\:text-wedding-600:hover {
    color: #a18072;
}

.media-type-tabs {
    @apply grid w-full grid-cols-3 items-end gap-2 border-b border-gray-300;
}

.media-type-tab {
    @apply px-4 py-2 text-sm font-semibold text-gray-500 transition-all duration-150;
    border: 1px solid transparent;
    border-bottom: none;
    border-radius: 0.65rem 0.65rem 0 0;
    margin-bottom: -1px;
    background: transparent;
}

.media-type-tab:hover {
    @apply text-gray-700;
    border-color: #e5e7eb;
    background: #f9fafb;
}

.media-type-tab-active {
    @apply text-wedding-700;
    background: #ffffff;
    border-color: #b8998a;
    box-shadow: inset 0 2px 0 #b8998a;
}
</style>
