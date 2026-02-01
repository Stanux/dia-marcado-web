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

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
});

// Computed properties
const logo = computed(() => props.content.logo || { url: '', alt: '' });
const navigation = computed(() => props.content.navigation || []);
const actionButton = computed(() => props.content.actionButton || { label: '', target: '', style: 'primary' });
const style = computed(() => props.content.style || {});

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
                <div v-if="logo.url" class="flex-shrink-0">
                    <img 
                        :src="logo.url" 
                        :alt="logo.alt || 'Logo'"
                        class="h-12 w-auto object-contain"
                    />
                </div>

                <!-- Title & Subtitle -->
                <div 
                    class="flex-1 px-4"
                    :class="{ 'text-center': style.alignment === 'center' }"
                >
                    <h1 
                        v-if="content.title"
                        class="text-xl font-semibold"
                        :style="{ color: theme.primaryColor, fontFamily: theme.fontFamily }"
                    >
                        {{ content.title }}
                    </h1>
                    <p 
                        v-if="content.subtitle"
                        class="text-sm text-gray-600 mt-0.5"
                    >
                        {{ content.subtitle }}
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
