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

// Extract sections from content
const sections = computed(() => props.content.sections || {});

// Extract theme from content
const theme = computed(() => props.content.theme || {
    primaryColor: '#d4a574',
    secondaryColor: '#8b7355',
    fontFamily: 'Georgia, serif',
    fontSize: '16px',
});

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
</script>

<template>
    <div 
        class="site-preview bg-white min-h-screen"
        :style="{ 
            fontFamily: theme.fontFamily + ', serif', 
            fontSize: theme.fontSize,
            width: '100%',
            margin: 0,
            padding: 0,
            boxSizing: 'border-box',
        }"
    >
        <!-- Header Section -->
        <PublicHeader
            v-if="isSectionEnabled('header')"
            :content="getSectionContent('header')"
            :theme="theme"
            :enabled-sections="enabledSections"
            :viewport-mode="mode"
        />

        <!-- Hero Section -->
        <PublicHero
            v-if="isSectionEnabled('hero')"
            :content="getSectionContent('hero')"
            :theme="theme"
        />

        <!-- Save the Date Section -->
        <PublicSaveTheDate
            v-if="isSectionEnabled('saveTheDate')"
            :content="getSectionContent('saveTheDate')"
            :theme="theme"
            :wedding="wedding"
        />

        <!-- Gift Registry Section -->
        <PublicGiftRegistry
            v-if="isSectionEnabled('giftRegistry')"
            :content="getSectionContent('giftRegistry')"
            :theme="theme"
            :event-id="wedding.id"
            :config="wedding.gift_registry_config"
            :is-preview="true"
        />

        <!-- RSVP Section -->
        <PublicRsvp
            v-if="isSectionEnabled('rsvp')"
            :content="getSectionContent('rsvp')"
            :theme="theme"
            :wedding="wedding"
            :is-preview="true"
            :preview-scenario="rsvpPreviewScenario"
            :invite-token-state="rsvpPreviewInviteTokenState"
        />

        <!-- Photo Gallery Section -->
        <PublicPhotoGallery
            v-if="isSectionEnabled('photoGallery')"
            :content="getSectionContent('photoGallery')"
            :theme="theme"
        />

        <!-- Footer Section -->
        <PublicFooter
            v-if="isSectionEnabled('footer')"
            :content="getSectionContent('footer')"
            :theme="theme"
        />

        <!-- Empty State -->
        <div 
            v-if="!isSectionEnabled('header') && !isSectionEnabled('hero') && !isSectionEnabled('saveTheDate') && !isSectionEnabled('giftRegistry') && !isSectionEnabled('rsvp') && !isSectionEnabled('photoGallery') && !isSectionEnabled('footer')" 
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
