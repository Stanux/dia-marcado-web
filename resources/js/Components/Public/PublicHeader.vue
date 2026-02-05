<script setup>
/**
 * PublicHeader Component
 * 
 * Renders the public site header with navigation, logo, and action button.
 * Supports sticky positioning when configured.
 * 
 * @Requirements: 8.1, 8.5, 8.6, 8.7
 */
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    enabledSections: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

// Computed properties
const logo = computed(() => props.content.logo || { type: 'image', url: '', alt: '' });
const navigation = computed(() => {
    // Filter navigation items to only show enabled sections
    const items = props.content.navigation || [];
    if (!items.length) return [];
    
    return items.filter(item => {
        // Always show if showInMenu is false (hidden items)
        if (!item.showInMenu) return false;
        
        // Check if target section is enabled
        if (item.sectionKey && props.enabledSections) {
            return props.enabledSections[item.sectionKey] === true;
        }
        
        return true;
    });
});
const actionButton = computed(() => props.content.actionButton || { label: '', target: '', style: 'primary' });
const style = computed(() => props.content.style || {});
const titleTypography = computed(() => props.content.titleTypography || {});
const subtitleTypography = computed(() => props.content.subtitleTypography || {});

/**
 * Replace placeholders in text with actual wedding data
 */
const replacePlaceholders = (text) => {
    if (!text) return text;
    
    const wedding = page.props.wedding;
    if (!wedding) return text;
    
    let result = text;
    
    // Replace wedding date
    if (wedding.wedding_date) {
        const date = new Date(wedding.wedding_date);
        
        // {data} = formato longo: "15 de Março de 2025"
        const monthNames = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
        ];
        const longDate = `${date.getDate()} de ${monthNames[date.getMonth()]} de ${date.getFullYear()}`;
        
        // {data_curta} = formato curto: "15/03/2025"
        const shortDate = date.toLocaleDateString('pt-BR', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric' 
        });
        
        result = result.replace(/{data}/g, longDate);
        result = result.replace(/{data_curta}/g, shortDate);
    }
    
    // Replace bride and groom names
    if (wedding.bride_name) {
        result = result.replace(/{noiva}/g, wedding.bride_name);
        const firstName = wedding.bride_name.split(' ')[0];
        result = result.replace(/{primeiro_nome_noiva}/g, firstName);
    }
    
    if (wedding.groom_name) {
        result = result.replace(/{noivo}/g, wedding.groom_name);
        const firstName = wedding.groom_name.split(' ')[0];
        result = result.replace(/{primeiro_nome_noivo}/g, firstName);
    }
    
    return result;
};

// Mobile menu state
const isMobileMenuOpen = ref(false);

// Scroll state for sticky header shadow
const isScrolled = ref(false);

// Handle scroll for sticky header
const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

