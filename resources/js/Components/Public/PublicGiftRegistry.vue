<script setup>
/**
 * PublicGiftRegistry Component
 * 
 * Renders the Gift Registry section with full functionality.
 * Integrates GiftCatalogGrid for displaying and purchasing gifts.
 * 
 * @Requirements: 11.1, 11.2, 11.3, 11.4
 */
import { computed } from 'vue';
import GiftCatalogGrid from './GiftCatalogGrid.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    eventId: {
        type: Number,
        required: true,
    },
    config: {
        type: Object,
        default: () => null,
    },
    isPreview: {
        type: Boolean,
        default: false,
    },
});

// Computed properties
const style = computed(() => props.content.style || {});
const resolveBaseBackgroundColor = (value) => {
    const fallback = props.theme?.baseBackgroundColor || '#ffffff';

    if (typeof value !== 'string' || !value.trim()) {
        return fallback;
    }
    
    return value.trim();
};

const sectionBackgroundColor = computed(() => resolveBaseBackgroundColor(style.value.backgroundColor));

// Default config if not provided
const giftConfig = computed(() => {
    return props.config || {
        section_title: 'Lista de Presentes',
        fee_modality: 'couple_pays',
        title_font_family: null,
        title_font_size: null,
        title_color: null,
        title_style: 'normal',
    };
});
</script>

<template>
    <section 
        class="py-20 px-4"
        :style="{ backgroundColor: sectionBackgroundColor }"
        id="gift-registry"
    >
        <div class="max-w-6xl mx-auto">
            <!-- Gift Catalog Grid -->
            <GiftCatalogGrid
                :event-id="eventId"
                :config="giftConfig"
                :is-preview="isPreview"
            />
        </div>
    </section>
</template>

<style scoped>
/* Section styling */
</style>
