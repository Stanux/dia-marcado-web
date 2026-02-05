<script setup>
/**
 * PublicSiteLayout Component
 * 
 * Clean layout for public wedding sites without platform navigation.
 * Applies site theme (colors, fonts) and dynamic meta tags.
 * 
 * @Requirements: 5.7, 18.4
 */
import { Head } from '@inertiajs/vue3';
import { computed, provide } from 'vue';

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    wedding: {
        type: Object,
        required: true,
    },
});

// Extract theme from site content
const theme = computed(() => {
    const content = props.site.published_content || props.site.draft_content || {};
    return content.theme || {
        primaryColor: '#d4a574',
        secondaryColor: '#8b7355',
        fontFamily: 'Georgia, serif',
        fontSize: '16px',
    };
});

// Extract meta from site content
const meta = computed(() => {
    const content = props.site.published_content || props.site.draft_content || {};
    return content.meta || {};
});

// Page title
const pageTitle = computed(() => {
    return meta.value.title || props.wedding.title || 'Site de Casamento';
});

// Provide theme to child components
provide('theme', theme);
provide('wedding', props.wedding);
provide('site', props.site);

// CSS custom properties for theme
const themeStyles = computed(() => ({
    '--primary-color': theme.value.primaryColor,
    '--secondary-color': theme.value.secondaryColor,
    '--font-family': theme.value.fontFamily,
    '--font-size': theme.value.fontSize,
}));
</script>

<template>
    <div class="public-site-layout" :style="themeStyles">
        <!-- Dynamic Head/Meta Tags -->
        <Head>
            <title>{{ pageTitle }}</title>
            <meta v-if="meta.description" name="description" :content="meta.description" />
            
            <!-- Open Graph -->
            <meta property="og:title" :content="pageTitle" />
            <meta v-if="meta.description" property="og:description" :content="meta.description" />
            <meta v-if="meta.ogImage" property="og:image" :content="meta.ogImage" />
            <meta property="og:type" content="website" />
            
            <!-- Canonical URL -->
            <link v-if="meta.canonical" rel="canonical" :href="meta.canonical" />
            
            <!-- Favicon (if available) -->
            <link v-if="site.favicon" rel="icon" :href="site.favicon" />
        </Head>

        <!-- Main Content -->
        <main class="public-site-content">
            <slot />
        </main>
    </div>
</template>

<style>
/* Global styles for public site */
.public-site-layout {
    font-family: var(--font-family);
    font-size: var(--font-size);
    line-height: 1.6;
    color: #333;
    min-height: 100vh;
}

.public-site-layout * {
    box-sizing: border-box;
}

/* Reset margins for public site */
.public-site-content {
    margin: 0;
    padding: 0;
}

/* Primary color utilities */
.public-site-layout .text-primary {
    color: var(--primary-color);
}

.public-site-layout .bg-primary {
    background-color: var(--primary-color);
}

.public-site-layout .border-primary {
    border-color: var(--primary-color);
}

/* Secondary color utilities */
.public-site-layout .text-secondary {
    color: var(--secondary-color);
}

.public-site-layout .bg-secondary {
    background-color: var(--secondary-color);
}

/* Smooth scrolling */
.public-site-layout {
    scroll-behavior: smooth;
}

/* Button base styles */
.public-site-layout .btn-primary {
    display: inline-block;
    padding: 12px 24px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 6px;
    font-weight: 500;
    transition: opacity 0.2s, transform 0.2s;
    text-decoration: none;
}

.public-site-layout .btn-primary:hover {
    opacity: 0.9;
    text-decoration: none;
}

.public-site-layout .btn-secondary {
    display: inline-block;
    padding: 12px 24px;
    background-color: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    border-radius: 6px;
    font-weight: 500;
    transition: background-color 0.2s, color 0.2s;
    text-decoration: none;
}

.public-site-layout .btn-secondary:hover {
    background-color: var(--primary-color);
    color: white;
    text-decoration: none;
}
</style>
