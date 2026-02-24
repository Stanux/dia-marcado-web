<script setup>
/**
 * PublicHeader Component
 * 
 * Renders the public site header with navigation, logo, and action button.
 * Supports sticky positioning when configured.
 * 
 * @Requirements: 8.1, 8.5, 8.6, 8.7
 */
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
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
    viewportMode: {
        type: String,
        default: 'auto',
    },
});

const page = usePage();

const SECTION_ANCHORS_BY_KEY = {
    hero: 'hero',
    saveTheDate: 'save-the-date',
    giftRegistry: 'gift-registry',
    rsvp: 'rsvp',
    photoGallery: 'photo-gallery',
};

const LEGACY_ANCHOR_ALIASES = {
    'lista-presentes': 'gift-registry',
    'confirmar-presenca': 'rsvp',
    galeria: 'photo-gallery',
};

const normalizeTarget = (rawTarget) => {
    if (typeof rawTarget !== 'string') {
        return '';
    }

    const target = rawTarget.trim();

    if (!target) {
        return '';
    }

    if (!target.startsWith('#')) {
        return target;
    }

    const rawAnchorId = target.slice(1);
    if (!rawAnchorId) {
        return '';
    }

    const normalizedAnchorId = LEGACY_ANCHOR_ALIASES[rawAnchorId] || rawAnchorId;
    return `#${normalizedAnchorId}`;
};

const resolveTarget = (item = {}) => {
    const targetFromConfig = normalizeTarget(item.target);
    if (targetFromConfig) {
        return targetFromConfig;
    }

    if (item.sectionKey && SECTION_ANCHORS_BY_KEY[item.sectionKey]) {
        return `#${SECTION_ANCHORS_BY_KEY[item.sectionKey]}`;
    }

    return '';
};

const resolveType = (item = {}, target = '') => {
    const explicitType = typeof item.type === 'string' ? item.type.trim().toLowerCase() : '';
    if (explicitType === 'anchor' || explicitType === 'url') {
        return explicitType;
    }

    if (target.startsWith('#')) {
        return 'anchor';
    }

    return '';
};

// Computed properties
const logo = computed(() => props.content.logo || { type: 'image', url: '', alt: '' });
const navigation = computed(() => {
    // Filter navigation items to only show enabled sections
    const items = props.content.navigation || [];
    if (!items.length) return [];
    
    return items
        .filter(item => {
            // Always show if showInMenu is false (hidden items)
            if (!item.showInMenu) return false;

            // Check if target section is enabled
            if (item.sectionKey && props.enabledSections) {
                return props.enabledSections[item.sectionKey] === true;
            }

            return true;
        })
        .map(item => {
            const target = resolveTarget(item);

            return {
                ...item,
                target,
                type: resolveType(item, target),
            };
        })
        .filter(item => item.target);
});
const actionButton = computed(() => {
    const button = props.content.actionButton || { label: '', target: '', style: 'primary' };
    const target = resolveTarget(button);

    return {
        ...button,
        target,
        type: resolveType(button, target),
    };
});
const style = computed(() => props.content.style || {});
const titleTypography = computed(() => props.content.titleTypography || {});
const subtitleTypography = computed(() => props.content.subtitleTypography || {});
const menuTypography = computed(() => props.content.menuTypography || {});
const menuHoverTypography = computed(() => props.content.menuHoverTypography || {});

const clampRgbChannel = (value) => {
    const parsed = Number.parseInt(value, 10);

    if (Number.isNaN(parsed)) {
        return 0;
    }

    return Math.max(0, Math.min(255, parsed));
};

const getThemeBaseBackgroundColor = () => props.theme?.baseBackgroundColor || '#ffffff';

