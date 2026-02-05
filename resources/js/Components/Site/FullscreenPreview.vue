<script setup>
/**
 * Fullscreen Preview Component
 * 
 * Displays site preview in fullscreen mode with device breakpoint selector
 */
import { ref } from 'vue';
import SitePreview from './SitePreview.vue';

const props = defineProps({
    content: {
        type: Object,
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
                class="fixed inset-0 z-50 bg-gray-100 overflow-auto"
            >
                <!-- Floating Top Menu -->
                <div class="fixed top-12 right-6 z-10">
                    <div class="bg-transparent rounded-lg px-2 py-2 flex flex-col items-center space-y-1">
                        <!-- Close Button -->
                        <button
                            @click="handleClose"
                            class="p-2 text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 rounded-md transition-colors"
                            title="Fechar"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Divider -->
                        <div class="w-6 h-px bg-gray-500"></div>

                        <!-- Device Selector -->
                        <div class="flex flex-col items-center space-y-1">
                            <!-- Desktop Button -->
                            <button
                                @click="setPreviewMode('desktop')"
                                class="p-2 text-sm font-medium rounded-md transition-colors"
                                :class="previewMode === 'desktop' 
                                    ? 'bg-wedding-600 text-white shadow-md' 
                                    : 'bg-gray-700 text-white hover:bg-gray-600'"
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
                                    : 'bg-gray-700 text-white hover:bg-gray-600'"
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
                                    : 'bg-gray-700 text-white hover:bg-gray-600'"
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
                <div class="w-full min-h-screen">
                    <div
                        class="mx-auto bg-white shadow-lg transition-all duration-300"
                        :style="{ 
                            width: previewBreakpoints[previewMode].width,
                            maxWidth: '100%',
                            minHeight: '100vh'
                        }"
                    >
                        <SitePreview 
                            :content="content" 
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
    background-color: #a18072;
}

.bg-wedding-600:hover {
    background-color: #8b6b5d;
}

.text-wedding-700 {
    color: #8b6b5d;
}
</style>
