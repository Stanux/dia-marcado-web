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
    footerContent: {
        type: Object,
        default: () => ({}),
    },
    sectionOrder: {
        type: Array,
        default: () => [],
    },
    viewportMode: {
        type: String,
        default: 'auto',
    },
});

const SECTION_ANCHORS_BY_KEY = {
    hero: 'hero',
    saveTheDate: 'save-the-date',
    giftRegistry: 'gift-registry',
    guestsV2: 'guests-v2',
    photoGallery: 'photo-gallery',
};

const LEGACY_ANCHOR_ALIASES = {
    'lista-presentes': 'gift-registry',
    rsvp: 'guests-v2',
    'confirmar-presenca': 'guests-v2',
    guests: 'guests-v2',
    convidados: 'guests-v2',
    galeria: 'photo-gallery',
};

const SECTION_KEYS_BY_ANCHOR = Object.entries(SECTION_ANCHORS_BY_KEY).reduce((accumulator, [sectionKey, anchor]) => {
    accumulator[anchor] = sectionKey;
    return accumulator;
}, {});

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

const resolveNavigationSectionKey = (item = {}, target = '') => {
    const rawSectionKey = typeof item.sectionKey === 'string' ? item.sectionKey.trim() : '';

    if (rawSectionKey) {
        return rawSectionKey === 'rsvp' ? 'guestsV2' : rawSectionKey;
    }

    if (!target.startsWith('#')) {
        return '';
    }

    const anchor = target.slice(1);
    const normalizedAnchor = LEGACY_ANCHOR_ALIASES[anchor] || anchor;

    return SECTION_KEYS_BY_ANCHOR[normalizedAnchor] || '';
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
const logo = computed(() => props.content.logo || {
    type: 'text',
    url: '',
    alt: '',
    text: {
        initials: ['', ''],
        connector: '&',
    },
});
const navigation = computed(() => {
    const items = Array.isArray(props.content.navigation) ? props.content.navigation : [];
    if (!items.length) return [];

    const resolveOrderIndex = (sectionKey) => {
        if (!sectionKey) {
            return Number.MAX_SAFE_INTEGER;
        }

        const index = props.sectionOrder.indexOf(sectionKey);
        return index === -1 ? Number.MAX_SAFE_INTEGER : index;
    };

    return items
        .map((item, sourceIndex) => {
            const target = resolveTarget(item);
            const sectionKey = resolveNavigationSectionKey(item, target);

            return {
                ...item,
                target,
                type: resolveType(item, target),
                sectionKey,
                _sourceIndex: sourceIndex,
                _orderIndex: resolveOrderIndex(sectionKey),
            };
        })
        .filter(item => item.showInMenu)
        .filter(item => {
            if (item.sectionKey && props.enabledSections) {
                return props.enabledSections[item.sectionKey] === true;
            }

            return true;
        })
        .filter(item => item.target)
        .sort((left, right) => {
            if (left._orderIndex !== right._orderIndex) {
                return left._orderIndex - right._orderIndex;
            }

            return left._sourceIndex - right._sourceIndex;
        })
        .map(({ _sourceIndex, _orderIndex, ...item }) => item);
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

const clampPercentage = (value, fallback = 0) => {
    const parsed = typeof value === 'number'
        ? value
        : Number.parseFloat(value);

    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return Math.max(0, Math.min(100, parsed));
};

const clampRange = (value, min, max, fallback) => {
    const parsed = typeof value === 'number'
        ? value
        : Number.parseFloat(value);

    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return Math.max(min, Math.min(max, parsed));
};

const resolveRgbChannels = (value, fallback = [17, 24, 39]) => {
    if (typeof value !== 'string' || !value.trim()) {
        return fallback;
    }

    const normalized = value.trim();

    if (/^#[0-9a-f]{6}$/i.test(normalized)) {
        return [
            Number.parseInt(normalized.slice(1, 3), 16),
            Number.parseInt(normalized.slice(3, 5), 16),
            Number.parseInt(normalized.slice(5, 7), 16),
        ];
    }

    if (/^#[0-9a-f]{3}$/i.test(normalized)) {
        return normalized
            .slice(1)
            .split('')
            .map((channel) => Number.parseInt(`${channel}${channel}`, 16));
    }

    const rgbMatch = normalized.match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*[+-]?\d*\.?\d+\s*)?\)$/i);
    if (rgbMatch) {
        return [
            clampRgbChannel(rgbMatch[1]),
            clampRgbChannel(rgbMatch[2]),
            clampRgbChannel(rgbMatch[3]),
        ];
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
const isTransparentBackground = computed(() => {
    if (Object.prototype.hasOwnProperty.call(style.value, 'transparent')) {
        return parseBoolean(style.value.transparent);
    }

    if (typeof style.value.backgroundColor === 'string') {
        return style.value.backgroundColor.trim().toLowerCase() === 'transparent';
    }

    return false;
});
const isStickyEnabled = computed(() => parseBoolean(style.value.sticky) && !isTransparentBackground.value);
const headerHeightCss = computed(() => toCssSize(style.value.height, '80px'));
const headerBackgroundColor = computed(() => (
    isTransparentBackground.value ? 'transparent' : safeHeaderBackgroundColor.value
));
const headerBorderClass = computed(() => (
    isTransparentBackground.value ? '' : 'border-b border-gray-100'
));
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
    textDecoration: logo.value.text?.typography?.fontUnderline ? 'underline' : 'none',
    lineHeight: 1.15,
    whiteSpace: 'nowrap',
}));

