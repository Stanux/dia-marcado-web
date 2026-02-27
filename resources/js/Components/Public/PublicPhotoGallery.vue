<script setup>
/**
 * PublicPhotoGallery Component
 *
 * Galeria pública com curadoria manual, mídia mista (foto + vídeo),
 * lazy loading, infinite scroll e lightbox.
 */
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
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

const DEFAULT_TITLE_TYPOGRAPHY = {
    fontFamily: 'Playfair Display',
    fontColor: '#c45a6f',
    fontSize: 40,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_TABS_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#6b7280',
    fontSize: 14,
    fontWeight: 500,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_TABS_ACTIVE_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#111827',
    fontSize: 14,
    fontWeight: 600,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_TABS_STYLE = {
    backgroundColor: '#f3f4f6',
    activeBackgroundColor: '#ffffff',
    borderColor: '#e5e7eb',
    activeBorderColor: '#d87a8d',
};

const DEFAULT_DISPLAY = {
    showBefore: true,
    showAfter: true,
};

const style = computed(() => props.content.style || {});
const resolveBaseBackgroundColor = (value) => {
    const fallback = props.theme?.baseBackgroundColor || '#ffffff';

    if (typeof value !== 'string' || !value.trim()) {
        return fallback;
    }

    const normalized = value.trim().toLowerCase();
    if (normalized === '#ffffff' || normalized === '#fff') {
        return fallback;
    }

    return value;
};

const sectionBackgroundColor = computed(() => resolveBaseBackgroundColor(style.value.backgroundColor));
const layout = computed(() => props.content.layout || 'masonry');
const showLightbox = computed(() => props.content.showLightbox ?? true);
const allowDownload = computed(() => props.content.allowDownload ?? true);
const columns = computed(() => style.value.columns || 3);
const PRELOAD_BATCH_SIZE = 8;
const perPage = computed(() => {
    const candidate = Number(props.content?.pagination?.perPage ?? 20);
    return Number.isFinite(candidate) && candidate > 0 ? candidate : 20;
});
const videoHoverPreview = computed(() => props.content?.video?.hoverPreview !== false);
const videoHoverDelay = computed(() => {
    const delay = Number(props.content?.video?.hoverDelayMs ?? 1000);
    return Number.isFinite(delay) && delay >= 0 ? delay : 1000;
});

const normalizeGalleryItem = (item, index = 0) => {
    if (!item) {
        return null;
    }

    if (typeof item === 'string') {
        return {
            mediaId: null,
            type: 'image',
            url: item,
            originalUrl: item,
            displayUrl: item,
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

    const originalUrl = item.originalUrl ?? item.original_url ?? item.url ?? '';
    const displayUrl = type === 'video'
        ? (item.displayUrl ?? item.display_url ?? originalUrl)
        : (item.displayUrl ?? item.display_url ?? item.thumbnailUrl ?? item.thumbnail_url ?? originalUrl);
    const thumbnailUrl = item.thumbnailUrl ?? item.thumbnail_url ?? (type === 'video' ? originalUrl : displayUrl);

    return {
        mediaId: item.mediaId ?? item.media_id ?? item.id ?? null,
        type,
        url: item.url ?? '',
        originalUrl,
        displayUrl,
        thumbnailUrl,
        alt: item.alt ?? '',
        title: item.title ?? '',
        caption: item.caption ?? '',
        width: item.width ?? null,
        height: item.height ?? null,
        isPrivate: false,
        sortOrder: Number.isFinite(item.sortOrder) ? item.sortOrder : index,
    };
};

const normalizeAlbum = (album, fallbackTitle) => {
    const items = Array.isArray(album?.items) ? album.items : [];
    const legacyPhotos = Array.isArray(album?.photos) ? album.photos : [];

    const baseItems = items.length > 0 ? items : legacyPhotos;

    return {
        title: album?.title || fallbackTitle,
        items: baseItems
            .map((item, index) => normalizeGalleryItem(item, index))
            .filter(Boolean)
            .sort((a, b) => a.sortOrder - b.sortOrder),
    };
};

const albums = computed(() => {
    const source = props.content.albums || {};

    return {
        before: normalizeAlbum(source.before, 'Nossa História'),
        after: normalizeAlbum(source.after, 'O Grande Dia'),
    };
});

const display = computed(() => ({
    ...DEFAULT_DISPLAY,
    ...(props.content?.display || {}),
}));

const titleTypography = computed(() => ({
    ...DEFAULT_TITLE_TYPOGRAPHY,
    ...(props.content?.titleTypography || {}),
}));

const tabsTypography = computed(() => ({
    ...DEFAULT_TABS_TYPOGRAPHY,
    ...(props.content?.tabsTypography || {}),
}));

const tabsActiveTypography = computed(() => ({
    ...DEFAULT_TABS_ACTIVE_TYPOGRAPHY,
    ...(props.content?.tabsActiveTypography || {}),
}));

const tabsStyle = computed(() => ({
    ...DEFAULT_TABS_STYLE,
    ...(props.content?.tabsStyle || {}),
}));

const titleTextStyle = computed(() => ({
    fontFamily: titleTypography.value.fontFamily || props.theme?.fontFamily || 'Playfair Display',
    color: titleTypography.value.fontColor || props.theme?.primaryColor || '#c45a6f',
    fontSize: titleTypography.value.fontSize ? `${titleTypography.value.fontSize}px` : undefined,
    fontWeight: titleTypography.value.fontWeight || 700,
    fontStyle: titleTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: titleTypography.value.fontUnderline ? 'underline' : 'none',
}));

const tabTextStyle = computed(() => ({
    fontFamily: tabsTypography.value.fontFamily || 'Montserrat',
    color: tabsTypography.value.fontColor || '#6b7280',
    fontSize: tabsTypography.value.fontSize ? `${tabsTypography.value.fontSize}px` : undefined,
    fontWeight: tabsTypography.value.fontWeight || 500,
    fontStyle: tabsTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: tabsTypography.value.fontUnderline ? 'underline' : 'none',
}));

const tabActiveTextStyle = computed(() => ({
    fontFamily: tabsActiveTypography.value.fontFamily || tabsTypography.value.fontFamily || 'Montserrat',
    color: tabsActiveTypography.value.fontColor || '#111827',
    fontSize: tabsActiveTypography.value.fontSize ? `${tabsActiveTypography.value.fontSize}px` : undefined,
    fontWeight: tabsActiveTypography.value.fontWeight || 600,
    fontStyle: tabsActiveTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: tabsActiveTypography.value.fontUnderline ? 'underline' : 'none',
}));

const availableAlbumKeys = computed(() => {
    const keys = [];

    if (display.value.showBefore) {
        keys.push('before');
    }

    if (display.value.showAfter) {
        keys.push('after');
    }

    return keys;
});

const visibleAlbums = computed(() => availableAlbumKeys.value.map((key) => ({
    key,
    title: albums.value[key]?.title || (key === 'before' ? 'Nossa História' : 'O Grande Dia'),
})));

const hasVisibleAlbums = computed(() => visibleAlbums.value.length > 0);

const activeAlbum = ref('before');
const visibleCountByAlbum = ref({
    before: perPage.value,
    after: perPage.value,
});
const loadingMoreByAlbum = ref({
    before: false,
    after: false,
});

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
const slideshowIndex = ref(0);
const sentinelRef = ref(null);
const previewSourceCache = ref(new Map());
const preloadedSources = ref(new Set());
const getCacheStorageKey = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return `dia-marcado:gallery:${window.location.pathname}`;
};

let observer = null;
let slideshowInterval = null;
const videoHoverTimeouts = new Map();

const getCurrentAlbumItems = (albumKey) => {
    if (!availableAlbumKeys.value.includes(albumKey)) {
        return [];
    }

    return albums.value[albumKey]?.items || [];
};

const currentItems = computed(() => getCurrentAlbumItems(activeAlbum.value));

const currentVisibleCount = computed(() => {
    const count = visibleCountByAlbum.value[activeAlbum.value] ?? perPage.value;
    return Math.max(perPage.value, count);
});

const visibleItems = computed(() => {
    if (layout.value === 'slideshow') {
        return currentItems.value;
    }

    return currentItems.value.slice(0, currentVisibleCount.value);
});

const hasMoreItems = computed(() => {
    if (layout.value === 'slideshow') {
        return false;
    }

    return currentVisibleCount.value < currentItems.value.length;
});

const isLoadingMore = computed(() => loadingMoreByAlbum.value[activeAlbum.value] === true);

const gridColumnsClass = computed(() => {
    switch (columns.value) {
        case 2: return 'grid-cols-1 sm:grid-cols-2';
        case 3: return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
        case 4: return 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4';
        case 5: return 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5';
        default: return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
    }
});

const placeholderImage = (index) => {
    const colors = ['#f3e8ff', '#fce7f3', '#dbeafe', '#d1fae5', '#fef3c7'];
    const color = colors[index % colors.length].replace('#', '%23');
    return `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400' viewBox='0 0 400 400'%3E%3Crect fill='${color}' width='400' height='400'/%3E%3Ctext fill='%239ca3af' font-family='sans-serif' font-size='16' x='50%25' y='50%25' text-anchor='middle' dy='.3em'%3EMídia ${index + 1}%3C/text%3E%3C/svg%3E`;
};

const resolveOriginalSource = (item, index) => {
    return item?.originalUrl || item?.url || placeholderImage(index);
};

const resolveDisplaySource = (item, index) => {
    if (item?.type === 'video') {
        return item?.displayUrl || resolveOriginalSource(item, index);
    }

    return item?.displayUrl || item?.thumbnailUrl || resolveOriginalSource(item, index);
};

const getMediaCacheKey = (item, index) => {
    return `${item?.mediaId || 'no-id'}|${item?.displayUrl || item?.thumbnailUrl || item?.url || 'no-url'}|${index}`;
};

const resolvePreviewSource = (item, index) => {
    const cacheKey = getMediaCacheKey(item, index);
    const cached = previewSourceCache.value.get(cacheKey);

    if (cached) {
        return cached;
    }

    const resolved = resolveDisplaySource(item, index);
    previewSourceCache.value.set(cacheKey, resolved);

    return resolved;
};

const onMediaPreviewError = (item, index, event) => {
    const target = event?.target;
    if (!target) {
        return;
    }

    const fallback = resolveOriginalSource(item, index);
    const cacheKey = getMediaCacheKey(item, index);

    if (target.src !== fallback) {
        target.src = fallback;
    }

    previewSourceCache.value.set(cacheKey, fallback);
};

const preloadSource = (url) => {
    if (!url || preloadedSources.value.has(url)) {
        return;
    }

    preloadedSources.value.add(url);
    const img = new Image();
    img.decoding = 'async';
    img.loading = 'eager';
    img.src = url;
};

const prefetchUpcomingMedia = () => {
    if (layout.value === 'slideshow') {
        return;
    }

    const start = currentVisibleCount.value;
    const end = start + PRELOAD_BATCH_SIZE;
    const upcoming = currentItems.value.slice(start, end);

    upcoming.forEach((item, index) => {
        const absoluteIndex = start + index;
        preloadSource(resolvePreviewSource(item, absoluteIndex));
    });
};

const persistGalleryCache = () => {
    const cacheKey = getCacheStorageKey();
    if (!cacheKey) {
        return;
    }

    try {
        sessionStorage.setItem(
            cacheKey,
            JSON.stringify({
                activeAlbum: activeAlbum.value,
                visibleCountByAlbum: visibleCountByAlbum.value,
            }),
        );
    } catch (_error) {
        // noop: cache is best effort
    }
};

const ensureActiveAlbumVisible = () => {
    if (availableAlbumKeys.value.includes(activeAlbum.value)) {
        return;
    }

    activeAlbum.value = availableAlbumKeys.value[0] || 'before';
};

const restoreGalleryCache = () => {
    const cacheKey = getCacheStorageKey();
    if (!cacheKey) {
        return;
    }

    try {
        const raw = sessionStorage.getItem(cacheKey);
        if (!raw) {
            return;
        }

        const parsed = JSON.parse(raw);
        const cachedCounts = parsed?.visibleCountByAlbum;
        const cachedActiveAlbum = parsed?.activeAlbum;

        if (cachedCounts && typeof cachedCounts === 'object') {
            visibleCountByAlbum.value = {
                before: Number(cachedCounts.before) > 0 ? Number(cachedCounts.before) : perPage.value,
                after: Number(cachedCounts.after) > 0 ? Number(cachedCounts.after) : perPage.value,
            };
        }

        if (
            (cachedActiveAlbum === 'before' || cachedActiveAlbum === 'after')
            && availableAlbumKeys.value.includes(cachedActiveAlbum)
        ) {
            activeAlbum.value = cachedActiveAlbum;
        }
    } catch (_error) {
        // noop: cache is best effort
    }

    ensureActiveAlbumVisible();
};

const currentSlideshowItem = computed(() => currentItems.value[slideshowIndex.value] || null);

const syncVisibleCountForAlbum = (albumKey) => {
    const total = getCurrentAlbumItems(albumKey).length;
    const current = visibleCountByAlbum.value[albumKey] ?? perPage.value;

    if (total === 0) {
        visibleCountByAlbum.value[albumKey] = perPage.value;
        return;
    }

    if (current < perPage.value) {
        visibleCountByAlbum.value[albumKey] = perPage.value;
        return;
    }

    if (current > total) {
        visibleCountByAlbum.value[albumKey] = total;
    }
};

const loadMore = () => {
    if (!hasMoreItems.value || isLoadingMore.value) {
        return;
    }

    loadingMoreByAlbum.value = {
        ...loadingMoreByAlbum.value,
        [activeAlbum.value]: true,
    };

    window.setTimeout(() => {
        const total = currentItems.value.length;
        const nextCount = Math.min(total, currentVisibleCount.value + perPage.value);

        visibleCountByAlbum.value = {
            ...visibleCountByAlbum.value,
            [activeAlbum.value]: nextCount,
        };

        loadingMoreByAlbum.value = {
            ...loadingMoreByAlbum.value,
            [activeAlbum.value]: false,
        };

        observeSentinel();
    }, 220);
};

const observeSentinel = () => {
    if (!observer) {
        return;
    }

    observer.disconnect();

    nextTick(() => {
        if (sentinelRef.value && hasMoreItems.value) {
            observer.observe(sentinelRef.value);
        }
    });
};

const openLightbox = (index) => {
    if (!showLightbox.value) {
        return;
    }

    lightboxIndex.value = index;
    lightboxOpen.value = true;
};

const closeLightbox = () => {
    lightboxOpen.value = false;
};

const startSlideshow = () => {
    if (layout.value !== 'slideshow' || currentItems.value.length <= 1 || slideshowInterval) {
        return;
    }

    slideshowInterval = window.setInterval(() => {
        slideshowIndex.value = (slideshowIndex.value + 1) % currentItems.value.length;
    }, 4500);
};

const stopSlideshow = () => {
    if (slideshowInterval) {
        window.clearInterval(slideshowInterval);
        slideshowInterval = null;
    }
};

const clearVideoHoverTimeout = (mediaKey) => {
    const timeoutId = videoHoverTimeouts.get(mediaKey);
    if (timeoutId) {
        window.clearTimeout(timeoutId);
        videoHoverTimeouts.delete(mediaKey);
    }
};

const getVideoElementFromEvent = (event) => {
    const card = event?.currentTarget;
    if (!card || typeof card.querySelector !== 'function') {
        return null;
    }

    return card.querySelector('video');
};

const onVideoHoverEnter = (item, event) => {
    if (!videoHoverPreview.value || item?.type !== 'video') {
        return;
    }

    const mediaKey = item.mediaId || item.originalUrl || item.url;
    clearVideoHoverTimeout(mediaKey);

    const video = getVideoElementFromEvent(event);
    if (!video) {
        return;
    }

    const timeoutId = window.setTimeout(() => {
        videoHoverTimeouts.delete(mediaKey);

        try {
            video.currentTime = 0;
            const playPromise = video.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => {});
            }
        } catch (_error) {
            // noop
        }
    }, videoHoverDelay.value);

    videoHoverTimeouts.set(mediaKey, timeoutId);
};

