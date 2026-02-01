<script setup>
/**
 * GiftRegistryPreview Component
 * 
 * Renders the Gift Registry section preview (mockup).
 * Shows placeholder content until the Gift Catalog module is implemented.
 * 
 * @Requirements: 11.1, 11.2, 11.3, 11.4
 */
import { computed } from 'vue';

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

// Mock gift items for preview
const mockGifts = [
    { id: 1, name: 'Jogo de Panelas', price: 'R$ 450,00', image: null, progress: 75 },
    { id: 2, name: 'Liquidificador', price: 'R$ 280,00', image: null, progress: 100 },
    { id: 3, name: 'Jogo de Cama', price: 'R$ 350,00', image: null, progress: 30 },
    { id: 4, name: 'Cafeteira', price: 'R$ 220,00', image: null, progress: 0 },
];
</script>

<template>
    <section 
        class="py-16 px-4 relative"
        :style="{ backgroundColor: style.backgroundColor || '#ffffff' }"
        id="gift-registry"
    >
        <div class="max-w-6xl mx-auto">
            <!-- Section Header -->
            <div class="text-center mb-12">
                <h2 
                    class="text-2xl md:text-3xl font-bold mb-4"
                    :style="{ color: theme.primaryColor, fontFamily: theme.fontFamily }"
                >
                    {{ content.title || 'Lista de Presentes' }}
                </h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    {{ content.description || 'Em breve você poderá ver nossa lista de presentes e contribuir para nossa nova vida juntos.' }}
                </p>
            </div>

            <!-- Mock Gift Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="gift in mockGifts"
                    :key="gift.id"
                    class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 opacity-60"
                >
                    <!-- Gift Image Placeholder -->
                    <div class="aspect-square bg-gray-100 flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                    </div>
                    
                    <!-- Gift Info -->
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 mb-1">{{ gift.name }}</h3>
                        <p class="text-sm text-gray-500 mb-3">{{ gift.price }}</p>
                        
                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                            <div 
                                class="h-2 rounded-full transition-all"
                                :style="{ 
                                    width: `${gift.progress}%`,
                                    backgroundColor: theme.primaryColor 
                                }"
                            />
                        </div>
                        <p class="text-xs text-gray-400">{{ gift.progress }}% contribuído</p>
                    </div>
                </div>
            </div>

            <!-- Coming Soon Overlay -->
            <div class="mt-8 text-center">
                <div class="inline-flex items-center px-4 py-2 bg-amber-50 border border-amber-200 rounded-full">
                    <svg class="w-5 h-5 text-amber-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-amber-700 font-medium">
                        Módulo em desenvolvimento - Em breve!
                    </span>
                </div>
            </div>
        </div>

        <!-- Edit Mode Indicator -->
        <div 
            v-if="isEditMode"
            class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-0.5 rounded z-10"
        >
            Lista de Presentes
        </div>
    </section>
</template>

<style scoped>
/* Mockup styling */
.mock-item {
    filter: grayscale(30%);
}
</style>