const resolveHeaderBackgroundColor = (value, fallback) => {
    if (typeof value !== 'string' || !value.trim()) {
        return fallback;
    }

    const normalized = value.trim();

    if (normalized.toLowerCase() === 'transparent') {
        return fallback;
    }

    if (/^#[0-9a-f]{6}$/i.test(normalized) || /^#[0-9a-f]{3}$/i.test(normalized)) {
        const normalizedHex = normalized.toLowerCase();
        if (normalizedHex === '#ffffff' || normalizedHex === '#fff') {
            return fallback;
        }

        return normalized;
    }

    const rgbaMatch = normalized.match(/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([+-]?\d*\.?\d+)\s*\)$/i);
    if (rgbaMatch) {
        const alpha = Number.parseFloat(rgbaMatch[4]);

        if (Number.isNaN(alpha) || alpha <= 0.01) {
            return fallback;
        }

        const r = clampRgbChannel(rgbaMatch[1]);
        const g = clampRgbChannel(rgbaMatch[2]);
        const b = clampRgbChannel(rgbaMatch[3]);
        const a = Math.max(0, Math.min(1, alpha));

        if (r === 255 && g === 255 && b === 255 && a >= 0.99) {
            return fallback;
        }

        return `rgba(${r}, ${g}, ${b}, ${a})`;
    }

    const rgbMatch = normalized.match(/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i);
    if (rgbMatch) {
        const r = clampRgbChannel(rgbMatch[1]);
        const g = clampRgbChannel(rgbMatch[2]);
        const b = clampRgbChannel(rgbMatch[3]);

        if (r === 255 && g === 255 && b === 255) {
            return fallback;
        }

        return `rgb(${r}, ${g}, ${b})`;
    }

    return fallback;
};

const safeHeaderBackgroundColor = computed(() => resolveHeaderBackgroundColor(style.value.backgroundColor, getThemeBaseBackgroundColor()));
const isWideScreen = ref(true);
const isMobileScreen = ref(false);
let desktopMediaQuery = null;
let mobileMediaQuery = null;

const parseBoolean = (value) => {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value === 1;
    }

    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();
        return ['1', 'true', 'on', 'yes', 'sim'].includes(normalized);
    }

    return false;
};

const parsePixels = (value, fallback) => {
    const source = value ?? fallback;

    if (typeof source === 'number' && Number.isFinite(source)) {
        return source;
    }

    if (typeof source === 'string') {
        const parsed = Number.parseFloat(source);
        if (Number.isFinite(parsed)) {
            return parsed;
        }
    }

    return fallback;
};

const toCssSize = (value, fallback = '64px') => {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return `${value}px`;
    }

    if (typeof value === 'string' && value.trim()) {
        const normalized = value.trim();
        if (/^\d+(\.\d+)?$/.test(normalized)) {
            return `${normalized}px`;
        }
        return normalized;
    }

    return fallback;
};

const responsiveSize = (value, fallback, min, viewportFactor = 5.5) => {
    const max = Math.max(min, parsePixels(value, fallback));
    return `clamp(${min}px, ${viewportFactor}vw, ${max}px)`;
};

const forceCompactNavigation = computed(() => ['mobile', 'tablet'].includes(props.viewportMode));
const hideHeaderTextOnMobile = computed(() => props.viewportMode === 'mobile' || isMobileScreen.value);
const isStickyEnabled = computed(() => parseBoolean(style.value.sticky));
const showDesktopNavigation = computed(
    () => navigation.value.length > 0 && !forceCompactNavigation.value && isWideScreen.value
);
const showMobileNavigation = computed(
    () => navigation.value.length > 0 && (forceCompactNavigation.value || !isWideScreen.value)
);

const headerLogoStyle = computed(() => ({
    height: `clamp(36px, 12vw, ${toCssSize(style.value.logoHeight, '64px')})`,
    maxWidth: '100%',
}));

const logoTextStyle = computed(() => ({
    color: logo.value.text?.typography?.fontColor || props.theme.primaryColor || '#333333',
    fontFamily: logo.value.text?.typography?.fontFamily || props.theme.fontFamily || 'Playfair Display',
    fontSize: responsiveSize(logo.value.text?.typography?.fontSize, 48, 24, 8),
    fontWeight: logo.value.text?.typography?.fontWeight || 700,
    fontStyle: logo.value.text?.typography?.fontItalic ? 'italic' : 'normal',
    lineHeight: 1.15,
    whiteSpace: 'nowrap',
}));

const titleStyle = computed(() => ({
    color: titleTypography.value.fontColor || props.theme.primaryColor,
    fontFamily: titleTypography.value.fontFamily || props.theme.fontFamily,
    fontSize: responsiveSize(titleTypography.value.fontSize, 20, 16, 6),
    fontWeight: titleTypography.value.fontWeight || 600,
    fontStyle: titleTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: titleTypography.value.fontUnderline ? 'underline' : 'none',
    overflowWrap: 'anywhere',
    wordBreak: 'break-word',
}));

