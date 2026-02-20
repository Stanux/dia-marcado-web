<script setup>
/**
 * PhotoGalleryEditor Component
 *
 * Editor para a seção Galeria de Fotos com curadoria manual.
 * Suporta seleção de mídia mista (foto + vídeo), ordem manual e
 * configuração de layout/estilo da seção.
 */
import { computed, ref, watch } from 'vue';
import MediaGalleryModal from '@/Components/Site/MediaGalleryModal.vue';
import { useColorField } from '@/Composables/useColorField';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);
const { isEyeDropperSupported, normalizeHexColor, pickColorFromScreen } = useColorField();

const getDefaultAlbums = () => ({
    before: {
        title: 'Nossa História',
        items: [],
        photos: [],
    },
    after: {
        title: 'O Grande Dia',
        items: [],
        photos: [],
    },
});

const localContent = ref(JSON.parse(JSON.stringify(props.content || {})));
const activeAlbum = ref('before');
const showMediaGallery = ref(false);

watch(
    () => props.content,
    (newContent) => {
        localContent.value = JSON.parse(JSON.stringify(newContent || {}));
        ensureStructure();
    },
);

const normalizeGalleryItem = (item, index = 0) => {
    if (!item) {
        return null;
    }

    if (typeof item === 'string') {
        return {
            mediaId: null,
            type: 'image',
            url: item,
            thumbnailUrl: item,
            alt: '',
            title: '',
            caption: '',
            isPrivate: false,
            sortOrder: index,
        };
    }

    if (typeof item !== 'object') {
        return null;
    }

    const type = item.type === 'video' ? 'video' : 'image';

    return {
        mediaId: item.mediaId ?? item.media_id ?? item.id ?? null,
        type,
        url: item.url ?? '',
        thumbnailUrl: item.thumbnailUrl ?? item.thumbnail_url ?? item.url ?? '',
        alt: item.alt ?? '',
        title: item.title ?? '',
        caption: item.caption ?? '',
        filename: item.filename ?? '',
        width: item.width ?? null,
        height: item.height ?? null,
        albumId: item.albumId ?? item.album_id ?? null,
        isPrivate: item.isPrivate ?? false,
        sortOrder: Number.isFinite(item.sortOrder) ? item.sortOrder : index,
    };
};

const ensureStructure = () => {
    if (!localContent.value.albums) {
        localContent.value.albums = getDefaultAlbums();
    }

    if (!localContent.value.albums.before) {
        localContent.value.albums.before = getDefaultAlbums().before;
    }

    if (!localContent.value.albums.after) {
        localContent.value.albums.after = getDefaultAlbums().after;
    }

    for (const key of ['before', 'after']) {
        const album = localContent.value.albums[key];

        if (!Array.isArray(album.items)) {
            const legacyPhotos = Array.isArray(album.photos) ? album.photos : [];
            album.items = legacyPhotos
                .map((photo, index) => normalizeGalleryItem(photo, index))
                .filter(Boolean);
        }

        album.items = album.items
            .map((item, index) => normalizeGalleryItem(item, index))
            .filter(Boolean)
            .map((item, index) => ({
                ...item,
                sortOrder: index,
            }));

        if (!Array.isArray(album.photos)) {
            album.photos = [];
        }
    }

    if (!localContent.value.pagination || typeof localContent.value.pagination !== 'object') {
        localContent.value.pagination = { perPage: 20 };
    }

    if (!Number.isFinite(localContent.value.pagination.perPage) || localContent.value.pagination.perPage <= 0) {
        localContent.value.pagination.perPage = 20;
    }

    if (!localContent.value.video || typeof localContent.value.video !== 'object') {
        localContent.value.video = {
            hoverPreview: true,
            hoverDelayMs: 1000,
        };
    }
};

ensureStructure();

const syncLegacyPhotosFromItems = () => {
    for (const key of ['before', 'after']) {
        const album = localContent.value.albums[key];
        album.photos = (album.items || [])
            .filter((item) => item.type === 'image')
            .map((item) => ({
                url: item.url,
                title: item.title ?? '',
                caption: item.caption ?? '',
                alt: item.alt ?? '',
                isPrivate: item.isPrivate ?? false,
            }));
    }
};

