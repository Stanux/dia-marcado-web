<script setup>
/**
 * Lightbox Component
 * 
 * Fullscreen modal for viewing photos with navigation,
 * zoom functionality, and optional download button.
 * 
 * @Requirements: 13.5
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    photos: {
        type: Array,
        required: true,
    },
    initialIndex: {
        type: Number,
        default: 0,
    },
    allowDownload: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

// Current photo index
const currentIndex = ref(props.initialIndex);

// Zoom state
const isZoomed = ref(false);
const zoomLevel = ref(1);
const panPosition = ref({ x: 0, y: 0 });

// Loading state
const isLoading = ref(true);

// Current photo
const currentPhoto = computed(() => props.photos[currentIndex.value] || null);

// Navigation
const hasPrevious = computed(() => currentIndex.value > 0);
const hasNext = computed(() => currentIndex.value < props.photos.length - 1);

const goToPrevious = () => {
    if (hasPrevious.value) {
        currentIndex.value--;
        resetZoom();
        isLoading.value = true;
    }
};

const goToNext = () => {
    if (hasNext.value) {
        currentIndex.value++;
        resetZoom();
        isLoading.value = true;
    }
};

// Zoom functions
const toggleZoom = () => {
    if (isZoomed.value) {
        resetZoom();
    } else {
        zoomLevel.value = 2;
        isZoomed.value = true;
    }
};

const resetZoom = () => {
    zoomLevel.value = 1;
    isZoomed.value = false;
    panPosition.value = { x: 0, y: 0 };
};

// Handle mouse move for panning when zoomed
const handleMouseMove = (event) => {
    if (!isZoomed.value) return;
    
    const rect = event.currentTarget.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width - 0.5) * -100;
    const y = ((event.clientY - rect.top) / rect.height - 0.5) * -100;
    
    panPosition.value = { x, y };
};

// Handle touch for mobile
let touchStartX = 0;
let touchStartY = 0;

const handleTouchStart = (event) => {
    touchStartX = event.touches[0].clientX;
    touchStartY = event.touches[0].clientY;
};

const handleTouchEnd = (event) => {
    const touchEndX = event.changedTouches[0].clientX;
    const touchEndY = event.changedTouches[0].clientY;
    const diffX = touchEndX - touchStartX;
    const diffY = touchEndY - touchStartY;
    
    // Horizontal swipe
    if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
        if (diffX > 0) {
            goToPrevious();
        } else {
            goToNext();
        }
    }
};

// Keyboard navigation
const handleKeydown = (event) => {
    switch (event.key) {
        case 'Escape':
            emit('close');
            break;
        case 'ArrowLeft':
            goToPrevious();
            break;
        case 'ArrowRight':
            goToNext();
            break;
        case ' ':
            event.preventDefault();
            toggleZoom();
            break;
    }
};

// Download current photo
const downloadPhoto = () => {
    if (!currentPhoto.value?.url) return;
    
    const link = document.createElement('a');
    link.href = currentPhoto.value.url;
    link.download = currentPhoto.value.title || `photo-${currentIndex.value + 1}`;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Image loaded handler
const onImageLoad = () => {
    isLoading.value = false;
};

// Prevent body scroll when lightbox is open
onMounted(() => {
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', handleKeydown);
});

// Reset zoom when photo changes
watch(currentIndex, () => {
    resetZoom();
});
</script>

<template>
    <Teleport to="body">
        <div 
            class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center"
            @click.self="emit('close')"
            @touchstart="handleTouchStart"
            @touchend="handleTouchEnd"
        >
            <!-- Close Button -->
            <button
                @click="emit('close')"
                class="absolute top-4 right-4 z-10 w-12 h-12 flex items-center justify-center text-white/70 hover:text-white transition-colors rounded-full hover:bg-white/10"
                aria-label="Fechar"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Previous Button -->
            <button
                v-if="hasPrevious"
                @click="goToPrevious"
                class="absolute left-4 z-10 w-14 h-14 flex items-center justify-center text-white/70 hover:text-white transition-colors rounded-full hover:bg-white/10"
                aria-label="Foto anterior"
            >
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Image Container -->
            <div 
                class="relative max-w-[90vw] max-h-[85vh] flex items-center justify-center"
                @mousemove="handleMouseMove"
            >
                <!-- Loading Spinner -->
                <div 
                    v-if="isLoading"
                    class="absolute inset-0 flex items-center justify-center"
                >
                    <svg class="w-12 h-12 text-white/50 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Image -->
                <img
                    v-if="currentPhoto"
                    :src="currentPhoto.url"
                    :alt="currentPhoto.alt || currentPhoto.title || 'Photo'"
                    class="max-w-full max-h-[85vh] object-contain transition-transform duration-200 cursor-zoom-in"
                    :class="{ 'cursor-zoom-out': isZoomed }"
                    :style="{
                        transform: `scale(${zoomLevel}) translate(${panPosition.x}%, ${panPosition.y}%)`,
                    }"
                    @click="toggleZoom"
                    @load="onImageLoad"
                    draggable="false"
                />
            </div>

            <!-- Next Button -->
            <button
                v-if="hasNext"
                @click="goToNext"
                class="absolute right-4 z-10 w-14 h-14 flex items-center justify-center text-white/70 hover:text-white transition-colors rounded-full hover:bg-white/10"
                aria-label="Próxima foto"
            >
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Bottom Bar -->
            <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent">
                <div class="max-w-4xl mx-auto">
                    <!-- Caption -->
                    <div v-if="currentPhoto?.title || currentPhoto?.caption" class="text-center mb-4">
                        <p v-if="currentPhoto.title" class="text-white font-medium text-lg">
                            {{ currentPhoto.title }}
                        </p>
                        <p v-if="currentPhoto.caption" class="text-white/70 text-sm mt-1">
                            {{ currentPhoto.caption }}
                        </p>
                    </div>

                    <!-- Controls -->
                    <div class="flex items-center justify-center gap-6">
                        <!-- Counter -->
                        <span class="text-white/60 text-sm">
                            {{ currentIndex + 1 }} / {{ photos.length }}
                        </span>

                        <!-- Zoom Button -->
                        <button
                            @click="toggleZoom"
                            class="text-white/70 hover:text-white transition-colors p-2"
                            :title="isZoomed ? 'Reduzir' : 'Ampliar'"
                        >
                            <svg v-if="!isZoomed" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                            </svg>
                        </button>

                        <!-- Download Button -->
                        <button
                            v-if="allowDownload"
                            @click="downloadPhoto"
                            class="text-white/70 hover:text-white transition-colors p-2"
                            title="Download"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                    </div>

                    <!-- Thumbnail Navigation (for desktop) -->
                    <div 
                        v-if="photos.length > 1"
                        class="hidden md:flex justify-center gap-2 mt-4 overflow-x-auto pb-2"
                    >
                        <button
                            v-for="(photo, index) in photos.slice(0, 10)"
                            :key="index"
                            @click="currentIndex = index"
                            class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 transition-all duration-200"
                            :class="currentIndex === index 
                                ? 'ring-2 ring-white opacity-100' 
                                : 'opacity-50 hover:opacity-75'"
                        >
                            <img
                                :src="photo.url"
                                :alt="`Thumbnail ${index + 1}`"
                                class="w-full h-full object-cover"
                            />
                        </button>
                        <span 
                            v-if="photos.length > 10"
                            class="w-16 h-16 rounded-lg bg-white/10 flex items-center justify-center text-white/60 text-sm flex-shrink-0"
                        >
                            +{{ photos.length - 10 }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Smooth image transitions */
img {
    user-select: none;
    -webkit-user-drag: none;
}

/* Hide scrollbar for thumbnail navigation */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
}
</style>
