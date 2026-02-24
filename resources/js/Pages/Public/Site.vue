<script setup>
/**
 * PublicSite Page
 * 
 * Renders the public wedding site with all enabled sections.
 * Receives published_content via Inertia with placeholders already substituted.
 * 
 * @Requirements: 5.7
 */
import { computed } from 'vue';
import PublicSiteLayout from '@/Layouts/PublicSiteLayout.vue';
import PublicHeader from '@/Components/Public/PublicHeader.vue';
import PublicHero from '@/Components/Public/PublicHero.vue';
import PublicSaveTheDate from '@/Components/Public/PublicSaveTheDate.vue';
import PublicGiftRegistry from '@/Components/Public/PublicGiftRegistry.vue';
import PublicRsvp from '@/Components/Public/PublicRsvp.vue';
import PublicPhotoGallery from '@/Components/Public/PublicPhotoGallery.vue';
import PublicFooter from '@/Components/Public/PublicFooter.vue';
const FIXED_SECTION_KEYS = ['header', 'footer'];
const DEFAULT_MOVABLE_SECTION_ORDER = ['hero', 'saveTheDate', 'giftRegistry', 'rsvp', 'photoGallery'];

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    wedding: {
        type: Object,
        required: true,
    },
    content: {
        type: Object,
        required: true,
    },
    inviteTokenState: {
        type: String,
        default: null,
    },
});

// Extract sections from content
const sections = computed(() => props.content.sections || {});

// Extract theme from content
const theme = computed(() => ({
    primaryColor: '#d4a574',
    secondaryColor: '#8b7355',
    baseBackgroundColor: '#ffffff',
    surfaceBackgroundColor: '#f5ebe4',
    fontFamily: 'Georgia, serif',
    fontSize: '16px',
    ...(props.content.theme || {}),
}));

// Check if section is enabled
const isSectionEnabled = (sectionKey) => {
    return sections.value[sectionKey]?.enabled ?? false;
};

// Get section content
const getSectionContent = (sectionKey) => {
    return sections.value[sectionKey] || {};
};

// Get enabled sections map for header navigation filtering
const enabledSections = computed(() => {
    const result = {};
    Object.keys(sections.value).forEach(key => {
        result[key] = sections.value[key]?.enabled ?? false;
    });
    return result;
});

const sanitizeMovableSectionOrder = (rawOrder, availableSectionKeys) => {
    const availableMovableKeys = availableSectionKeys.filter((key) => !FIXED_SECTION_KEYS.includes(key));
    const requestedOrder = Array.isArray(rawOrder) ? rawOrder : [];

    const sanitized = [];

    requestedOrder.forEach((key) => {
        if (!availableMovableKeys.includes(key)) {
            return;
        }

        if (!sanitized.includes(key)) {
            sanitized.push(key);
        }
    });

    DEFAULT_MOVABLE_SECTION_ORDER.forEach((key) => {
        if (availableMovableKeys.includes(key) && !sanitized.includes(key)) {
            sanitized.push(key);
        }
    });

    availableMovableKeys.forEach((key) => {
        if (!sanitized.includes(key)) {
            sanitized.push(key);
        }
    });

    return sanitized;
};

const orderedSectionKeys = computed(() => {
    const availableSectionKeys = Object.keys(sections.value);
    const movableOrder = sanitizeMovableSectionOrder(props.content?.sectionOrder, availableSectionKeys);

    return [
        ...(availableSectionKeys.includes('header') ? ['header'] : []),
        ...movableOrder,
        ...(availableSectionKeys.includes('footer') ? ['footer'] : []),
    ];
});

const sectionComponentMap = {
    header: PublicHeader,
    hero: PublicHero,
    saveTheDate: PublicSaveTheDate,
    giftRegistry: PublicGiftRegistry,
    rsvp: PublicRsvp,
    photoGallery: PublicPhotoGallery,
    footer: PublicFooter,
};

const renderedSections = computed(() => {
    return orderedSectionKeys.value
        .filter((sectionKey) => isSectionEnabled(sectionKey))
        .map((sectionKey) => {
            const component = sectionComponentMap[sectionKey];
            if (!component) {
                return null;
            }

            const content = getSectionContent(sectionKey);
            const baseProps = {
                content,
                theme: theme.value,
            };

            if (sectionKey === 'header') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        enabledSections: enabledSections.value,
                    },
                };
            }

            if (sectionKey === 'saveTheDate') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        wedding: props.wedding,
                    },
                };
            }

            if (sectionKey === 'giftRegistry') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        eventId: props.wedding.id,
                        config: props.wedding.gift_registry_config,
                        isPreview: false,
                    },
                };
            }

            if (sectionKey === 'rsvp') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        wedding: props.wedding,
                        siteSlug: props.site.slug,
                        inviteTokenState: props.inviteTokenState,
                    },
                };
            }

            return {
                key: sectionKey,
                component,
                props: baseProps,
            };
        })
        .filter(Boolean);
});
</script>

<template>
    <PublicSiteLayout :site="site" :content="content" :wedding="wedding">
        <component
            :is="section.component"
            v-for="section in renderedSections"
            :key="section.key"
            v-bind="section.props"
        />
    </PublicSiteLayout>
</template>

<style scoped>
/* Page-specific styles if needed */
</style>