const subtitleStyle = computed(() => ({
    color: subtitleTypography.value.fontColor || '#6b7280',
    fontFamily: subtitleTypography.value.fontFamily || props.theme.fontFamily,
    fontSize: responsiveSize(subtitleTypography.value.fontSize, 14, 12, 4.5),
    fontWeight: subtitleTypography.value.fontWeight || 400,
    fontStyle: subtitleTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: subtitleTypography.value.fontUnderline ? 'underline' : 'none',
    overflowWrap: 'anywhere',
    wordBreak: 'break-word',
}));

const menuLinkStyle = computed(() => {
    const normal = menuTypography.value;
    const hover = menuHoverTypography.value;
    const fallbackFamily = props.theme.fontFamily || 'Montserrat';
    const fallbackColor = '#374151';
    const fallbackHoverColor = props.theme.primaryColor || '#d4a574';

    return {
        '--dm-menu-font-family': normal.fontFamily || fallbackFamily,
        '--dm-menu-font-color': normal.fontColor || fallbackColor,
        '--dm-menu-font-size': `${parsePixels(normal.fontSize, 14)}px`,
        '--dm-menu-font-weight': String(normal.fontWeight || 400),
        '--dm-menu-font-style': normal.fontItalic ? 'italic' : 'normal',
        '--dm-menu-font-decoration': normal.fontUnderline ? 'underline' : 'none',
        '--dm-menu-hover-font-family': hover.fontFamily || normal.fontFamily || fallbackFamily,
        '--dm-menu-hover-font-color': hover.fontColor || fallbackHoverColor,
        '--dm-menu-hover-font-size': `${parsePixels(hover.fontSize, parsePixels(normal.fontSize, 14))}px`,
        '--dm-menu-hover-font-weight': String(hover.fontWeight || normal.fontWeight || 500),
        '--dm-menu-hover-font-style': hover.fontItalic ? 'italic' : 'normal',
        '--dm-menu-hover-font-decoration': hover.fontUnderline ? 'underline' : 'none',
    };
});

const syncViewportBreakpoint = () => {
    if (typeof window === 'undefined') {
        return;
    }

    isWideScreen.value = window.matchMedia('(min-width: 1024px)').matches;
    isMobileScreen.value = window.matchMedia('(max-width: 767px)').matches;
};

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
        
        // {data_extenso} = formato longo: "15 de Março de 2025"
        const monthNames = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
        ];
        const longDate = `${date.getDate()} de ${monthNames[date.getMonth()]} de ${date.getFullYear()}`;
        
        // {data_simples} = formato curto: "15/03/2025"
        const shortDate = date.toLocaleDateString('pt-BR', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric' 
        });
        
        result = result.replace(/{data_extenso}/g, longDate);
        result = result.replace(/{data_simples}/g, shortDate);
        // Compatibilidade retroativa
        result = result.replace(/{data}/g, longDate);
        result = result.replace(/{data_curta}/g, shortDate);
    }
    
    // Replace bride and groom names
    if (wedding.bride_name) {
        result = result.replace(/{nome_2}/g, wedding.bride_name);
        result = result.replace(/{noiva}/g, wedding.bride_name);
        const firstName = wedding.bride_name.split(' ')[0];
        result = result.replace(/{primeiro_nome_2}/g, firstName);
        result = result.replace(/{primeiro_nome_noiva}/g, firstName);
    }
    
    if (wedding.groom_name) {
        result = result.replace(/{nome_1}/g, wedding.groom_name);
        result = result.replace(/{noivo}/g, wedding.groom_name);
        const firstName = wedding.groom_name.split(' ')[0];
        result = result.replace(/{primeiro_nome_1}/g, firstName);
        result = result.replace(/{primeiro_nome_noivo}/g, firstName);
    }
    
    return result;
};

// Mobile menu state
const isMobileMenuOpen = ref(false);
const headerRef = ref(null);

// Scroll state for sticky header shadow
const isScrolled = ref(false);

// Handle scroll for sticky header
const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