const menuLinkStyle = computed(() => {
    const normal = menuTypography.value;
    const hover = menuHoverTypography.value;
    const fallbackFamily = props.theme.fontFamily || 'Montserrat';
    const fallbackColor = '#374151';
    const fallbackHoverColor = props.theme.primaryColor || '#f97373';
    const hasHoverItalic = Object.prototype.hasOwnProperty.call(hover, 'fontItalic');
    const hasHoverUnderline = Object.prototype.hasOwnProperty.call(hover, 'fontUnderline');
    const normalItalic = Boolean(normal.fontItalic);
    const normalUnderline = Boolean(normal.fontUnderline);

    return {
        '--dm-menu-font-family': normal.fontFamily || fallbackFamily,
        '--dm-menu-font-color': normal.fontColor || fallbackColor,
        '--dm-menu-font-size': `${parsePixels(normal.fontSize, 14)}px`,
        '--dm-menu-font-weight': String(normal.fontWeight ?? 400),
        '--dm-menu-font-style': normalItalic ? 'italic' : 'normal',
        '--dm-menu-font-decoration': normalUnderline ? 'underline' : 'none',
        '--dm-menu-hover-font-family': hover.fontFamily ?? normal.fontFamily ?? fallbackFamily,
        '--dm-menu-hover-font-color': hover.fontColor || fallbackHoverColor,
        '--dm-menu-hover-font-size': `${parsePixels(hover.fontSize, parsePixels(normal.fontSize, 14))}px`,
        '--dm-menu-hover-font-weight': String(hover.fontWeight ?? normal.fontWeight ?? 400),
        '--dm-menu-hover-font-style': (hasHoverItalic ? Boolean(hover.fontItalic) : normalItalic) ? 'italic' : 'normal',
        '--dm-menu-hover-font-decoration': (hasHoverUnderline ? Boolean(hover.fontUnderline) : normalUnderline) ? 'underline' : 'none',
    };
});

const mobileMenuButtonStyle = computed(() => ({
    color: menuTypography.value.fontColor || '#374151',
}));

const mobileMenuBackgroundColor = computed(() => {
    if (typeof style.value.mobileMenuBackgroundColor === 'string' && style.value.mobileMenuBackgroundColor.trim()) {
        return style.value.mobileMenuBackgroundColor.trim();
    }

    return '#111827';
});

const mobileMenuTransparency = computed(() => clampPercentage(style.value.mobileMenuTransparency, 18));
const mobileMenuBlur = computed(() => clampRange(style.value.mobileMenuBlur, 0, 32, 14));

const showBackToTop = computed(() => {
    if (Object.prototype.hasOwnProperty.call(props.content, 'showBackToTop')) {
        return parseBoolean(props.content.showBackToTop);
    }

    if (Object.prototype.hasOwnProperty.call(props.footerContent || {}, 'showBackToTop')) {
        return parseBoolean(props.footerContent.showBackToTop);
    }

    return true;
});

