<script setup>
/**
 * Lightbox Component
 *
 * Modal fullscreen para mídia mista (imagem + vídeo).
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    items: {
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

const currentIndex = ref(props.initialIndex);
const isZoomed = ref(false);
const zoomLevel = ref(1);
const panPosition = ref({ x: 0, y: 0 });
const isLoading = ref(true);

const allItems = computed(() => props.items || []);
const currentItem = computed(() => allItems.value[currentIndex.value] || null);
const isCurrentVideo = computed(() => currentItem.value?.type === 'video');
const hasPrevious = computed(() => currentIndex.value > 0);
const hasNext = computed(() => currentIndex.value < allItems.value.length - 1);

const getOriginalSource = (item) => {
    if (!item || typeof item !== 'object') {
        return '';
    }

    return item.originalUrl || item.original_url || item.url || '';
};

const getDisplaySource = (item) => {
    if (!item || typeof item !== 'object') {
        return '';
    }

    if (item.type === 'video') {
        return item.displayUrl || item.display_url || getOriginalSource(item);
    }

    return item.displayUrl
        || item.display_url
        || item.thumbnailUrl
        || item.thumbnail_url
        || getOriginalSource(item);
};

const currentOriginalSource = computed(() => getOriginalSource(currentItem.value));

const resetZoom = () => {
    zoomLevel.value = 1;
    isZoomed.value = false;
    panPosition.value = { x: 0, y: 0 };
};

const goToPrevious = () => {
    if (!hasPrevious.value) {
        return;
    }

    currentIndex.value -= 1;
    resetZoom();
    isLoading.value = true;
};

const goToNext = () => {
    if (!hasNext.value) {
        return;
    }

    currentIndex.value += 1;
    resetZoom();
    isLoading.value = true;
};

const toggleZoom = () => {
    if (isCurrentVideo.value) {
        return;
    }

    if (isZoomed.value) {
        resetZoom();
        return;
    }

    zoomLevel.value = 2;
    isZoomed.value = true;
};

const handleMouseMove = (event) => {
    if (!isZoomed.value || isCurrentVideo.value) {
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width - 0.5) * -100;
    const y = ((event.clientY - rect.top) / rect.height - 0.5) * -100;

    panPosition.value = { x, y };
};

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

    if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
        if (diffX > 0) {
            goToPrevious();
        } else {
            goToNext();
        }
    }
};

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
        default:
            break;
    }
};

const downloadCurrentItem = () => {
    if (!currentOriginalSource.value) {
        return;
    }

    const link = document.createElement('a');
    link.href = currentOriginalSource.value;
    link.download = currentItem.value.filename || currentItem.value.title || `midia-${currentIndex.value + 1}`;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const onMediaLoaded = () => {
    isLoading.value = false;
};

watch(currentIndex, () => {
    resetZoom();
});

onMounted(() => {
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', handleKeydown);
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
            <button
                type="button"
                class="absolute top-4 right-4 z-10 w-12 h-12 flex items-center justify-center text-white/70 hover:text-white transition-colors rounded-full hover:bg-white/10"
                aria-label="Fechar"
                @click="emit('close')"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <button
                v-if="hasPrevious"
                type="button"
                class="absolute left-4 z-10 w-14 h-14 flex items-center justify-center text-white/70 hover:text-white transition-colors rounded-full hover:bg-white/10"
                aria-label="Mídia anterior"
                @click="goToPrevious"
            >
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div class="relative max-w-[90vw] max-h-[85vh] flex items-center justify-center" @mousemove="handleMouseMove">
                <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-12 h-12 text-white/50 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <video
                    v-if="currentItem && isCurrentVideo"
                    :key="`video-${currentItem.mediaId || currentItem.url}`"
                    :src="currentOriginalSource"
                    :poster="currentItem.thumbnailUrl || undefined"
                    class="max-w-full max-h-[85vh] object-contain"
                    controls
                    autoplay
                    playsinline
                    preload="metadata"
                    @loadeddata="onMediaLoaded"
                ></video>

                <img
                    v-else-if="currentItem"
                    :src="currentOriginalSource"
                    :alt="currentItem.alt || currentItem.title || 'Mídia'"
                    class="max-w-full max-h-[85vh] object-contain transition-transform duration-200 cursor-zoom-in"
                    :class="{ 'cursor-zoom-out': isZoomed }"
                    :style="{ transform: `scale(${zoomLevel}) translate(${panPosition.x}%, ${panPosition.y}%)` }"
                    draggable="false"
                    @click="toggleZoom"
                    @load="onMediaLoaded"
                />
            </div>

            <button
                v-if="hasNext"
                type="button"
                class="absolute right-4 z-10 w-14 h-14 flex items-center justify-center text-white/70 hover:text-white transition-colors rounded-full hover:bg-white/10"
                aria-label="Próxima mídia"
                @click="goToNext"
            >
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent">
                <div class="max-w-5xl mx-auto">
                    <div v-if="currentItem?.title || currentItem?.caption" class="text-center mb-4">
                        <p v-if="currentItem.title" class="text-white font-medium text-lg">{{ currentItem.title }}</p>
                        <p v-if="currentItem.caption" class="text-white/70 text-sm mt-1">{{ currentItem.caption }}</p>
                    </div>

                    <div class="flex items-center justify-center gap-6">
                        <span class="text-white/60 text-sm">{{ currentIndex + 1 }} / {{ allItems.length }}</span>

                        <button
                            v-if="!isCurrentVideo"
                            type="button"
                            class="text-white/70 hover:text-white transition-colors p-2"
                            :title="isZoomed ? 'Reduzir' : 'Ampliar'"
                            @click="toggleZoom"
                        >
                            <svg v-if="!isZoomed" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                            </svg>
                        </button>

                        <button
                            v-if="allowDownload"
                            type="button"
                            class="text-white/70 hover:text-white transition-colors p-2"
                            title="Download"
                            @click="downloadCurrentItem"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                    </div>

                    <div v-if="allItems.length > 1" class="hidden md:flex justify-center gap-2 mt-4 overflow-x-auto pb-2">
                        <button
                            v-for="(item, index) in allItems.slice(0, 12)"
                            :key="`${item.mediaId || item.url}-thumb-${index}`"
                            type="button"
                            class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 transition-all duration-200 relative"
                            :class="currentIndex === index ? 'ring-2 ring-white opacity-100' : 'opacity-50 hover:opacity-75'"
                            @click="currentIndex = index; isLoading = true"
                        >
                            <video
                                v-if="item.type === 'video'"
                                :src="getDisplaySource(item)"
                                :poster="item.thumbnailUrl || undefined"
                                class="w-full h-full object-cover"
                                muted
                                playsinline
                                preload="metadata"
                            ></video>
                            <img
                                v-else
                                :src="item.thumbnailUrl || getDisplaySource(item)"
                                :alt="`Thumbnail ${index + 1}`"
                                class="w-full h-full object-cover"
                            />
                            <span
                                v-if="item.type === 'video'"
                                class="absolute bottom-1 right-1 text-[10px] px-1 py-0.5 bg-black/70 text-white rounded"
                            >
                                Vídeo
                            </span>
                        </button>
                        <span
                            v-if="allItems.length > 12"
                            class="w-16 h-16 rounded-lg bg-white/10 flex items-center justify-center text-white/60 text-sm flex-shrink-0"
                        >
                            +{{ allItems.length - 12 }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
img,
video {
    user-select: none;
    -webkit-user-drag: none;
}

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
