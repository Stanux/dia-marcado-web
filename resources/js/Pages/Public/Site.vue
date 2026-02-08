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
});

// Extract sections from content
const sections = computed(() => props.content.sections || {});

// Extract theme from content
const theme = computed(() => props.content.theme || {
    primaryColor: '#d4a574',
    secondaryColor: '#8b7355',
    fontFamily: 'Georgia, serif',
    fontSize: '16px',
});

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
</script>

<template>
    <PublicSiteLayout :site="site" :wedding="wedding">
        <!-- Header Section -->
        <PublicHeader
            v-if="isSectionEnabled('header')"
            :content="getSectionContent('header')"
            :theme="theme"
            :enabled-sections="enabledSections"
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
            :is-preview="false"
        />

        <!-- RSVP Section (Mockup) -->
        <PublicRsvp
            v-if="isSectionEnabled('rsvp')"
            :content="getSectionContent('rsvp')"
            :theme="theme"
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
    </PublicSiteLayout>
</template>

<style scoped>
/* Page-specific styles if needed */
</style>