const onVideoHoverLeave = (item, event) => {
    if (item?.type !== 'video') {
        return;
    }

    const mediaKey = item.mediaId || item.originalUrl || item.url;
    clearVideoHoverTimeout(mediaKey);

    const video = getVideoElementFromEvent(event);
    if (!video) {
        return;
    }

    try {
        video.pause();
        video.currentTime = 0;
    } catch (_error) {
        // noop
    }
};

watch(perPage, (value) => {
    visibleCountByAlbum.value = {
        before: value,
        after: value,
    };
});

watch(activeAlbum, () => {
    ensureActiveAlbumVisible();
    syncVisibleCountForAlbum(activeAlbum.value);
    slideshowIndex.value = 0;
    observeSentinel();
    persistGalleryCache();
    prefetchUpcomingMedia();
});

watch(
    availableAlbumKeys,
    (keys) => {
        if (!keys.includes(activeAlbum.value)) {
            activeAlbum.value = keys[0] || 'before';
            return;
        }

        syncVisibleCountForAlbum(activeAlbum.value);
        observeSentinel();
    },
    { immediate: true },
);

watch(
    () => albums.value,
    () => {
        syncVisibleCountForAlbum('before');
        syncVisibleCountForAlbum('after');
        if (slideshowIndex.value >= currentItems.value.length) {
            slideshowIndex.value = 0;
        }
        observeSentinel();
        prefetchUpcomingMedia();
    },
    { deep: true },
);

