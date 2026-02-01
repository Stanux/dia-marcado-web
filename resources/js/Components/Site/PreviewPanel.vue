<script setup>
/**
 * PreviewPanel Component
 * 
 * Provides responsive preview functionality with three viewports:
 * mobile (375px), tablet (768px), and desktop (1280px).
 * Shows draft watermark when content is not published.
 * 
 * @Requirements: 7.1, 7.2, 7.3
 */
import { ref, computed, watch } from 'vue';
import SitePreview from './SitePreview.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    wedding: {
        type: Object,
        default: () => ({}),
    },
    isDraft: {
        type: Boolean,
        default: true,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'view-as-guest']);

// Preview mode: 'tabs' for tabbed view, 'side-by-side' for all viewports
const viewMode = ref('tabs');

// Active viewport in tabs mode
const activeViewport = ref('desktop');

// Viewport configurations
const viewports = {
    mobile: {
        key: 'mobile',
        label: 'Mobile',
        width: 375,
        icon: 'mobile',
    },
    tablet: {
        key: 'tablet',
        label: 'Tablet',
        width: 768,
        icon: 'tablet',
    },
    desktop: {
        key: 'desktop',
        label: 'Desktop',
        width: 1280,
        icon: 'desktop',
    },
};

// Scale factor for fitting preview in container
const containerRef = ref(null);
const previewScale = ref(1);

// Calculate scale based on container width
const calculateScale = (containerWidth, viewportWidth) => {
    if (containerWidth >= viewportWidth) {
        return 1;
    }
    return Math.max(0.25, containerWidth / viewportWidth);
};

// Watch for container size changes
const updateScale = () => {
    if (!containerRef.value) return;
    const containerWidth = containerRef.value.clientWidth - 48; // Account for padding
    const viewportWidth = viewports[activeViewport.value].width;
    previewScale.value = calculateScale(containerWidth, viewportWidth);
};

// Update scale when viewport changes
watch(activeViewport, updateScale);

// Guest view mode
const isGuestView = ref(false);

const toggleGuestView = () => {
    isGuestView.value = !isGuestView.value;
    emit('view-as-guest', isGuestView.value);
};

// Computed styles for preview container
const previewContainerStyle = computed(() => {
    const viewport = viewports[activeViewport.value];
    return {
        width: `${viewport.width}px`,
        transform: `scale(${previewScale.value})`,
        transformOrigin: 'top center',
    };
});
</script>