const backToTopButton = computed(() => {
    const headerButton = props.content?.backToTopButton || {};
    const legacyFooterButton = props.footerContent?.backToTopButton || {};

    return {
        backgroundColor: headerButton.backgroundColor || legacyFooterButton.backgroundColor || '#111827',
        iconColor: headerButton.iconColor || legacyFooterButton.iconColor || '#ffffff',
    };
});

const backToTopButtonStyle = computed(() => ({
    backgroundColor: backToTopButton.value.backgroundColor,
    color: backToTopButton.value.iconColor,
}));

const syncViewportBreakpoint = () => {
    if (typeof window === 'undefined') {
        return;
    }

    isWideScreen.value = window.matchMedia('(min-width: 1024px)').matches;
    isMobileScreen.value = window.matchMedia('(max-width: 767px)').matches;
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
    minHeight: headerHeightCss.value,
    height: headerHeightCss.value,
    backgroundColor: headerBackgroundColor.value,
    position: isStickyEnabled.value ? 'sticky' : 'relative',
    top: isStickyEnabled.value ? '0px' : undefined,
    zIndex: isStickyEnabled.value || isTransparentBackground.value ? 50 : undefined,
    marginBottom: isTransparentBackground.value ? `calc(-1 * ${headerHeightCss.value})` : undefined,
    overflow: 'visible',
}));

const headerRowStyles = computed(() => ({
    minHeight: headerHeightCss.value,
}));

const mobileMenuStyles = computed(() => ({
    position: 'absolute',
    left: '0px',
    right: '0px',
    top: '100%',
    zIndex: 65,
    backgroundColor: (() => {
        const [r, g, b] = resolveRgbChannels(mobileMenuBackgroundColor.value, [17, 24, 39]);
        const alpha = Math.max(0, Math.min(1, 1 - (mobileMenuTransparency.value / 100)));

        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    })(),
    backdropFilter: `blur(${mobileMenuBlur.value}px)`,
    WebkitBackdropFilter: `blur(${mobileMenuBlur.value}px)`,
    boxShadow: '0 18px 50px rgba(0, 0, 0, 0.28)',
}));

// Header classes
const headerClasses = computed(() => ({
    'shadow-md': isStickyEnabled.value && isScrolled.value,
}));

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