const emitChange = () => {
    syncLegacyPhotosFromItems();
    emit('change', JSON.parse(JSON.stringify(localContent.value)));
};

const updateField = (field, value) => {
    localContent.value[field] = value;
    emitChange();
};

const updateStyle = (field, value) => {
    if (!localContent.value.style) {
        localContent.value.style = {};
    }

    localContent.value.style[field] = value;
    emitChange();
};

const updateVideoConfig = (field, value) => {
    localContent.value.video[field] = value;
    emitChange();
};

const updateAlbumTitle = (albumKey, title) => {
    localContent.value.albums[albumKey].title = title;
    emitChange();
};

const getAlbumItems = (albumKey) => {
    return localContent.value.albums?.[albumKey]?.items || [];
};

const updateMediaItemField = (albumKey, index, field, value) => {
    const items = getAlbumItems(albumKey);
    if (!items[index]) {
        return;
    }

    items[index][field] = value;
    emitChange();
};

const removeMediaItem = (albumKey, index) => {
    const items = getAlbumItems(albumKey);
    if (!items[index]) {
        return;
    }

    items.splice(index, 1);
    emitChange();
};

const moveMediaItemUp = (albumKey, index) => {
    const items = getAlbumItems(albumKey);
    if (index <= 0 || !items[index]) {
        return;
    }

    const [item] = items.splice(index, 1);
    items.splice(index - 1, 0, item);
    emitChange();
};

const moveMediaItemDown = (albumKey, index) => {
    const items = getAlbumItems(albumKey);
    if (index >= items.length - 1 || !items[index]) {
        return;
    }

    const [item] = items.splice(index, 1);
    items.splice(index + 1, 0, item);
    emitChange();
};

const showAlbumSelector = (albumKey) => {
    activeAlbum.value = albumKey;
    showMediaGallery.value = true;
};

const onMediaSelected = (payload) => {
    const selectedItems = Array.isArray(payload) ? payload : [payload];
    const existingItems = getAlbumItems(activeAlbum.value);
    const existingKeys = new Set(
        existingItems.map((item) => `id:${item.mediaId ?? 'none'}|url:${item.url ?? ''}`),
    );

    const normalized = selectedItems
        .map((item, index) => normalizeGalleryItem(item, existingItems.length + index))
        .filter(Boolean)
        .filter((item) => {
            const dedupeKey = `id:${item.mediaId ?? 'none'}|url:${item.url ?? ''}`;
            if (existingKeys.has(dedupeKey)) {
                return false;
            }

            existingKeys.add(dedupeKey);
            return true;
        });

    if (normalized.length === 0) {
        showMediaGallery.value = false;
        return;
    }

    localContent.value.albums[activeAlbum.value].items = [
        ...existingItems,
        ...normalized,
    ].map((item, index) => ({
        ...item,
        sortOrder: index,
    }));

    showMediaGallery.value = false;
    emitChange();
};

const style = computed(() => localContent.value.style || {});
const pagination = computed(() => localContent.value.pagination || { perPage: 20 });
const videoConfig = computed(() => localContent.value.video || { hoverPreview: true, hoverDelayMs: 1000 });
const albums = computed(() => localContent.value.albums || getDefaultAlbums());
const currentAlbumItems = computed(() => getAlbumItems(activeAlbum.value));
const albumTypeFilter = computed(() => (activeAlbum.value === 'before' ? 'pre_casamento' : 'pos_casamento'));
const selectorTitle = computed(() => (
    activeAlbum.value === 'before'
        ? 'Selecionar Mídias - Álbum Antes'
        : 'Selecionar Mídias - Álbum Depois'
));
const photoGalleryBackgroundColorHex = computed(() => normalizeHexColor(style.value.backgroundColor, '#ffffff'));

