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

const LEGACY_ALBUM_TITLES = {
    before: 'Nossa História',
    after: 'O Grande Dia',
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

const normalizeAlbumEntry = (album, index = 0, legacyKey = '') => {
    const baseAlbum = album && typeof album === 'object' ? album : {};
    const items = Array.isArray(baseAlbum.items) ? baseAlbum.items : [];
    const legacyPhotos = Array.isArray(baseAlbum.photos) ? baseAlbum.photos : [];
    const sourceItems = items.length > 0 ? items : legacyPhotos;
    const fallbackTitle = LEGACY_ALBUM_TITLES[legacyKey] || `Álbum ${index + 1}`;

    return {
        id: typeof baseAlbum.id === 'string' && baseAlbum.id.trim()
            ? baseAlbum.id
            : `${legacyKey || 'album'}-${index + 1}`,
        title: baseAlbum.title || fallbackTitle,
        items: sourceItems
            .map((item, itemIndex) => normalizeGalleryItem(item, itemIndex))
            .filter(Boolean)
            .sort((a, b) => a.sortOrder - b.sortOrder),
    };
};

const normalizeAlbumsCollection = (source) => {
    if (Array.isArray(source)) {
        return source
            .map((album, index) => normalizeAlbumEntry(album, index))
            .filter(Boolean);
    }

    if (source && typeof source === 'object') {
        return Object.entries(source)
            .map(([legacyKey, album], index) => normalizeAlbumEntry(album, index, legacyKey))
            .filter(Boolean);
    }

    return [];
};

const albums = computed(() => normalizeAlbumsCollection(props.content.albums || []));

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

const hasVisibleAlbums = computed(() => albums.value.length > 0);

const activeAlbumId = ref('');
const visibleCountByAlbum = ref({});
const loadingMoreByAlbum = ref({});
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

const syncAlbumStateMaps = () => {
    const nextVisibleCount = {};
    const nextLoadingState = {};

    albums.value.forEach((album) => {
        const currentVisibleCount = Number(visibleCountByAlbum.value[album.id]);
        nextVisibleCount[album.id] = currentVisibleCount > 0 ? currentVisibleCount : perPage.value;
        nextLoadingState[album.id] = loadingMoreByAlbum.value[album.id] === true;
    });

    visibleCountByAlbum.value = nextVisibleCount;
    loadingMoreByAlbum.value = nextLoadingState;
};

const ensureActiveAlbumVisible = () => {
    if (!albums.value.some((album) => album.id === activeAlbumId.value)) {
        activeAlbumId.value = albums.value[0]?.id || '';
    }
};

const getCurrentAlbumItems = (albumId) => {
    const album = albums.value.find((entry) => entry.id === albumId);
    return album?.items || [];
};

const currentAlbum = computed(() => albums.value.find((album) => album.id === activeAlbumId.value) || albums.value[0] || null);
const currentItems = computed(() => currentAlbum.value?.items || []);

const currentVisibleCount = computed(() => {
    if (!currentAlbum.value) {
        return perPage.value;
    }

    const count = visibleCountByAlbum.value[currentAlbum.value.id] ?? perPage.value;
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

const isLoadingMore = computed(() => {
    if (!currentAlbum.value) {
        return false;
    }

    return loadingMoreByAlbum.value[currentAlbum.value.id] === true;
});

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

const resolveOriginalSource = (item, index) => item?.originalUrl || item?.url || placeholderImage(index);

const resolveDisplaySource = (item, index) => {
    if (item?.type === 'video') {
        return item?.displayUrl || resolveOriginalSource(item, index);
    }

    return item?.displayUrl || item?.thumbnailUrl || resolveOriginalSource(item, index);
};

const getMediaCacheKey = (item, index) => `${item?.mediaId || 'no-id'}|${item?.displayUrl || item?.thumbnailUrl || item?.url || 'no-url'}|${index}`;

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
                activeAlbumId: activeAlbumId.value,
                visibleCountByAlbum: visibleCountByAlbum.value,
            }),
        );
    } catch (_error) {
        // noop
    }
};

const restoreGalleryCache = () => {
    const cacheKey = getCacheStorageKey();
    if (!cacheKey) {
        ensureActiveAlbumVisible();
        return;
    }

    try {
        const raw = sessionStorage.getItem(cacheKey);
        if (!raw) {
            ensureActiveAlbumVisible();
            return;
        }

        const parsed = JSON.parse(raw);
        const cachedCounts = parsed?.visibleCountByAlbum;
        const nextVisibleCount = {};

        albums.value.forEach((album) => {
            const candidate = Number(cachedCounts?.[album.id]);
            nextVisibleCount[album.id] = candidate > 0 ? candidate : perPage.value;
        });

        visibleCountByAlbum.value = nextVisibleCount;

        if (albums.value.some((album) => album.id === parsed?.activeAlbumId)) {
            activeAlbumId.value = parsed.activeAlbumId;
        }
    } catch (_error) {
        // noop
    }

    ensureActiveAlbumVisible();
};

