<script setup>
/**
 * PhotoGalleryPreview Component
 * 
 * Renders the Photo Gallery section with albums, lightbox support,
 * and multiple layout options (masonry, grid, slideshow).
 * 
 * @Requirements: 13.1, 13.5, 13.6
 */
import { computed, ref } from 'vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    isEditMode: {
        type: Boolean,
        default: false,
    },
    viewport: {
        type: String,
        default: 'desktop',
    },
});

// Computed properties
const style = computed(() => props.content.style || {});
const albums = computed(() => props.content.albums || {
    before: { title: 'Nossa História', photos: [] },
    after: { title: 'O Grande Dia', photos: [] },
});
const layout = computed(() => props.content.layout || 'masonry');
const showLightbox = computed(() => props.content.showLightbox ?? true);
const allowDownload = computed(() => props.content.allowDownload ?? true);
const columns = computed(() => style.value.columns || 3);

// Active album tab
const activeAlbum = ref('before');

// Get photos for current album (excluding private ones in guest mode)
const currentPhotos = computed(() => {
    const albumPhotos = albums.value[activeAlbum.value]?.photos || [];
    if (props.isEditMode) {
        return albumPhotos;
    }
    return albumPhotos.filter(photo => !photo.isPrivate);
});

// Lightbox state
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

const openLightbox = (index) => {
    if (!showLightbox.value) return;
    lightboxIndex.value = index;
    lightboxOpen.value = true;
};

const closeLightbox = () => {
    lightboxOpen.value = false;
};

const nextPhoto = () => {
    if (lightboxIndex.value < currentPhotos.value.length - 1) {
        lightboxIndex.value++;
    }
};

const prevPhoto = () => {
    if (lightboxIndex.value > 0) {
        lightboxIndex.value--;
    }
};

// Current lightbox photo
const currentLightboxPhoto = computed(() => currentPhotos.value[lightboxIndex.value] || null);

// Grid columns class
const gridColumnsClass = computed(() => {
    switch (columns.value) {
        case 2: return 'grid-cols-1 sm:grid-cols-2';
        case 3: return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
        case 4: return 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4';
        case 5: return 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5';
        default: return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
    }
});

// Placeholder image
const placeholderImage = (index) => {
    const colors = ['#f3e8ff', '#fce7f3', '#dbeafe', '#d1fae5', '#fef3c7'];
    const color = colors[index % colors.length].replace('#', '%23');
    return `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400' viewBox='0 0 400 400'%3E%3Crect fill='${color}' width='400' height='400'/%3E%3Ctext fill='%239ca3af' font-family='sans-serif' font-size='16' x='50%25' y='50%25' text-anchor='middle' dy='.3em'%3EFoto ${index + 1}%3C/text%3E%3C/svg%3E`;
};

// Slideshow state
const slideshowIndex = ref(0);
const startSlideshow = () => {
    setInterval(() => {
        slideshowIndex.value = (slideshowIndex.value + 1) % currentPhotos.value.length;
    }, 4000);
};
</script>

