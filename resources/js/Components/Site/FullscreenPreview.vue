<script setup>
/**
 * Fullscreen Preview Component
 * 
 * Displays site preview in fullscreen mode with device breakpoint selector.
 * Uses the same rendering as published site.
 */
import { ref, watch } from 'vue';
import axios from 'axios';
import SitePreview from './SitePreview.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    wedding: {
        type: Object,
        default: () => ({}),
    },
    siteId: {
        type: String,
        required: true,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

// Preview mode state
const previewMode = ref('desktop'); // 'mobile', 'tablet', 'desktop'
const previewContent = ref(null);
const isLoading = ref(false);
const loadError = ref(null);

// Preview breakpoints
const previewBreakpoints = {
    mobile: { width: '375px', label: 'Mobile', icon: 'mobile' },
    tablet: { width: '768px', label: 'Tablet', icon: 'tablet' },
    desktop: { width: '100%', label: 'Web', icon: 'desktop' },
};

// Handle close
const handleClose = () => {
    emit('close');
};

// Handle mode change
const setPreviewMode = (mode) => {
    previewMode.value = mode;
};

const loadPreview = async () => {
    if (!props.siteId) return;
    isLoading.value = true;
    loadError.value = null;
    try {
        const response = await axios.get(`/admin/sites/${props.siteId}/preview`);
        previewContent.value = response.data?.data?.content || props.content;
    } catch (err) {
        loadError.value = err?.response?.data?.message || 'Erro ao carregar preview';
        previewContent.value = props.content;
    } finally {
        isLoading.value = false;
    }
};

watch(
    () => props.show,
    (show) => {
        if (show) {
            loadPreview();
        }
    }
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 bg-white overflow-auto"
            >
                <!-- Floating Top Menu -->
                <div class="fixed top-4 right-4 md:top-6 md:right-6 pointer-events-auto" style="z-index: 2147483647;">
                    <div class="rounded-xl bg-white px-2 py-2 flex flex-col items-center space-y-1 shadow-xl ring-1 ring-black/10">
                        <!-- Close Button -->
                        <button
                            @click="handleClose"
                            class="inline-flex items-center justify-center p-2 text-sm font-medium rounded-md transition-colors shadow-sm"
                            style="background-color: #b91c1c; color: #ffffff;"
                            title="Fechar"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Divider -->
                        <div class="w-6 h-px bg-gray-300"></div>

                        <!-- Device Selector -->
                        <div class="flex flex-col items-center space-y-1">
                            <!-- Desktop Button -->
                            <button
                                @click="setPreviewMode('desktop')"
                                class="p-2 text-sm font-medium rounded-md transition-colors"
                                :class="previewMode === 'desktop' 
                                    ? 'bg-wedding-600 text-white shadow-md' 
                                    : 'bg-white text-gray-700 hover:bg-gray-100 ring-1 ring-gray-200'"
                                title="Web"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </button>
                            
                            <!-- Tablet Button -->
                            <button
                                @click="setPreviewMode('tablet')"
                                class="p-2 text-sm font-medium rounded-md transition-colors"
                                :class="previewMode === 'tablet' 
                                    ? 'bg-wedding-600 text-white shadow-md' 
                                    : 'bg-white text-gray-700 hover:bg-gray-100 ring-1 ring-gray-200'"
                                title="Tablet"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </button>
                            
                            <!-- Mobile Button -->
                            <button
                                @click="setPreviewMode('mobile')"
                                class="p-2 text-sm font-medium rounded-md transition-colors"
                                :class="previewMode === 'mobile' 
                                    ? 'bg-wedding-600 text-white shadow-md' 
                                    : 'bg-white text-gray-700 hover:bg-gray-100 ring-1 ring-gray-200'"
                                title="Mobile"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview Content Area -->
                <div class="w-full min-h-screen flex justify-center" style="margin: 0; padding: 0;">
                    <div
                        class="bg-white transition-all duration-300"
                        :style="{ 
                            width: previewBreakpoints[previewMode].width,
                            maxWidth: '100%',
                            minHeight: '100vh',
                            margin: 0,
                            padding: 0,
                        }"
                    >
                        <div v-if="isLoading" class="p-8 text-center text-gray-500">
                            Carregando preview...
                        </div>
                        <div v-else-if="loadError" class="p-8 text-center text-red-600">
                            {{ loadError }}
                        </div>
                        <SitePreview
                            v-else
                            :content="previewContent || content"
                            :wedding="wedding"
                            :mode="previewMode"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.bg-wedding-600 {
    background-color: #c45a6f;
}

.bg-wedding-600:hover {
    background-color: #b9163a;
}

.text-wedding-700 {
    color: #b9163a;
}
</style>
