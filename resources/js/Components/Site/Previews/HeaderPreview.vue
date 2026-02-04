<script setup>
/**
 * HeaderPreview Component
 * 
 * Renders the header section preview with logo, title, navigation, and action button.
 * 
 * @Requirements: 8.1, 8.5, 8.6, 8.7
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
const logo = computed(() => props.content.logo || { url: '', alt: '' });
const logoType = computed(() => logo.value.type || 'image');
const logoText = computed(() => logo.value.text || { initials: ['', ''], connector: '&' });
const logoTextTypography = computed(() => logoText.value.typography || {
    fontFamily: 'Playfair Display',
    fontColor: '#333333',
    fontSize: 32,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
});
const titleTypography = computed(() => props.content.titleTypography || {
    fontFamily: 'Playfair Display',
    fontColor: '#333333',
    fontSize: 48,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
});
const subtitleTypography = computed(() => props.content.subtitleTypography || {
    fontFamily: 'Montserrat',
    fontColor: '#666666',
    fontSize: 24,
    fontWeight: 400,
    fontItalic: true,
    fontUnderline: false,
});
const navigation = computed(() => {
    const navItems = props.content.navigation || [];
    // Filter to only show items where showInMenu is true
    return navItems.filter(item => item.showInMenu === true);
});
const actionButton = computed(() => props.content.actionButton || { label: '', target: '', style: 'primary' });
const style = computed(() => props.content.style || {});

// Section mapping helpers
const SECTION_IDS = {
    hero: 'hero',
    saveTheDate: 'save-the-date',
    giftRegistry: 'lista-presentes',
    rsvp: 'confirmar-presenca',
    photoGallery: 'galeria',
};

const SECTION_LABELS = {
    hero: 'Hero',
    saveTheDate: 'Save the Date',
    giftRegistry: 'Lista de Presentes',
    rsvp: 'Confirme Presença',
    photoGallery: 'Galeria de Fotos',
};

const getSectionAnchor = (sectionKey) => {
    return `#${SECTION_IDS[sectionKey] || ''}`;
};

const getSectionLabel = (sectionKey) => {
    return SECTION_LABELS[sectionKey] || sectionKey;
};

// Header styles
const headerStyles = computed(() => ({
    height: style.value.height || '80px',
    backgroundColor: style.value.backgroundColor || '#ffffff',
    position: style.value.sticky ? 'sticky' : 'relative',
    top: style.value.sticky ? '0' : 'auto',
    zIndex: style.value.sticky ? '100' : 'auto',
}));

// Alignment classes
const alignmentClass = computed(() => {
    switch (style.value.alignment) {
        case 'left': return 'justify-start';
        case 'right': return 'justify-end';
        default: return 'justify-center';
    }
});

// Button style classes
const buttonClasses = computed(() => {
    const baseClasses = 'px-4 py-2 text-sm font-medium rounded-md transition-colors';
    switch (actionButton.value.style) {
        case 'secondary':
            return `${baseClasses} border border-current text-primary hover:bg-primary hover:text-white`;
        case 'ghost':
            return `${baseClasses} text-primary hover:bg-gray-100`;
        default:
            return `${baseClasses} bg-primary text-white hover:opacity-90`;
    }
});

// Mobile menu state
const isMobileMenuOpen = computed(() => false); // Static for preview
</script>

<template>
    <header 
        class="border-b border-gray-100"
        :style="headerStyles"
    >
        <div class="h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-full flex items-center" :class="alignmentClass">
                <!-- Logo -->
                <div v-if="logoType === 'image' && logo.url" class="flex-shrink-0">
                    <img 
                        :src="logo.url" 
                        :alt="logo.alt || 'Logo'"
                        class="h-10 w-auto object-contain"
                    />
                </div>

                <!-- Logo Texto (Iniciais) -->
                <div 
                    v-else-if="logoType === 'text' && (logoText.initials[0] || logoText.initials[1])"
                    class="flex-shrink-0"
                >
                    <span
                        :style="{
                            fontFamily: logoTextTypography.fontFamily,
                            color: logoTextTypography.fontColor,
                            fontSize: `${logoTextTypography.fontSize}px`,
                            fontWeight: logoTextTypography.fontWeight,
                            fontStyle: logoTextTypography.fontItalic ? 'italic' : 'normal',
                            textDecoration: logoTextTypography.fontUnderline ? 'underline' : 'none',
                        }"
                    >
                        {{ (logoText.initials[0] || '').toUpperCase() }} {{ logoText.connector }} {{ (logoText.initials[1] || '').toUpperCase() }}
                    </span>
                </div>

                <!-- Title & Subtitle -->
                <div 
                    class="flex-1 px-4"
                    :class="{ 'text-center': style.alignment === 'center' }"
                >
                    <h1 
                        v-if="content.title"
                        :style="{
                            fontFamily: titleTypography.fontFamily,
                            color: titleTypography.fontColor,
                            fontSize: `${titleTypography.fontSize}px`,
                            fontWeight: titleTypography.fontWeight,
                            fontStyle: titleTypography.fontItalic ? 'italic' : 'normal',
                            textDecoration: titleTypography.fontUnderline ? 'underline' : 'none',
                        }"
                    >
                        {{ content.title }}
                    </h1>
                    <p 
                        v-if="content.subtitle"
                        :style="{
                            fontFamily: subtitleTypography.fontFamily,
                            color: subtitleTypography.fontColor,
                            fontSize: `${subtitleTypography.fontSize}px`,
                            fontWeight: subtitleTypography.fontWeight,
                            fontStyle: subtitleTypography.fontItalic ? 'italic' : 'normal',
                            textDecoration: subtitleTypography.fontUnderline ? 'underline' : 'none',
                        }"
                    >
                        {{ content.subtitle }}
                    </p>
                    <p 
                        v-if="content.showDate"
                        class="text-xs text-gray-500 mt-0.5"
                    >
                        {data}
                    </p>
                </div>

                <!-- Desktop Navigation -->
                <nav 
                    v-if="navigation.length > 0 && viewport !== 'mobile'"
                    class="hidden md:flex items-center space-x-6"
                >
                    <a
                        v-for="(item, index) in navigation"
                        :key="index"
                        :href="getSectionAnchor(item.sectionKey)"
                        class="text-sm text-gray-700 hover:text-primary transition-colors"
                    >
                        {{ item.label || getSectionLabel(item.sectionKey) }}
                    </a>
                </nav>

                <!-- Action Button -->
                <div v-if="actionButton.label" class="ml-4">
                    <a
                        :href="actionButton.target || '#'"
                        :class="buttonClasses"
                        :style="actionButton.style === 'primary' ? { backgroundColor: theme.primaryColor } : {}"
                    >
                        {{ actionButton.label }}
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button 
                    v-if="viewport === 'mobile' && navigation.length > 0"
                    class="ml-4 p-2 text-gray-600 hover:text-gray-900 md:hidden"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Edit Mode Indicator -->
        <div 
            v-if="isEditMode"
            class="absolute top-0 left-0 bg-blue-500 text-white text-xs px-2 py-0.5 rounded-br"
        >
            Header
        </div>
    </header>
</template>

<style scoped>
.text-primary {
    color: var(--primary-color, #d4a574);
}
.bg-primary {
    background-color: var(--primary-color, #d4a574);
}
.hover\:text-primary:hover {
    color: var(--primary-color, #d4a574);
}
.hover\:bg-primary:hover {
    background-color: var(--primary-color, #d4a574);
}
</style>
