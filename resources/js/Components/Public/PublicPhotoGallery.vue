<script setup>
/**
 * PublicPhotoGallery Component
 * 
 * Renders the Photo Gallery section with albums, lightbox support,
 * and multiple layout options (masonry, grid, slideshow).
 * 
 * @Requirements: 13.1, 13.5, 13.6
 */
import { computed, ref } from 'vue';
import Lightbox from '@/Components/Public/Lightbox.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
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

// Get photos for current album (excluding private ones)
const currentPhotos = computed(() => {
    const albumPhotos = albums.value[activeAlbum.value]?.photos || [];
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
let slideshowInterval = null;

const startSlideshow = () => {
    if (slideshowInterval) return;
    slideshowInterval = setInterval(() => {
        if (currentPhotos.value.length > 0) {
            slideshowIndex.value = (slideshowIndex.value + 1) % currentPhotos.value.length;
        }
    }, 4000);
};

const stopSlideshow = () => {
    if (slideshowInterval) {
        clearInterval(slideshowInterval);
        slideshowInterval = null;
    }
};
</script>

<template>
    <section 
        class="py-20 px-4"
        :style="{ backgroundColor: style.backgroundColor || '#ffffff' }"
        id="photo-gallery"
    >
        <div class="max-w-7xl mx-auto">
            <!-- Section Header -->
            <div class="text-center mb-10">
                <h2 
                    class="text-3xl md:text-4xl font-bold mb-4"
                    :style="{ color: theme.primaryColor, fontFamily: theme.fontFamily }"
                >
                    Galeria de Fotos
                </h2>
            </div>

            <!-- Album Tabs -->
            <div class="flex justify-center mb-10">
                <div class="flex w-full max-w-md sm:max-w-none sm:w-auto flex-col sm:flex-row bg-gray-100 rounded-xl p-1.5 gap-1">
                    <button
                        @click="activeAlbum = 'before'"
                        class="w-full sm:w-auto px-4 sm:px-6 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 break-words"
                        :class="activeAlbum === 'before' 
                            ? 'bg-white shadow-md text-gray-900' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        {{ albums.before?.title || 'Nossa História' }}
                    </button>
                    <button
                        @click="activeAlbum = 'after'"
                        class="w-full sm:w-auto px-4 sm:px-6 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 break-words"
                        :class="activeAlbum === 'after' 
                            ? 'bg-white shadow-md text-gray-900' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        {{ albums.after?.title || 'O Grande Dia' }}
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div 
                v-if="currentPhotos.length === 0"
                class="text-center py-16 bg-gray-50 rounded-2xl"
            >
                <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-500 text-lg">Nenhuma foto neste álbum</p>
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
                    class="relative group cursor-pointer overflow-hidden rounded-xl"
                    :class="{ 'row-span-2': layout === 'masonry' && index % 5 === 0 }"
                    @click="openLightbox(index)"
                >
                    <img
                        :src="photo.url || placeholderImage(index)"
                        :alt="photo.alt || photo.title || `Foto ${index + 1}`"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                        :class="layout === 'masonry' && index % 5 === 0 ? 'aspect-[3/4]' : 'aspect-square'"
                        loading="lazy"
                    />
                    
                    <!-- Overlay on hover -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                        <div class="p-5 text-white w-full">
                            <p v-if="photo.title" class="font-semibold text-lg">{{ photo.title }}</p>
                            <p v-if="photo.caption" class="text-sm text-white/80 mt-1">{{ photo.caption }}</p>
                        </div>
                    </div>

                    <!-- Zoom Icon -->
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slideshow Layout -->
            <div 
                v-else-if="layout === 'slideshow'"
                class="relative max-w-5xl mx-auto"
                @mouseenter="stopSlideshow"
                @mouseleave="startSlideshow"
            >
                <div class="aspect-video bg-gray-100 rounded-2xl overflow-hidden shadow-xl">
                    <img
                        v-if="currentPhotos[slideshowIndex]"
                        :src="currentPhotos[slideshowIndex].url || placeholderImage(slideshowIndex)"
                        :alt="currentPhotos[slideshowIndex].alt || 'Slideshow'"
                        class="w-full h-full object-cover cursor-pointer"
                        @click="openLightbox(slideshowIndex)"
                    />
                </div>
                
                <!-- Slideshow Navigation -->
                <div class="flex justify-center mt-6 space-x-2">
                    <button
                        v-for="(photo, index) in currentPhotos"
                        :key="index"
                        @click="slideshowIndex = index"
                        class="w-3 h-3 rounded-full transition-all duration-200"
                        :class="slideshowIndex === index ? 'scale-125' : 'opacity-50 hover:opacity-75'"
                        :style="{ backgroundColor: slideshowIndex === index ? theme.primaryColor : '#9ca3af' }"
                    />
                </div>

                <!-- Caption -->
                <div v-if="currentPhotos[slideshowIndex]?.title" class="text-center mt-4">
                    <p class="text-lg font-medium text-gray-800">{{ currentPhotos[slideshowIndex].title }}</p>
                    <p v-if="currentPhotos[slideshowIndex].caption" class="text-gray-600 mt-1">
                        {{ currentPhotos[slideshowIndex].caption }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <Lightbox
            v-if="lightboxOpen"
            :photos="currentPhotos"
            :initial-index="lightboxIndex"
            :allow-download="allowDownload"
            @close="closeLightbox"
        />
    </section>
</template>

<style scoped>
/* Masonry-like effect for varied heights */
.row-span-2 {
    grid-row: span 2;
}
</style>
