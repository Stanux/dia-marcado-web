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
const cardBackgroundColor = computed(() => {
    const value = style.value.cardBackgroundColor;

    if (typeof value !== 'string' || !value.trim()) {
        return '#ffffff';
    }

    return value.trim();
});

const DEFAULT_CARD_TITLE_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#1f2937',
    fontSize: 18,
    fontWeight: 600,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_CARD_DESCRIPTION_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#6b7280',
    fontSize: 14,
    fontWeight: 400,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_CARD_PRICE_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#059669',
    fontSize: 24,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_BUTTON_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#ffffff',
    fontSize: 14,
    fontWeight: 600,
    fontItalic: false,
    fontUnderline: false,
};

const resolveTypography = (value, defaults) => ({
    ...defaults,
    ...(value && typeof value === 'object' && !Array.isArray(value) ? value : {}),
});

const cardBorderColor = computed(() => {
    const value = style.value.cardBorderColor;

    if (typeof value !== 'string' || !value.trim()) {
        return '#e5e7eb';
    }

    return value.trim();
});

const buttonBackgroundColor = computed(() => {
    const value = style.value.buttonBackgroundColor;

    if (typeof value !== 'string' || !value.trim()) {
        return '#3b82f6';
    }

    return value.trim();
});

const giftAppearance = computed(() => ({
    cardBackgroundColor: cardBackgroundColor.value,
    cardBorderColor: cardBorderColor.value,
    buttonBackgroundColor: buttonBackgroundColor.value,
    cardTitleTypography: resolveTypography(props.content?.cardTitleTypography, DEFAULT_CARD_TITLE_TYPOGRAPHY),
    cardDescriptionTypography: resolveTypography(props.content?.cardDescriptionTypography, DEFAULT_CARD_DESCRIPTION_TYPOGRAPHY),
    cardPriceTypography: resolveTypography(props.content?.cardPriceTypography, DEFAULT_CARD_PRICE_TYPOGRAPHY),
    buttonTypography: resolveTypography(props.content?.buttonTypography, DEFAULT_BUTTON_TYPOGRAPHY),
}));

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
                :appearance="giftAppearance"
                :is-preview="isPreview"
            />
        </div>
    </section>
</template>

<style scoped>
/* Section styling */
</style>
