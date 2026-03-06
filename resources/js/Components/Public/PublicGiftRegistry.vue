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
        type: String,
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

const resolveConfigObject = (value) => (
    value && typeof value === 'object' && !Array.isArray(value) ? value : {}
);

const resolveTitleStyleFromTypography = (typography = {}) => {
    const isBold = Number(typography.fontWeight) >= 700;
    const isItalic = Boolean(typography.fontItalic);

    if (isBold && isItalic) {
        return 'bold_italic';
    }

    if (isBold) {
        return 'bold';
    }

    if (isItalic) {
        return 'italic';
    }

    return 'normal';
};

// Merge config from persisted model and section content.
// Section draft/published content has priority because it reflects editor state.
const giftConfig = computed(() => {
    const mergedConfig = {
        section_title: 'Lista de Presentes',
        registry_mode: 'quantity',
        fee_modality: 'couple_pays',
        title_font_family: null,
        title_font_size: null,
        title_color: null,
        title_style: 'normal',
        title_underline: false,
        ...resolveConfigObject(props.config),
        ...resolveConfigObject(props.content?.config),
    };

    const titleTypography = resolveConfigObject(props.content?.titleTypography);

    if (titleTypography.fontFamily) {
        mergedConfig.title_font_family = titleTypography.fontFamily;
    }

    if (titleTypography.fontColor) {
        mergedConfig.title_color = titleTypography.fontColor;
    }

    if (titleTypography.fontSize !== null && titleTypography.fontSize !== undefined && titleTypography.fontSize !== '') {
        const parsedSize = Number.parseFloat(String(titleTypography.fontSize));
        if (Number.isFinite(parsedSize)) {
            mergedConfig.title_font_size = parsedSize;
        }
    }

    if (Object.prototype.hasOwnProperty.call(titleTypography, 'fontWeight') || Object.prototype.hasOwnProperty.call(titleTypography, 'fontItalic')) {
        mergedConfig.title_style = resolveTitleStyleFromTypography(titleTypography);
    }

    if (Object.prototype.hasOwnProperty.call(titleTypography, 'fontUnderline')) {
        mergedConfig.title_underline = Boolean(titleTypography.fontUnderline);
    }

    return mergedConfig;
});
</script>

<template>
    <section 
        class="py-16 sm:py-20 px-4"
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