const currentSlideshowItem = computed(() => currentItems.value[slideshowIndex.value] || null);

const syncVisibleCountForAlbum = (albumId) => {
    const total = getCurrentAlbumItems(albumId).length;
    const currentCount = visibleCountByAlbum.value[albumId] ?? perPage.value;

    if (total === 0) {
        visibleCountByAlbum.value = {
            ...visibleCountByAlbum.value,
            [albumId]: perPage.value,
        };
        return;
    }

    if (currentCount < perPage.value) {
        visibleCountByAlbum.value = {
            ...visibleCountByAlbum.value,
            [albumId]: perPage.value,
        };
        return;
    }

    if (currentCount > total) {
        visibleCountByAlbum.value = {
            ...visibleCountByAlbum.value,
            [albumId]: total,
        };
    }
};

const loadMore = () => {
    if (!hasMoreItems.value || isLoadingMore.value || !currentAlbum.value) {
        return;
    }

    const albumId = currentAlbum.value.id;
    loadingMoreByAlbum.value = {
        ...loadingMoreByAlbum.value,
        [albumId]: true,
    };

    window.setTimeout(() => {
        const albumItems = getCurrentAlbumItems(albumId);
        const total = albumItems.length;
        const visibleCount = visibleCountByAlbum.value[albumId] ?? perPage.value;
        const nextCount = Math.min(total, visibleCount + perPage.value);

        visibleCountByAlbum.value = {
            ...visibleCountByAlbum.value,
            [albumId]: nextCount,
        };

        loadingMoreByAlbum.value = {
            ...loadingMoreByAlbum.value,
            [albumId]: false,
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

watch(perPage, () => {
    syncAlbumStateMaps();
    albums.value.forEach((album) => syncVisibleCountForAlbum(album.id));
});

watch(activeAlbumId, () => {
    ensureActiveAlbumVisible();
    if (currentAlbum.value) {
        syncVisibleCountForAlbum(currentAlbum.value.id);
    }
    slideshowIndex.value = 0;
    observeSentinel();
    persistGalleryCache();
    prefetchUpcomingMedia();
});

watch(
    albums,
    () => {
        syncAlbumStateMaps();
        ensureActiveAlbumVisible();
        albums.value.forEach((album) => syncVisibleCountForAlbum(album.id));
        if (slideshowIndex.value >= currentItems.value.length) {
            slideshowIndex.value = 0;
        }
        observeSentinel();
        prefetchUpcomingMedia();
    },
    { deep: true, immediate: true },
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
    ensureActiveAlbumVisible();
    restoreGalleryCache();

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

    albums.value.forEach((album) => syncVisibleCountForAlbum(album.id));
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
        class="py-16 sm:py-20 px-4"
        :style="{ backgroundColor: sectionBackgroundColor }"
    >
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-8">
                <h2
                    class="text-3xl md:text-4xl font-bold"
                    :style="titleTextStyle"
                >
                    {{ content.title || 'Galeria de Fotos' }}
                </h2>
            </div>

            <div v-if="albums.length > 1" class="flex justify-center mb-10">
                <div
                    class="flex w-full max-w-md sm:max-w-none sm:w-auto flex-col sm:flex-row rounded-xl p-1.5 gap-1"
                    :style="{ backgroundColor: tabsStyle.backgroundColor, border: `1px solid ${tabsStyle.borderColor}` }"
                >
                    <button
                        v-for="album in albums"
                        :key="album.id"
                        type="button"
                        class="w-full sm:w-auto px-4 sm:px-6 py-2.5 rounded-lg transition-all duration-200 break-words"
                        :style="activeAlbumId === album.id
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
                        @click="activeAlbumId = album.id"
                    >
                        {{ album.title }}
                    </button>
                </div>
            </div>
            <div v-else-if="albums.length === 1" class="flex justify-center mb-10">
                <div
                    class="px-4 sm:px-6 py-2.5 rounded-lg break-words text-center max-w-full"
                    :style="{
                        ...tabActiveTextStyle,
                        backgroundColor: tabsStyle.activeBackgroundColor,
                        border: `1px solid ${tabsStyle.activeBorderColor}`,
                        boxShadow: '0 3px 10px rgba(0, 0, 0, 0.08)'
                    }"
                >
                    {{ albums[0].title }}
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
                        :key="`skeleton-${activeAlbumId}-${n}`"
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