onMounted(() => {
    syncViewportBreakpoint();

    if (typeof window !== 'undefined') {
        desktopMediaQuery = window.matchMedia('(min-width: 1024px)');
        desktopMediaQuery.addEventListener('change', syncViewportBreakpoint);
        mobileMediaQuery = window.matchMedia('(max-width: 767px)');
        mobileMediaQuery.addEventListener('change', syncViewportBreakpoint);
    }

    if (isStickyEnabled.value) {
        window.addEventListener('scroll', handleScroll);
    }
});

onUnmounted(() => {
    if (desktopMediaQuery) {
        desktopMediaQuery.removeEventListener('change', syncViewportBreakpoint);
    }
    if (mobileMediaQuery) {
        mobileMediaQuery.removeEventListener('change', syncViewportBreakpoint);
    }

    window.removeEventListener('scroll', handleScroll);
});

watch(showMobileNavigation, (canShowMobileNavigation) => {
    if (!canShowMobileNavigation) {
        isMobileMenuOpen.value = false;
    }
});

watch(isStickyEnabled, (enabled) => {
    if (enabled) {
        window.addEventListener('scroll', handleScroll);
    } else {
        window.removeEventListener('scroll', handleScroll);
        isScrolled.value = false;
    }
});

// Header styles
const headerStyles = computed(() => ({
    minHeight: style.value.height || '80px',
    backgroundColor: safeHeaderBackgroundColor.value,
    position: isStickyEnabled.value ? 'sticky' : 'relative',
    top: isStickyEnabled.value ? '0px' : undefined,
    zIndex: isStickyEnabled.value ? 50 : undefined,
}));

const headerRowStyles = computed(() => ({
    minHeight: style.value.height || '80px',
}));

const headerRowClass = computed(() => {
    if (hideHeaderTextOnMobile.value) {
        return 'justify-between';
    }

    return alignmentClass.value;
});