watch(layout, (value) => {
    if (value === 'slideshow') {
        stopSlideshow();
        startSlideshow();
        return;
    }

    stopSlideshow();
    observeSentinel();
    prefetchUpcomingMedia();
});

watch(currentVisibleCount, () => {
    persistGalleryCache();
    prefetchUpcomingMedia();
});

onMounted(() => {
    restoreGalleryCache();
    ensureActiveAlbumVisible();

    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                loadMore();
            }
        },
        {
            root: null,
            rootMargin: '180px 0px',
            threshold: 0,
        },
    );

    syncVisibleCountForAlbum('before');
    syncVisibleCountForAlbum('after');
    observeSentinel();
    prefetchUpcomingMedia();
    startSlideshow();
});

onUnmounted(() => {
    stopSlideshow();

    if (observer) {
        observer.disconnect();
    }

    videoHoverTimeouts.forEach((timeoutId) => window.clearTimeout(timeoutId));
    videoHoverTimeouts.clear();
});
</script>

<template>
    <section
        v-if="hasVisibleAlbums"
        id="photo-gallery"
        class="py-20 px-4"
        :style="{ backgroundColor: sectionBackgroundColor }"
    >
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-6">
                <h2
                    class="text-3xl md:text-4xl font-bold mb-4"
                    :style="titleTextStyle"
                >
                    {{ content.title || 'Galeria de Fotos' }}
                </h2>
            </div>

            <div v-if="visibleAlbums.length > 1" class="flex justify-center mb-10">
                <div
                    class="flex w-full max-w-md sm:max-w-none sm:w-auto flex-col sm:flex-row rounded-xl p-1.5 gap-1"
                    :style="{ backgroundColor: tabsStyle.backgroundColor, border: `1px solid ${tabsStyle.borderColor}` }"
                >
                    <button
                        v-for="album in visibleAlbums"
                        :key="album.key"
                        type="button"
                        class="w-full sm:w-auto px-4 sm:px-6 py-2.5 rounded-lg transition-all duration-200 break-words"
                        :style="activeAlbum === album.key
                            ? {
                                ...tabActiveTextStyle,
                                backgroundColor: tabsStyle.activeBackgroundColor,
                                border: `1px solid ${tabsStyle.activeBorderColor}`,
                                boxShadow: '0 3px 10px rgba(0, 0, 0, 0.08)'
                            }
                            : {
                                ...tabTextStyle,
                                backgroundColor: 'transparent',
                                border: '1px solid transparent'
                            }"
                        @click="activeAlbum = album.key"
                    >
                        {{ album.title }}
                    </button>
                </div>
            </div>
            <div v-else-if="visibleAlbums.length === 1" class="flex justify-center mb-10">
                <div
                    class="px-4 sm:px-6 py-2.5 rounded-lg break-words text-center max-w-full"
                    :style="{
                        ...tabActiveTextStyle,
                        backgroundColor: tabsStyle.activeBackgroundColor,
                        border: `1px solid ${tabsStyle.activeBorderColor}`,
                        boxShadow: '0 3px 10px rgba(0, 0, 0, 0.08)'
                    }"
                >
                    {{ visibleAlbums[0].title }}
                </div>
            </div>

            <div
                v-if="currentItems.length === 0"
                class="text-center py-16 bg-gray-50 rounded-2xl"
            >
                <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-500 text-lg">Nenhuma mídia neste álbum</p>
            </div>

            <div
                v-else-if="layout === 'grid' || layout === 'masonry'"
                class="grid gap-4"
                :class="gridColumnsClass"
            >
                <article
                    v-for="(item, index) in visibleItems"
                    :key="`${item.mediaId || item.url}-${index}`"
                    class="relative group cursor-pointer overflow-hidden rounded-xl"
                    :class="{ 'row-span-2': layout === 'masonry' && index % 5 === 0 }"
                    @click="openLightbox(index)"
                    @mouseenter="onVideoHoverEnter(item, $event)"
                    @mouseleave="onVideoHoverLeave(item, $event)"
                >
                    <video
                        v-if="item.type === 'video'"
                        :src="resolveDisplaySource(item, index)"
                        :poster="resolvePreviewSource(item, index)"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        :class="layout === 'masonry' && index % 5 === 0 ? 'aspect-[3/4]' : 'aspect-square'"
                        muted
                        playsinline
                        preload="metadata"
                    ></video>
                    <img
                        v-else
                        :src="resolvePreviewSource(item, index)"
                        :alt="item.alt || item.title || `Imagem ${index + 1}`"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        :class="layout === 'masonry' && index % 5 === 0 ? 'aspect-[3/4]' : 'aspect-square'"
                        loading="lazy"
                        decoding="async"
                        @error="onMediaPreviewError(item, index, $event)"
                    />

                    <div v-if="item.type === 'video'" class="absolute top-4 left-4 bg-black/65 text-white px-2.5 py-1 rounded-full text-xs font-semibold">
                        Vídeo
                    </div>

                    <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                        <div class="p-5 text-white w-full">
                            <p v-if="item.title" class="font-semibold text-lg">{{ item.title }}</p>
                            <p v-if="item.caption" class="text-sm text-white/85 mt-1">{{ item.caption }}</p>
                            <p v-if="item.type === 'video'" class="text-xs text-white/80 mt-2">Clique para abrir o player</p>
                        </div>
                    </div>
                </article>

                <template v-if="isLoadingMore">
                    <div
                        v-for="n in 4"
                        :key="`skeleton-${activeAlbum}-${n}`"
                        class="rounded-xl bg-gray-100 animate-pulse aspect-square"
                        aria-hidden="true"
                    ></div>
                </template>
            </div>

            <div
                v-else-if="layout === 'slideshow'"
                class="relative max-w-5xl mx-auto"
                @mouseenter="stopSlideshow"
                @mouseleave="startSlideshow"
            >
                <div class="aspect-video bg-gray-100 rounded-2xl overflow-hidden shadow-xl cursor-pointer" @click="openLightbox(slideshowIndex)">
                    <video
                        v-if="currentSlideshowItem?.type === 'video'"
                        :src="resolveDisplaySource(currentSlideshowItem, slideshowIndex)"
                        :poster="resolvePreviewSource(currentSlideshowItem, slideshowIndex)"
                        class="w-full h-full object-cover"
                        muted
                        playsinline
                        preload="metadata"
                    ></video>
                    <img
                        v-else-if="currentSlideshowItem"
                        :src="resolvePreviewSource(currentSlideshowItem, slideshowIndex)"
                        :alt="currentSlideshowItem.alt || currentSlideshowItem.title || 'Mídia destaque'"
                        class="w-full h-full object-cover"
                        loading="lazy"
                        decoding="async"
                        @error="onMediaPreviewError(currentSlideshowItem, slideshowIndex, $event)"
                    />
                </div>

                <div class="flex justify-center mt-6 space-x-2">
                    <button
                        v-for="(item, index) in currentItems"
                        :key="`${item.mediaId || item.url}-dot-${index}`"
                        type="button"
                        class="w-3 h-3 rounded-full transition-all duration-200"
                        :class="slideshowIndex === index ? 'scale-125' : 'opacity-50 hover:opacity-75'"
                        :style="{ backgroundColor: slideshowIndex === index ? (theme.primaryColor || '#c45a6f') : '#9ca3af' }"
                        @click="slideshowIndex = index"
                    ></button>
                </div>
            </div>

            <div v-if="hasMoreItems" ref="sentinelRef" class="h-8" aria-hidden="true"></div>
        </div>

        <Lightbox
            v-if="lightboxOpen"
            :items="currentItems"
            :initial-index="lightboxIndex"
            :allow-download="allowDownload"
            @close="closeLightbox"
        />
    </section>
</template>

<style scoped>
.row-span-2 {
    grid-row: span 2;
}
</style>