<template>
    <section 
        class="py-16 px-4 relative"
        :style="{ backgroundColor: style.backgroundColor || '#ffffff' }"
        id="photo-gallery"
    >
        <div class="max-w-7xl mx-auto">
            <!-- Section Header -->
            <div class="text-center mb-8">
                <h2 
                    class="text-2xl md:text-3xl font-bold mb-4"
                    :style="{ color: theme.primaryColor, fontFamily: theme.fontFamily }"
                >
                    Galeria de Fotos
                </h2>
            </div>

            <!-- Album Tabs -->
            <div class="flex justify-center mb-8">
                <div class="inline-flex bg-gray-100 rounded-lg p-1">
                    <button
                        @click="activeAlbum = 'before'"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="activeAlbum === 'before' 
                            ? 'bg-white shadow text-gray-900' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        {{ albums.before?.title || 'Nossa História' }}
                    </button>
                    <button
                        @click="activeAlbum = 'after'"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="activeAlbum === 'after' 
                            ? 'bg-white shadow text-gray-900' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        {{ albums.after?.title || 'O Grande Dia' }}
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div 
                v-if="currentPhotos.length === 0"
                class="text-center py-12 bg-gray-50 rounded-lg"
            >
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-500">Nenhuma foto neste álbum</p>
                <p class="text-sm text-gray-400 mt-1">Adicione fotos no editor</p>
            </div>

            <!-- Grid/Masonry Layout -->
            <div 
                v-else-if="layout === 'grid' || layout === 'masonry'"
                class="grid gap-4"
                :class="gridColumnsClass"
            >
                <div
                    v-for="(photo, index) in currentPhotos"
                    :key="index"
                    class="relative group cursor-pointer overflow-hidden rounded-lg"
                    :class="{ 'row-span-2': layout === 'masonry' && index % 3 === 0 }"
                    @click="openLightbox(index)"
                >
                    <img
                        :src="photo.url || placeholderImage(index)"
                        :alt="photo.alt || photo.title || `Foto ${index + 1}`"
                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        :class="layout === 'masonry' && index % 3 === 0 ? 'aspect-[3/4]' : 'aspect-square'"
                    />
                    
                    <!-- Overlay on hover -->
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-end">
                        <div class="p-4 text-white opacity-0 group-hover:opacity-100 transition-opacity w-full">
                            <p v-if="photo.title" class="font-medium">{{ photo.title }}</p>
                            <p v-if="photo.caption" class="text-sm text-white/80">{{ photo.caption }}</p>
                        </div>
                    </div>

                    <!-- Private Badge -->
                    <div 
                        v-if="photo.isPrivate && isEditMode"
                        class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded"
                    >
                        Privada
                    </div>
                </div>
            </div>

            <!-- Slideshow Layout -->
            <div 
                v-else-if="layout === 'slideshow'"
                class="relative max-w-4xl mx-auto"
            >
                <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                    <img
                        v-if="currentPhotos[slideshowIndex]"
                        :src="currentPhotos[slideshowIndex].url || placeholderImage(slideshowIndex)"
                        :alt="currentPhotos[slideshowIndex].alt || 'Slideshow'"
                        class="w-full h-full object-cover"
                    />
                </div>
                
                <!-- Slideshow Controls -->
                <div class="flex justify-center mt-4 space-x-2">
                    <button
                        v-for="(photo, index) in currentPhotos"
                        :key="index"
                        @click="slideshowIndex = index"
                        class="w-2 h-2 rounded-full transition-colors"
                        :class="slideshowIndex === index ? 'bg-gray-800' : 'bg-gray-300'"
                    />
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div 
                v-if="lightboxOpen && currentLightboxPhoto"
                class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center"
                @click.self="closeLightbox"
            >
                <!-- Close Button -->
                <button
                    @click="closeLightbox"
                    class="absolute top-4 right-4 text-white/80 hover:text-white p-2"
                >
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Previous Button -->
                <button
                    v-if="lightboxIndex > 0"
                    @click="prevPhoto"
                    class="absolute left-4 text-white/80 hover:text-white p-2"
                >
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Image -->
                <div class="max-w-5xl max-h-[80vh] px-16">
                    <img
                        :src="currentLightboxPhoto.url"
                        :alt="currentLightboxPhoto.alt || 'Photo'"
                        class="max-w-full max-h-[80vh] object-contain"
                    />
                    
                    <!-- Caption -->
                    <div v-if="currentLightboxPhoto.title || currentLightboxPhoto.caption" class="text-center mt-4 text-white">
                        <p v-if="currentLightboxPhoto.title" class="font-medium">{{ currentLightboxPhoto.title }}</p>
                        <p v-if="currentLightboxPhoto.caption" class="text-sm text-white/70">{{ currentLightboxPhoto.caption }}</p>
                    </div>

                    <!-- Download Button -->
                    <div v-if="allowDownload" class="text-center mt-4">
                        <a
                            :href="currentLightboxPhoto.url"
                            download
                            class="inline-flex items-center text-white/80 hover:text-white text-sm"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download
                        </a>
                    </div>
                </div>

                <!-- Next Button -->
                <button
                    v-if="lightboxIndex < currentPhotos.length - 1"
                    @click="nextPhoto"
                    class="absolute right-4 text-white/80 hover:text-white p-2"
                >
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Counter -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/60 text-sm">
                    {{ lightboxIndex + 1 }} / {{ currentPhotos.length }}
                </div>
            </div>
        </Teleport>

        <!-- Edit Mode Indicator -->
        <div 
            v-if="isEditMode"
            class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-0.5 rounded z-10"
        >
            Galeria de Fotos
        </div>
    </section>
</template>

<style scoped>
/* Masonry-like effect for varied heights */
.row-span-2 {
    grid-row: span 2;
}
</style>
