<script setup>
/**
 * PhotoGalleryEditor Component
 * 
 * Editor for the Photo Gallery section of the wedding site.
 * Supports two albums (Before/After), multiple photos per album,
 * layout options, lightbox, and download settings.
 * 
 * @Requirements: 13.1, 13.4, 13.5, 13.6, 13.7
 */
import { ref, watch, computed } from 'vue';
import NavigationSettings from './NavigationSettings.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

// Active album tab
const activeAlbum = ref('before');

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
 * Update album title
 */
const updateAlbumTitle = (albumKey, value) => {
    if (!localContent.value.albums) {
        localContent.value.albums = {
            before: { title: 'Nossa História', photos: [] },
            after: { title: 'O Grande Dia', photos: [] },
        };
    }
    if (!localContent.value.albums[albumKey]) {
        localContent.value.albums[albumKey] = { title: '', photos: [] };
    }
    localContent.value.albums[albumKey].title = value;
    emitChange();
};

/**
 * Add photo to album
 */
const addPhoto = (albumKey) => {
    if (!localContent.value.albums) {
        localContent.value.albums = {
            before: { title: 'Nossa História', photos: [] },
            after: { title: 'O Grande Dia', photos: [] },
        };
    }
    if (!localContent.value.albums[albumKey]) {
        localContent.value.albums[albumKey] = { title: '', photos: [] };
    }
    if (!localContent.value.albums[albumKey].photos) {
        localContent.value.albums[albumKey].photos = [];
    }
    localContent.value.albums[albumKey].photos.push({
        url: '',
        title: '',
        caption: '',
        alt: '',
        isPrivate: false,
    });
    emitChange();
};

/**
 * Update photo field
 */
const updatePhoto = (albumKey, index, field, value) => {
    if (localContent.value.albums?.[albumKey]?.photos?.[index]) {
        localContent.value.albums[albumKey].photos[index][field] = value;
        emitChange();
    }
};

/**
 * Remove photo from album
 */
const removePhoto = (albumKey, index) => {
    if (localContent.value.albums?.[albumKey]?.photos) {
        localContent.value.albums[albumKey].photos.splice(index, 1);
        emitChange();
    }
};

/**
 * Move photo up in album
 */
const movePhotoUp = (albumKey, index) => {
    if (index > 0 && localContent.value.albums?.[albumKey]?.photos) {
        const photos = localContent.value.albums[albumKey].photos;
        const item = photos.splice(index, 1)[0];
        photos.splice(index - 1, 0, item);
        emitChange();
    }
};

/**
 * Move photo down in album
 */
