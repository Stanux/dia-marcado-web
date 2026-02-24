<script setup>
/**
 * SitePreview Component
 * 
 * Renders a complete live preview of the site based on draft content.
 * Uses the same public components as the published site to ensure 100% fidelity.
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicHeader from '@/Components/Public/PublicHeader.vue';
import PublicHero from '@/Components/Public/PublicHero.vue';
import PublicSaveTheDate from '@/Components/Public/PublicSaveTheDate.vue';
import PublicGiftRegistry from '@/Components/Public/PublicGiftRegistry.vue';
import PublicRsvp from '@/Components/Public/PublicRsvp.vue';
import PublicPhotoGallery from '@/Components/Public/PublicPhotoGallery.vue';
import PublicFooter from '@/Components/Public/PublicFooter.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'desktop',
    },
});

const page = usePage();
const FIXED_SECTION_KEYS = ['header', 'footer'];
const DEFAULT_MOVABLE_SECTION_ORDER = ['hero', 'saveTheDate', 'giftRegistry', 'rsvp', 'photoGallery'];

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

// Mock wedding data for preview (uses actual wedding data from page props if available)
const wedding = computed(() => page.props.wedding || {});

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

const rsvpPreviewScenario = computed(() => {
    return sections.value?.rsvp?.preview?.scenario || 'default';
});

const rsvpPreviewInviteTokenState = computed(() => {
    switch (rsvpPreviewScenario.value) {
        case 'valid_token':
            return 'valid';
        case 'invalid_token':
            return 'invalid';
        case 'token_limit_reached':
            return 'limit_reached';
        case 'restricted_denied':
            return 'restricted_denied';
        default:
            return null;
    }
});

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
                        viewportMode: props.mode,
                    },
                };
            }

            if (sectionKey === 'saveTheDate') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        wedding: wedding.value,
                    },
                };
            }

            if (sectionKey === 'giftRegistry') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        eventId: wedding.value.id,
                        config: wedding.value.gift_registry_config,
                        isPreview: true,
                    },
                };
            }

            if (sectionKey === 'rsvp') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        wedding: wedding.value,
                        isPreview: true,
                        previewScenario: rsvpPreviewScenario.value,
                        inviteTokenState: rsvpPreviewInviteTokenState.value,
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
    <div 
        class="site-preview bg-white min-h-screen"
        :style="{ 
            fontFamily: theme.fontFamily + ', serif', 
            fontSize: theme.fontSize,
            backgroundColor: theme.baseBackgroundColor || '#ffffff',
            width: '100%',
            margin: 0,
            padding: 0,
            boxSizing: 'border-box',
        }"
    >
        <component
            :is="section.component"
            v-for="section in renderedSections"
            :key="section.key"
            v-bind="section.props"
        />

        <!-- Empty State -->
        <div 
            v-if="renderedSections.length === 0" 
            class="flex items-center justify-center min-h-[400px] bg-gray-50"
        >
            <div class="text-center text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <p class="text-base">Ative as seções para visualizar o preview</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.site-preview {
    transition: all 0.3s ease;
    font-family: var(--font-family);
    font-size: var(--font-size);
    line-height: 1.6;
    color: #333;
}

.site-preview * {
    box-sizing: border-box;
}
</style>