onMounted(() => {
    if (style.value.sticky) {
        window.addEventListener('scroll', handleScroll);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// Header styles
const headerStyles = computed(() => ({
    minHeight: style.value.height || '80px',
    backgroundColor: style.value.backgroundColor || '#ffffff',
}));

// Header classes
const headerClasses = computed(() => ({
    'sticky top-0 z-50': style.value.sticky,
    'shadow-md': style.value.sticky && isScrolled.value,
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
    const base = 'px-5 py-2.5 text-sm font-medium rounded-md transition-all duration-200';
    switch (actionButton.value.style) {
        case 'secondary':
            return `${base} border-2 hover:opacity-80`;
        case 'ghost':
            return `${base} hover:bg-gray-100`;
        default:
            return `${base} text-white hover:opacity-90`;
    }
});

// Navigate to target
const navigateTo = (target, type) => {
    if (type === 'anchor' && target.startsWith('#')) {
        const element = document.querySelector(target);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    } else if (type === 'url') {
        window.open(target, '_blank');
    } else {
        window.location.href = target;
    }
    isMobileMenuOpen.value = false;
};
</script>

<template>
    <header 
        class="border-b border-gray-100 transition-shadow duration-200"
        :class="headerClasses"
        :style="headerStyles"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
            <div class="flex items-center h-full py-4" :class="alignmentClass">
                <!-- Logo -->
                <div v-if="logo.type === 'image' && logo.url" class="flex-shrink-0">
                    <img 
                        :src="logo.url" 
                        :alt="logo.alt || 'Logo'"
                        :style="{ height: style.logoHeight || '64px' }"
                        class="w-auto object-contain"
                    />
                </div>
                
                <!-- Logo Text (Initials) -->
                <div v-else-if="logo.type === 'text' && logo.text" class="flex-shrink-0">
                    <span 
                        class="font-bold tracking-wider"
                        :style="{ 
                            color: logo.text.typography?.fontColor || theme.primaryColor || '#333333', 
                            fontFamily: logo.text.typography?.fontFamily || theme.fontFamily || 'Playfair Display',
                            fontSize: logo.text.typography?.fontSize ? `${logo.text.typography.fontSize}px` : '48px',
                            fontWeight: logo.text.typography?.fontWeight || 700,
                            fontStyle: logo.text.typography?.fontItalic ? 'italic' : 'normal',
                        }"
                    >
                        {{ (logo.text.initials?.[0] || '').toUpperCase().charAt(0) }}
                        <span class="mx-1">{{ logo.text.connector || '&' }}</span>
                        {{ (logo.text.initials?.[1] || '').toUpperCase().charAt(0) }}
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
                            color: titleTypography.fontColor || theme.primaryColor, 
                            fontFamily: titleTypography.fontFamily || theme.fontFamily,
                            fontSize: titleTypography.fontSize ? `${titleTypography.fontSize}px` : '20px',
                            fontWeight: titleTypography.fontWeight || 600,
                            fontStyle: titleTypography.fontItalic ? 'italic' : 'normal',
                            textDecoration: titleTypography.fontUnderline ? 'underline' : 'none',
                        }"
                    >
                        {{ replacePlaceholders(content.title) }}
                    </h1>
                    <p 
                        v-if="content.subtitle"
                        class="mt-0.5"
                        :style="{ 
                            color: subtitleTypography.fontColor || '#6b7280',
                            fontFamily: subtitleTypography.fontFamily || theme.fontFamily,
                            fontSize: subtitleTypography.fontSize ? `${subtitleTypography.fontSize}px` : '14px',
                            fontWeight: subtitleTypography.fontWeight || 400,
                            fontStyle: subtitleTypography.fontItalic ? 'italic' : 'normal',
                            textDecoration: subtitleTypography.fontUnderline ? 'underline' : 'none',
                        }"
                    >
                        {{ replacePlaceholders(content.subtitle) }}
                    </p>
                </div>

                <!-- Desktop Navigation -->
                <nav 
                    v-if="navigation.length > 0"
                    class="hidden md:flex items-center space-x-8"
                >
                    <a
                        v-for="(item, index) in navigation"
                        :key="index"
                        :href="item.target || '#'"
                        class="text-sm text-gray-700 hover:opacity-80 transition-opacity cursor-pointer"
                        :style="{ ':hover': { color: theme.primaryColor } }"
                        @click.prevent="navigateTo(item.target, item.type)"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <!-- Action Button -->
                <div v-if="actionButton.label" class="hidden md:block ml-6">
                    <a
                        :href="actionButton.target || '#'"
                        :class="buttonClasses"
                        :style="actionButton.style === 'primary' 
                            ? { backgroundColor: theme.primaryColor } 
                            : actionButton.style === 'secondary'
                                ? { borderColor: theme.primaryColor, color: theme.primaryColor }
                                : { color: theme.primaryColor }"
                        @click.prevent="navigateTo(actionButton.target, actionButton.type || 'anchor')"
                    >
                        <span v-if="actionButton.icon" class="mr-2">{{ actionButton.icon }}</span>
                        {{ actionButton.label }}
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button 
                    v-if="navigation.length > 0"
                    class="md:hidden ml-4 p-2 text-gray-600 hover:text-gray-900 rounded-md hover:bg-gray-100"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    aria-label="Menu"
                >
                    <svg v-if="!isMobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div 
                v-if="isMobileMenuOpen && navigation.length > 0"
                class="md:hidden border-t border-gray-100"
                :style="{ backgroundColor: style.backgroundColor || '#ffffff' }"
            >
                <nav class="px-4 py-4 space-y-2">
                    <a
                        v-for="(item, index) in navigation"
                        :key="index"
                        :href="item.target || '#'"
                        class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                        @click.prevent="navigateTo(item.target, item.type)"
                    >
                        {{ item.label }}
                    </a>
                    
                    <!-- Mobile Action Button -->
                    <a
                        v-if="actionButton.label"
                        :href="actionButton.target || '#'"
                        class="block px-4 py-3 text-center rounded-md font-medium mt-4"
                        :style="{ backgroundColor: theme.primaryColor, color: 'white' }"
                        @click.prevent="navigateTo(actionButton.target, actionButton.type || 'anchor')"
                    >
                        {{ actionButton.label }}
                    </a>
                </nav>
            </div>
        </Transition>
    </header>
</template>

<style scoped>
/* Smooth transitions */
header {
    transition: box-shadow 0.2s ease;
}
</style>