const movePhotoDown = (albumKey, index) => {
    const photos = localContent.value.albums?.[albumKey]?.photos;
    if (photos && index < photos.length - 1) {
        const item = photos.splice(index, 1)[0];
        photos.splice(index + 1, 0, item);
        emitChange();
    }
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
 * Update navigation settings
 */
const updateNavigation = (navigation) => {
    localContent.value.navigation = navigation;
    emitChange();
};

// Computed properties
const albums = computed(() => localContent.value.albums || {
    before: { title: 'Nossa História', photos: [] },
    after: { title: 'O Grande Dia', photos: [] },
});
const style = computed(() => localContent.value.style || {});
const currentAlbumPhotos = computed(() => albums.value[activeAlbum.value]?.photos || []);
</script>

<template>
    <div class="space-y-6">
        <!-- Navigation Settings -->
        <NavigationSettings
            :navigation="localContent.navigation"
            @change="updateNavigation"
        />

        <!-- Layout Settings -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Layout</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Layout</label>
                <select
                    :value="localContent.layout"
                    @change="updateField('layout', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="masonry">Masonry</option>
                    <option value="grid">Grid</option>
                    <option value="slideshow">Slideshow</option>
                </select>
            </div>

            <div class="flex items-center space-x-6">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        :checked="localContent.showLightbox"
                        @change="updateField('showLightbox', $event.target.checked)"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                    />
                    <label class="ml-2 text-sm text-gray-700">Habilitar lightbox</label>
                </div>
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        :checked="localContent.allowDownload"
                        @change="updateField('allowDownload', $event.target.checked)"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                    />
                    <label class="ml-2 text-sm text-gray-700">Permitir download</label>
                </div>
            </div>
        </div>

        <!-- Albums -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Álbuns</h3>
            
            <!-- Album Tabs -->
            <div class="flex space-x-2 border-b border-gray-200">
                <button
                    @click="activeAlbum = 'before'"
                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
                    :class="activeAlbum === 'before' 
                        ? 'border-wedding-600 text-wedding-600' 
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    Álbum "Antes"
                </button>
                <button
                    @click="activeAlbum = 'after'"
                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
                    :class="activeAlbum === 'after' 
                        ? 'border-wedding-600 text-wedding-600' 
                        : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    Álbum "Depois"
                </button>
            </div>

            <!-- Album Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título do Álbum</label>
                <input
                    type="text"
                    :value="albums[activeAlbum]?.title"
                    @input="updateAlbumTitle(activeAlbum, $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    :placeholder="activeAlbum === 'before' ? 'Nossa História' : 'O Grande Dia'"
                />
            </div>

            <!-- Photos List -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">
                        Fotos ({{ currentAlbumPhotos.length }})
                    </span>
                    <button
                        @click="addPhoto(activeAlbum)"
                        class="text-sm text-wedding-600 hover:text-wedding-700 font-medium"
                    >
                        + Adicionar foto
                    </button>
                </div>

                <div v-if="currentAlbumPhotos.length === 0" class="p-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                    Nenhuma foto neste álbum. Clique em "Adicionar foto" para começar.
                </div>

                <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                    <div
                        v-for="(photo, index) in currentAlbumPhotos"
                        :key="index"
                        class="p-4 bg-gray-50 rounded-lg space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Foto {{ index + 1 }}</span>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="movePhotoUp(activeAlbum, index)"
                                    :disabled="index === 0"
                                    class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                    title="Mover para cima"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="movePhotoDown(activeAlbum, index)"
                                    :disabled="index === currentAlbumPhotos.length - 1"
                                    class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                    title="Mover para baixo"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <button
                                    @click="removePhoto(activeAlbum, index)"
                                    class="p-1 text-red-400 hover:text-red-600"
                                    title="Remover"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">URL da Imagem</label>
                            <input
                                type="text"
                                :value="photo.url"
                                @input="updatePhoto(activeAlbum, index, 'url', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="https://exemplo.com/foto.jpg"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Título</label>
                                <input
                                    type="text"
                                    :value="photo.title"
                                    @input="updatePhoto(activeAlbum, index, 'title', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                    placeholder="Título da foto"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Texto Alt (acessibilidade)</label>
                                <input
                                    type="text"
                                    :value="photo.alt"
                                    @input="updatePhoto(activeAlbum, index, 'alt', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                    placeholder="Descrição da imagem"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Legenda</label>
                            <input
                                type="text"
                                :value="photo.caption"
                                @input="updatePhoto(activeAlbum, index, 'caption', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="Legenda opcional"
                            />
                        </div>

                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="photo.isPrivate"
                                @change="updatePhoto(activeAlbum, index, 'isPrivate', $event.target.checked)"
                                class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                            />
                            <label class="ml-2 text-sm text-gray-700">Foto privada (não exibir publicamente)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Style Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Fundo</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="style.backgroundColor || '#ffffff'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <input
                        type="text"
                        :value="style.backgroundColor || '#ffffff'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Colunas (Grid/Masonry)</label>
                <select
                    :value="style.columns || 3"
                    @change="updateStyle('columns', parseInt($event.target.value))"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option :value="2">2 colunas</option>
                    <option :value="3">3 colunas</option>
                    <option :value="4">4 colunas</option>
                    <option :value="5">5 colunas</option>
                </select>
            </div>
        </div>

        <!-- Help Text -->
        <div class="p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">
                <strong>Dica:</strong> Use o gerenciador de mídia para fazer upload de fotos em lote. 
                As imagens serão automaticamente otimizadas e você poderá adicionar as URLs aqui.
            </p>
        </div>
    </div>
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
</style>
