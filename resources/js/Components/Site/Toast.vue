<script setup>
/**
 * Toast Component
 * 
 * Simple toast notification for success/error messages
 */
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'success', // 'success', 'error', 'warning', 'info'
    },
    message: {
        type: String,
        required: true,
    },
    duration: {
        type: Number,
        default: 3000,
    },
});

const emit = defineEmits(['close']);

const visible = ref(false);
let timeoutId = null;

const close = () => {
    visible.value = false;
    setTimeout(() => {
        emit('close');
    }, 300);
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        visible.value = true;
        
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        
        if (props.duration > 0) {
            timeoutId = setTimeout(() => {
                close();
            }, props.duration);
        }
    }
});

onMounted(() => {
    if (props.show) {
        visible.value = true;
        
        if (props.duration > 0) {
            timeoutId = setTimeout(() => {
                close();
            }, props.duration);
        }
    }
});

const iconColors = {
    success: 'text-green-400',
    error: 'text-red-400',
    warning: 'text-amber-400',
    info: 'text-blue-400',
};

const bgColors = {
    success: 'bg-green-50',
    error: 'bg-red-50',
    warning: 'bg-amber-50',
    info: 'bg-blue-50',
};

const textColors = {
    success: 'text-green-800',
    error: 'text-red-800',
    warning: 'text-amber-800',
    info: 'text-blue-800',
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="visible"
                class="fixed top-4 right-4 z-50 max-w-sm w-full pointer-events-auto"
            >
                <div
                    class="rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden"
                    :class="bgColors[type]"
                >
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <!-- Success Icon -->
                                <svg
                                    v-if="type === 'success'"
                                    class="h-6 w-6"
                                    :class="iconColors[type]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <!-- Error Icon -->
                                <svg
                                    v-else-if="type === 'error'"
                                    class="h-6 w-6"
                                    :class="iconColors[type]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <!-- Warning Icon -->
                                <svg
                                    v-else-if="type === 'warning'"
                                    class="h-6 w-6"
                                    :class="iconColors[type]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <!-- Info Icon -->
                                <svg
                                    v-else
                                    class="h-6 w-6"
                                    :class="iconColors[type]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 w-0 flex-1 pt-0.5">
                                <p class="text-sm font-medium" :class="textColors[type]">
                                    {{ message }}
                                </p>
                            </div>
                            <div class="ml-4 flex flex-shrink-0">
                                <button
                                    @click="close"
                                    class="inline-flex rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    :class="textColors[type]"
                                >
                                    <span class="sr-only">Fechar</span>
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