const scrollToTop = () => {
    window.scrollTo({
        top: 0,
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
        class="transition-shadow duration-200"
        :class="[headerBorderClass, headerClasses]"
        :style="headerStyles"
    >
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-full">
            <div class="flex items-center gap-2 md:gap-4 h-full" :style="headerRowStyles">
                <!-- Logo -->
                <div v-if="logo.type === 'image' && logo.url" class="flex-shrink-0 max-w-[96px] sm:max-w-[140px]">
                    <a href="/" class="public-header-logo-link" aria-label="Ir para o início">
                        <img 
                            :src="logo.url" 
                            :alt="logo.alt || 'Logo'"
                            :style="headerLogoStyle"
                            class="w-auto object-contain"
                        />
                    </a>
                </div>
                
                <!-- Logo Text (Initials) -->
                <div v-else-if="logo.type === 'text' && logo.text" class="flex-shrink-0 max-w-[45%] sm:max-w-none">
                    <a href="/" class="public-header-logo-link" aria-label="Ir para o início">
                        <span 
                            class="font-bold tracking-wider block truncate"
                            :style="logoTextStyle"
                        >
                            {{ (logo.text.initials?.[0] || '').toUpperCase().charAt(0) }}
                            <span class="mx-1">{{ logo.text.connector || '&' }}</span>
                            {{ (logo.text.initials?.[1] || '').toUpperCase().charAt(0) }}
                        </span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav 
                    v-if="showDesktopNavigation"
                    class="ml-auto flex items-center space-x-6 lg:space-x-8 shrink-0"
                >
                    <a
                        v-for="(item, index) in navigation"
                        :key="index"
                        :href="item.target || '#'"
                        class="public-header-nav-link public-header-nav-link-desktop cursor-pointer"
                        :data-label="item.label"
                        :style="menuLinkStyle"
                        @click.prevent="navigateTo(item.target, item.type)"
                    >
                        <span class="public-header-nav-link-normal">{{ item.label }}</span>
                        <span class="public-header-nav-link-hover" aria-hidden="true">{{ item.label }}</span>
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
                    class="ml-auto p-2 rounded-md hover:bg-gray-100 flex-shrink-0"
                    :style="mobileMenuButtonStyle"
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
                class="absolute left-0 right-0 top-full z-[65]"
                :class="isTransparentBackground ? '' : 'border-t border-gray-100'"
                :style="mobileMenuStyles"
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

        <button
            v-if="showBackToTop"
            type="button"
            class="fixed bottom-4 right-4 md:bottom-6 md:right-6 z-[70] h-12 w-12 rounded-full shadow-lg transition-transform duration-200 hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-black/40 flex items-center justify-center"
            :style="backToTopButtonStyle"
            aria-label="Voltar ao topo"
            title="Voltar ao topo"
            @click="scrollToTop"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
        </button>
    </header>
</template>

<style scoped>
/* Smooth transitions */
header {
    transition: box-shadow 0.2s ease;
}

.public-header-nav-link {
    line-height: 1.25;
    transition: color 0.2s ease, opacity 0.2s ease;
}

.public-header-nav-link-desktop {
    display: inline-grid;
    align-items: center;
}

.public-header-nav-link-desktop::before {
    content: attr(data-label);
    grid-area: 1 / 1;
    visibility: hidden;
    pointer-events: none;
    white-space: nowrap;
    line-height: 1.25;
    font-family: var(--dm-menu-hover-font-family);
    font-size: var(--dm-menu-hover-font-size);
    font-weight: var(--dm-menu-hover-font-weight);
    font-style: var(--dm-menu-hover-font-style);
    text-decoration: var(--dm-menu-hover-font-decoration);
}

.public-header-nav-link-normal,
.public-header-nav-link-hover {
    grid-area: 1 / 1;
    white-space: nowrap;
    transition: opacity 0.2s ease;
}

.public-header-nav-link-normal {
    color: var(--dm-menu-font-color);
    font-family: var(--dm-menu-font-family);
    font-size: var(--dm-menu-font-size);
    font-weight: var(--dm-menu-font-weight);
    font-style: var(--dm-menu-font-style);
    text-decoration: var(--dm-menu-font-decoration);
    opacity: 1;
}

.public-header-nav-link-hover {
    color: var(--dm-menu-hover-font-color);
    font-family: var(--dm-menu-hover-font-family);
    font-size: var(--dm-menu-hover-font-size);
    font-weight: var(--dm-menu-hover-font-weight);
    font-style: var(--dm-menu-hover-font-style);
    text-decoration: var(--dm-menu-hover-font-decoration);
    pointer-events: none;
    opacity: 0;
}

.public-header-nav-link-desktop:hover .public-header-nav-link-normal,
.public-header-nav-link-desktop:focus-visible .public-header-nav-link-normal {
    opacity: 0;
}

.public-header-nav-link-desktop:hover .public-header-nav-link-hover,
.public-header-nav-link-desktop:focus-visible .public-header-nav-link-hover {
    opacity: 0.95;
}

.public-header-nav-link-mobile {
    color: var(--dm-menu-font-color);
    font-family: var(--dm-menu-font-family);
    font-size: var(--dm-menu-font-size);
    font-weight: var(--dm-menu-font-weight);
    font-style: var(--dm-menu-font-style);
    text-decoration: var(--dm-menu-font-decoration);
}

.public-header-nav-link-mobile:hover,
.public-header-nav-link-mobile:focus-visible {
    color: var(--dm-menu-hover-font-color);
    font-family: var(--dm-menu-hover-font-family);
    font-size: var(--dm-menu-hover-font-size);
    font-weight: var(--dm-menu-hover-font-weight);
    font-style: var(--dm-menu-hover-font-style);
    text-decoration: var(--dm-menu-hover-font-decoration);
    background-color: rgba(255, 255, 255, 0.08);
}

.public-header-logo-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
}

.public-header-logo-link:hover,
.public-header-logo-link:focus-visible,
.public-header-logo-link:visited {
    text-decoration: none;
    color: inherit;
}
</style>