// Header classes
const headerClasses = computed(() => ({
    'shadow-md': isStickyEnabled.value && isScrolled.value,
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

const getStickyOffset = () => {
    if (!isStickyEnabled.value) {
        return 0;
    }

    return (headerRef.value?.offsetHeight || 0) + 8;
};

const scrollToAnchor = (targetSelector) => {
    const element = document.querySelector(targetSelector);

    if (!element) {
        return;
    }

    const offset = getStickyOffset();
    const targetTop = window.scrollY + element.getBoundingClientRect().top - offset;

    window.scrollTo({
        top: Math.max(0, targetTop),
        behavior: 'smooth',
    });
};

// Navigate to target
const navigateTo = (target, type) => {
    const normalizedTarget = normalizeTarget(target);
    if (!normalizedTarget) {
        isMobileMenuOpen.value = false;
        return;
    }

    const resolvedType = type || resolveType({}, normalizedTarget);

    if (resolvedType === 'anchor' && normalizedTarget.startsWith('#')) {
        const performScroll = () => scrollToAnchor(normalizedTarget);

        if (isMobileMenuOpen.value) {
            isMobileMenuOpen.value = false;
            requestAnimationFrame(() => requestAnimationFrame(performScroll));
        } else {
            performScroll();
        }

        if (window.history?.replaceState) {
            window.history.replaceState(null, '', normalizedTarget);
        }

        return;
    }

    if (resolvedType === 'url') {
        window.open(normalizedTarget, '_blank');
    } else {
        window.location.href = normalizedTarget;
    }
    isMobileMenuOpen.value = false;
};
</script>

<template>
    <header 
        ref="headerRef"
        class="border-b border-gray-100 transition-shadow duration-200"
        :class="headerClasses"
        :style="headerStyles"
    >
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-full">
            <div class="flex items-center gap-2 md:gap-4" :class="headerRowClass" :style="headerRowStyles">
                <!-- Logo -->
                <div v-if="logo.type === 'image' && logo.url" class="flex-shrink-0 max-w-[96px] sm:max-w-[140px]">
                    <img 
                        :src="logo.url" 
                        :alt="logo.alt || 'Logo'"
                        :style="headerLogoStyle"
                        class="w-auto object-contain"
                    />
                </div>
                
                <!-- Logo Text (Initials) -->
                <div v-else-if="logo.type === 'text' && logo.text" class="flex-shrink-0 max-w-[45%] sm:max-w-none">
                    <span 
                        class="font-bold tracking-wider block truncate"
                        :style="logoTextStyle"
                    >
                        {{ (logo.text.initials?.[0] || '').toUpperCase().charAt(0) }}
                        <span class="mx-1">{{ logo.text.connector || '&' }}</span>
                        {{ (logo.text.initials?.[1] || '').toUpperCase().charAt(0) }}
                    </span>
                </div>

                <!-- Title & Subtitle -->
                <div 
                    v-if="!hideHeaderTextOnMobile"
                    class="flex-1 min-w-0 px-1 sm:px-3"
                    :class="{ 'text-center': style.alignment === 'center' }"
                >
                    <h1 
                        v-if="content.title"
                        class="leading-tight break-words"
                        :style="titleStyle"
                    >
                        {{ replacePlaceholders(content.title) }}
                    </h1>
                    <p 
                        v-if="content.subtitle"
                        class="mt-0.5 break-words"
                        :style="subtitleStyle"
                    >
                        {{ replacePlaceholders(content.subtitle) }}
                    </p>
                </div>

                <!-- Desktop Navigation -->
                <nav 
                    v-if="showDesktopNavigation"
                    class="flex items-center space-x-6 lg:space-x-8 shrink-0"
                >
                    <a
                        v-for="(item, index) in navigation"
                        :key="index"
                        :href="item.target || '#'"
                        class="public-header-nav-link cursor-pointer"
                        :style="menuLinkStyle"
                        @click.prevent="navigateTo(item.target, item.type)"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <!-- Action Button -->
                <div v-if="showDesktopNavigation && actionButton.label" class="hidden lg:block ml-3 lg:ml-6 shrink-0">
                    <a
                        :href="actionButton.target || '#'"
                        :class="buttonClasses"
                        :style="actionButton.style === 'primary' 
                            ? { backgroundColor: theme.primaryColor } 
                            : actionButton.style === 'secondary'
                                ? { borderColor: theme.primaryColor, color: theme.primaryColor }
                                : { color: theme.primaryColor }"
                        @click.prevent="navigateTo(actionButton.target, actionButton.type)"
                    >
                        <span v-if="actionButton.icon" class="mr-2">{{ actionButton.icon }}</span>
                        {{ actionButton.label }}
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button 
                    v-if="showMobileNavigation"
                    class="p-2 text-gray-600 hover:text-gray-900 rounded-md hover:bg-gray-100 flex-shrink-0"
                    :class="hideHeaderTextOnMobile ? 'ml-auto' : 'ml-2'"
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
                v-if="isMobileMenuOpen && showMobileNavigation"
                class="border-t border-gray-100"
                :style="{ backgroundColor: safeHeaderBackgroundColor }"
            >
                <nav class="px-4 py-4 space-y-2">
                    <a
                        v-for="(item, index) in navigation"
                        :key="index"
                        :href="item.target || '#'"
                        class="public-header-nav-link public-header-nav-link-mobile block px-4 py-3 rounded-md"
                        :style="menuLinkStyle"
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
                        @click.prevent="navigateTo(actionButton.target, actionButton.type)"
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

.public-header-nav-link {
    color: var(--dm-menu-font-color);
    font-family: var(--dm-menu-font-family);
    font-size: var(--dm-menu-font-size);
    font-weight: var(--dm-menu-font-weight);
    font-style: var(--dm-menu-font-style);
    text-decoration: var(--dm-menu-font-decoration);
    line-height: 1.25;
    transition: color 0.2s ease, opacity 0.2s ease;
}

.public-header-nav-link:hover,
.public-header-nav-link:focus-visible {
    color: var(--dm-menu-hover-font-color);
    font-family: var(--dm-menu-hover-font-family);
    font-size: var(--dm-menu-hover-font-size);
    font-weight: var(--dm-menu-hover-font-weight);
    font-style: var(--dm-menu-hover-font-style);
    text-decoration: var(--dm-menu-hover-font-decoration);
    opacity: 0.95;
}

.public-header-nav-link-mobile:hover,
.public-header-nav-link-mobile:focus-visible {
    background-color: rgba(0, 0, 0, 0.04);
}
</style>
