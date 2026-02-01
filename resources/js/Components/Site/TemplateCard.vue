<script setup>
/**
 * TemplateCard Component
 * 
 * Displays a template card with thumbnail, name, description,
 * and action buttons for preview and selection.
 * 
 * @Requirements: 15.1
 */
import { computed } from 'vue';

const props = defineProps({
    template: {
        type: Object,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['preview', 'select']);

// Determine badge type based on template ownership
const badgeType = computed(() => {
    if (props.template.is_system) {
        return { label: 'Sistema', class: 'bg-blue-100 text-blue-800' };
    }
    if (props.template.is_public) {
        return { label: 'Público', class: 'bg-green-100 text-green-800' };
    }
    return { label: 'Privado', class: 'bg-gray-100 text-gray-800' };
});

// Default thumbnail placeholder
const thumbnailUrl = computed(() => {
    return props.template.thumbnail || '/images/template-placeholder.svg';
});

const handlePreview = () => {
    emit('preview', props.template);
};

const handleSelect = () => {
    emit('select', props.template);
};
</script>

<template>
    <div
        class="group relative bg-white rounded-lg border-2 transition-all duration-200 overflow-hidden"
        :class="[
            selected 
                ? 'border-wedding-500 ring-2 ring-wedding-200' 
                : 'border-gray-200 hover:border-wedding-300 hover:shadow-md'
        ]"
    >
        <!-- Thumbnail -->
        <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
            <img
                :src="thumbnailUrl"
                :alt="template.name"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                @error="$event.target.src = '/images/template-placeholder.svg'"
            />
            
            <!-- Hover Overlay with Actions -->
            <div 
                class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-3"
            >
                <button
                    @click.stop="handlePreview"
                    class="px-4 py-2 bg-white text-gray-800 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview
                </button>
                <button
                    @click.stop="handleSelect"
                    class="px-4 py-2 bg-wedding-600 text-white text-sm font-medium rounded-md hover:bg-wedding-700 transition-colors"
                >
                    Usar
                </button>
            </div>

            <!-- Badge -->
            <span
                :class="[
                    'absolute top-2 right-2 px-2 py-1 text-xs font-medium rounded-full',
                    badgeType.class
                ]"
            >
                {{ badgeType.label }}
            </span>

            <!-- Selected Indicator -->
            <div
                v-if="selected"
                class="absolute top-2 left-2 w-6 h-6 bg-wedding-500 rounded-full flex items-center justify-center"
            >
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4">
            <h3 class="font-medium text-gray-900 truncate">
                {{ template.name }}
            </h3>
            <p 
                v-if="template.description" 
                class="mt-1 text-sm text-gray-500 line-clamp-2"
            >
                {{ template.description }}
            </p>
            <p 
                v-else 
                class="mt-1 text-sm text-gray-400 italic"
            >
                Sem descrição
            </p>
        </div>
    </div>
</template>

<style scoped>
.bg-wedding-500 {
    background-color: #a18072;
}
.bg-wedding-600 {
    background-color: #8b6b5d;
}
.bg-wedding-700 {
    background-color: #6b5347;
}
.border-wedding-300 {
    border-color: #c9b8ae;
}
.border-wedding-500 {
    border-color: #a18072;
}
.ring-wedding-200 {
    --tw-ring-color: rgba(161, 128, 114, 0.3);
}
.text-wedding-600 {
    color: #8b6b5d;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