const pickPhotoGalleryBackgroundColor = () => {
    pickColorFromScreen((hex) => updateStyle('backgroundColor', hex));
};
</script>

<template>
    <div class="space-y-6 h-full overflow-y-auto">
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Layout</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Layout</label>
                <select
                    :value="localContent.layout"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    @change="updateField('layout', $event.target.value)"
                >
                    <option value="masonry">Masonry</option>
                    <option value="grid">Grid</option>
                    <option value="slideshow">Slideshow</option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-6">
                <label class="inline-flex items-center">
                    <input
                        type="checkbox"
                        :checked="localContent.showLightbox"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        @change="updateField('showLightbox', $event.target.checked)"
                    />
                    <span class="ml-2 text-sm text-gray-700">Habilitar lightbox</span>
                </label>

                <label class="inline-flex items-center">
                    <input
                        type="checkbox"
                        :checked="localContent.allowDownload"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        @change="updateField('allowDownload', $event.target.checked)"
                    />
                    <span class="ml-2 text-sm text-gray-700">Permitir download</span>
                </label>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Comportamento da Galeria</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Carregamento Infinito</label>
                <p class="text-xs text-gray-500 mb-2">A galeria carrega progressivamente para manter o site rápido.</p>
                <div class="flex items-center gap-3">
                    <input
                        type="number"
                        min="20"
                        max="20"
                        step="1"
                        class="w-24 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-700"
                        :value="pagination.perPage || 20"
                        readonly
                    />
                    <span class="text-sm text-gray-600">itens por carregamento</span>
                </div>
            </div>

            <div class="space-y-3">
                <label class="inline-flex items-center">
                    <input
                        type="checkbox"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="videoConfig.hoverPreview !== false"
                        @change="updateVideoConfig('hoverPreview', $event.target.checked)"
                    />
                    <span class="ml-2 text-sm text-gray-700">Vídeo reproduz no hover</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-[220px_1fr] items-center gap-3">
                    <label class="text-sm text-gray-700">Atraso do preview (ms)</label>
                    <input
                        type="number"
                        min="0"
                        max="5000"
                        step="100"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                        :value="videoConfig.hoverDelayMs ?? 1000"
                        @input="updateVideoConfig('hoverDelayMs', Math.max(0, Number.parseInt($event.target.value, 10) || 0))"
                    />
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Álbuns</h3>

            <div class="album-tabs" role="tablist" aria-label="Seleção de álbum">
                <button
                    type="button"
                    role="tab"
                    class="album-tab"
                    :class="{ 'album-tab-active': activeAlbum === 'before' }"
                    :aria-selected="activeAlbum === 'before'"
                    @click="activeAlbum = 'before'"
                >
                    Álbum "Antes"
                </button>
                <button
                    type="button"
                    role="tab"
                    class="album-tab"
                    :class="{ 'album-tab-active': activeAlbum === 'after' }"
                    :aria-selected="activeAlbum === 'after'"
                    @click="activeAlbum = 'after'"
                >
                    Álbum "Depois"
                </button>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título do Álbum</label>
                <input
                    type="text"
                    :value="albums[activeAlbum]?.title"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    :placeholder="activeAlbum === 'before' ? 'Nossa História' : 'O Grande Dia'"
                    @input="updateAlbumTitle(activeAlbum, $event.target.value)"
                />
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">
                    Itens selecionados ({{ currentAlbumItems.length }})
                </span>
                <button
                    type="button"
                    class="text-sm text-wedding-600 hover:text-wedding-700 font-medium"
                    @click="showAlbumSelector(activeAlbum)"
                >
                    + Selecionar fotos e vídeos
                </button>
            </div>

            <div v-if="currentAlbumItems.length === 0" class="p-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                Nenhum item selecionado para este álbum.
            </div>

            <div v-else class="space-y-3 max-h-96 overflow-y-auto pr-1">
                <div
                    v-for="(item, index) in currentAlbumItems"
                    :key="`${item.mediaId || item.url}-${index}`"
                    class="p-4 bg-gray-50 rounded-lg space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">
                            {{ item.type === 'video' ? 'Vídeo' : 'Imagem' }} {{ index + 1 }}
                        </span>
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                :disabled="index === 0"
                                title="Mover para cima"
                                @click="moveMediaItemUp(activeAlbum, index)"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                :disabled="index === currentAlbumItems.length - 1"
                                title="Mover para baixo"
                                @click="moveMediaItemDown(activeAlbum, index)"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="p-1 text-red-400 hover:text-red-600"
                                title="Remover"
                                @click="removeMediaItem(activeAlbum, index)"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[90px_1fr] gap-3 items-start">
                        <div class="w-[90px] h-[90px] rounded-lg overflow-hidden border border-gray-200 bg-white">
                            <video
                                v-if="item.type === 'video'"
                                :src="item.url"
                                :poster="item.thumbnailUrl || undefined"
                                class="w-full h-full object-cover"
                                muted
                                playsinline
                                preload="metadata"
                            ></video>
                            <img
                                v-else
                                :src="item.thumbnailUrl || item.url"
                                :alt="item.alt || item.title || 'Imagem'"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            />
                        </div>

                        <div class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Título</label>
                                    <input
                                        type="text"
                                        :value="item.title"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                        placeholder="Título opcional"
                                        @input="updateMediaItemField(activeAlbum, index, 'title', $event.target.value)"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Legenda</label>
                                    <input
                                        type="text"
                                        :value="item.caption"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                        placeholder="Legenda opcional"
                                        @input="updateMediaItemField(activeAlbum, index, 'caption', $event.target.value)"
                                    />
                                </div>
                            </div>

                            <div v-if="item.type === 'image'">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Texto alternativo (acessibilidade)</label>
                                <input
                                    type="text"
                                    :value="item.alt"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                    placeholder="Descreva a imagem"
                                    @input="updateMediaItemField(activeAlbum, index, 'alt', $event.target.value)"
                                />
                            </div>

                            <label class="inline-flex items-center">
                                <input
                                    type="checkbox"
                                    :checked="item.isPrivate"
                                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                                    @change="updateMediaItemField(activeAlbum, index, 'isPrivate', $event.target.checked)"
                                />
                                <span class="ml-2 text-sm text-gray-700">Não exibir publicamente</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Fundo</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="photoGalleryBackgroundColorHex"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        @change="updateStyle('backgroundColor', $event.target.value)"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                        @click="pickPhotoGalleryBackgroundColor"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="style.backgroundColor || '#ffffff'"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Colunas (Grid/Masonry)</label>
                <select
                    :value="style.columns || 3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    @change="updateStyle('columns', parseInt($event.target.value, 10))"
                >
                    <option :value="2">2 colunas</option>
                    <option :value="3">3 colunas</option>
                    <option :value="4">4 colunas</option>
                    <option :value="5">5 colunas</option>
                </select>
            </div>
        </div>
    </div>

    <MediaGalleryModal
        :show="showMediaGallery"
        :title="selectorTitle"
        media-type="all"
        :allow-crop="false"
        :multiple="true"
        :allowed-album-types="[albumTypeFilter]"
        :default-album-type="albumTypeFilter"
        @close="showMediaGallery = false"
        @select="onMediaSelected"
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
.border-wedding-600 {
    border-color: #a18072;
}

.album-tabs {
    @apply grid w-full grid-cols-2 items-end gap-2 border-b border-gray-300;
}

.album-tab {
    @apply px-4 py-2 text-sm font-semibold text-gray-500 transition-all duration-150;
    border: 1px solid transparent;
    border-bottom: none;
    border-radius: 0.65rem 0.65rem 0 0;
    margin-bottom: -1px;
    background: transparent;
}

.album-tab:hover {
    @apply text-gray-700;
    border-color: #e5e7eb;
    background: #f9fafb;
}

.album-tab-active {
    @apply text-wedding-700;
    background: #ffffff;
    border-color: #b8998a;
    box-shadow: inset 0 2px 0 #b8998a;
}
</style>