<template>
    <aside
        v-if="isOpen"
        class="w-full lg:w-[480px] xl:w-[560px] bg-white border-l border-gray-200 flex flex-col h-full"
    >
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex-shrink-0">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-gray-900">Preview</h2>
                <button 
                    @click="$emit('close')" 
                    class="text-gray-400 hover:text-gray-600 p-1 rounded-md hover:bg-gray-100"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- View Mode Toggle -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <button
                        @click="viewMode = 'tabs'"
                        class="px-2 py-1 text-xs font-medium rounded"
                        :class="viewMode === 'tabs' 
                            ? 'bg-wedding-100 text-wedding-700' 
                            : 'text-gray-500 hover:bg-gray-100'"
                    >
                        Tabs
                    </button>
                    <button
                        @click="viewMode = 'side-by-side'"
                        class="px-2 py-1 text-xs font-medium rounded"
                        :class="viewMode === 'side-by-side' 
                            ? 'bg-wedding-100 text-wedding-700' 
                            : 'text-gray-500 hover:bg-gray-100'"
                    >
                        Lado a lado
                    </button>
                </div>

                <!-- Guest View Toggle -->
                <button
                    @click="toggleGuestView"
                    class="flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                    :class="isGuestView 
                        ? 'bg-wedding-600 text-white' 
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ isGuestView ? 'Modo Convidado' : 'Ver como convidado' }}
                </button>
            </div>

            <!-- Viewport Tabs (only in tabs mode) -->
            <div v-if="viewMode === 'tabs'" class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
                <button
                    v-for="viewport in viewports"
                    :key="viewport.key"
                    @click="activeViewport = viewport.key"
                    class="flex-1 flex items-center justify-center px-3 py-2 text-xs font-medium rounded-md transition-colors"
                    :class="activeViewport === viewport.key 
                        ? 'bg-white text-gray-900 shadow-sm' 
                        : 'text-gray-600 hover:text-gray-900'"
                >
                    <!-- Mobile Icon -->
                    <svg v-if="viewport.icon === 'mobile'" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <!-- Tablet Icon -->
                    <svg v-else-if="viewport.icon === 'tablet'" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <!-- Desktop Icon -->
                    <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {{ viewport.label }}
                    <span class="ml-1 text-gray-400">({{ viewport.width }}px)</span>
                </button>
            </div>
        </div>

        <!-- Preview Content -->
        <div 
            ref="containerRef"
            class="flex-1 overflow-auto bg-gray-100 p-6"
        >
            <!-- Tabs Mode: Single Viewport -->
            <template v-if="viewMode === 'tabs'">
                <div class="flex justify-center">
                    <div
                        class="bg-white border border-gray-300 rounded-lg shadow-lg overflow-hidden relative"
                        :style="previewContainerStyle"
                    >
                        <!-- Draft Watermark -->
                        <div
                            v-if="isDraft && !isGuestView"
                            class="absolute inset-0 pointer-events-none z-50 flex items-center justify-center"
                        >
                            <div class="absolute inset-0 bg-amber-500/5"></div>
                            <span class="text-amber-600/30 text-6xl font-bold transform -rotate-45 select-none">
                                RASCUNHO
                            </span>
                        </div>

                        <!-- Site Preview -->
                        <SitePreview
                            :content="content"
                            :theme="theme"
                            :wedding="wedding"
                            :is-edit-mode="!isGuestView"
                            :viewport="activeViewport"
                        />
                    </div>
                </div>

                <!-- Viewport Info -->
                <p class="text-center text-xs text-gray-500 mt-4">
                    {{ viewports[activeViewport].label }} - {{ viewports[activeViewport].width }}px
                    <span v-if="previewScale < 1" class="ml-1">({{ Math.round(previewScale * 100) }}% zoom)</span>
                </p>
            </template>

            <!-- Side by Side Mode: All Viewports -->
            <template v-else>
                <div class="flex flex-col space-y-6">
                    <div
                        v-for="viewport in viewports"
                        :key="viewport.key"
                        class="flex flex-col items-center"
                    >
                        <!-- Viewport Label -->
                        <div class="flex items-center mb-2 text-sm font-medium text-gray-700">
                            <svg v-if="viewport.icon === 'mobile'" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <svg v-else-if="viewport.icon === 'tablet'" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ viewport.label }} ({{ viewport.width }}px)
                        </div>

                        <!-- Preview Container -->
                        <div
                            class="bg-white border border-gray-300 rounded-lg shadow overflow-hidden relative"
                            :style="{
                                width: `${Math.min(viewport.width, 320)}px`,
                                transform: `scale(${Math.min(1, 320 / viewport.width)})`,
                                transformOrigin: 'top center',
                            }"
                        >
                            <!-- Draft Watermark -->
                            <div
                                v-if="isDraft && !isGuestView"
                                class="absolute inset-0 pointer-events-none z-50 flex items-center justify-center"
                            >
                                <div class="absolute inset-0 bg-amber-500/5"></div>
                                <span class="text-amber-600/20 text-2xl font-bold transform -rotate-45 select-none">
                                    RASCUNHO
                                </span>
                            </div>

                            <SitePreview
                                :content="content"
                                :theme="theme"
                                :wedding="wedding"
                                :is-edit-mode="!isGuestView"
                                :viewport="viewport.key"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </aside>
</template>

<style scoped>
.bg-wedding-100 {
    background-color: #f5ebe4;
}
.bg-wedding-600 {
    background-color: #a18072;
}
.text-wedding-700 {
    color: #8b6b5d;
}
</style>
