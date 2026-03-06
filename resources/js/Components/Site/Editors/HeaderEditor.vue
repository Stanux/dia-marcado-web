<script setup>
/**
 * HeaderEditor Component
 * 
 * Editor for the Header section of the wedding site.
 * Supports logo upload and navigation menu configuration.
 * 
 * @Requirements: 8.1, 8.5, 8.6, 8.7
 */
import { ref, watch, computed, useAttrs, onMounted } from 'vue';
import { SECTION_IDS, SECTION_LABELS } from '@/Composables/useSiteEditor';
import MediaGalleryModal from '@/Components/Site/MediaGalleryModal.vue';
import TypographyControl from '@/Components/Site/TypographyControl.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    enabledSections: {
        type: Object,
        default: () => ({}),
    },
    logoInitials: {
        type: Array,
        default: () => ['', ''],
    },
});

/**
 * Get navigable sections (all sections except header and footer)
 */
const navigableSections = computed(() => {
    return Object.keys(SECTION_IDS).map(key => ({
        key: key,
        id: SECTION_IDS[key],
        label: SECTION_LABELS[key],
        enabled: props.enabledSections[key] || false,
    }));
});

const emit = defineEmits(['change']);
const attrs = useAttrs();
const isEyeDropperSupported = ref(false);

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

const sanitizeInitial = (value) => {
    if (!value || typeof value !== 'string') return '';
    return value.trim().charAt(0).toUpperCase();
};

const getDefaultLogoInitials = () => {
    const [first = '', second = ''] = props.logoInitials || [];
    return [sanitizeInitial(first), sanitizeInitial(second)];
};

const applyDefaultLogoInitialsIfEmpty = () => {
    if (!localContent.value.logo?.text) {
        return;
    }

    if (!Array.isArray(localContent.value.logo.text.initials)) {
        localContent.value.logo.text.initials = ['', ''];
    }

    const currentInitial1 = sanitizeInitial(localContent.value.logo.text.initials[0] || '');
    const currentInitial2 = sanitizeInitial(localContent.value.logo.text.initials[1] || '');

    // Never override initials that were already manually filled.
    if (currentInitial1 || currentInitial2) {
        localContent.value.logo.text.initials = [currentInitial1, currentInitial2];
        return;
    }

    const [defaultInitial1, defaultInitial2] = getDefaultLogoInitials();

    if (!defaultInitial1 && !defaultInitial2) {
        return;
    }

    localContent.value.logo.text.initials = [defaultInitial1, defaultInitial2];
};

const syncDefaultLogoInitials = () => {
    const effectiveLogoType = localContent.value.logo?.type || 'text';
    if (effectiveLogoType !== 'text') {
        return false;
    }

    if (!localContent.value.logo) {
        localContent.value.logo = {
            type: 'text',
            url: '',
            alt: '',
            text: {
                initials: ['', ''],
                connector: '&',
            },
        };
    }

    if (!localContent.value.logo.text) {
        localContent.value.logo.text = {
            initials: ['', ''],
            connector: '&',
        };
    }

    const before = JSON.stringify(localContent.value.logo.text.initials || ['', '']);
    applyDefaultLogoInitialsIfEmpty();
    const after = JSON.stringify(localContent.value.logo.text.initials || ['', '']);

    return before !== after;
};

// Initialize typography for existing logo text if missing
if (localContent.value.logo?.type === 'text' && localContent.value.logo.text && !localContent.value.logo.text.typography) {
    localContent.value.logo.text.typography = {
        fontFamily: 'Playfair Display',
        fontColor: '#333333',
        fontSize: 48,
        fontWeight: 700,
        fontItalic: false,
        fontUnderline: false,
    };
    // Emit the change to save the initialized typography
    emit('change', JSON.parse(JSON.stringify(localContent.value)));
}

if (syncDefaultLogoInitials()) {
    emit('change', JSON.parse(JSON.stringify(localContent.value)));
}

// Watch for external content changes
watch(() => props.content, (newContent) => {
    localContent.value = JSON.parse(JSON.stringify(newContent));
    
    // Initialize typography for existing logo text if missing
    if (localContent.value.logo?.type === 'text' && localContent.value.logo.text && !localContent.value.logo.text.typography) {
        localContent.value.logo.text.typography = {
            fontFamily: 'Playfair Display',
            fontColor: '#333333',
            fontSize: 48,
            fontWeight: 700,
            fontItalic: false,
            fontUnderline: false,
        };
        // Emit the change to save the initialized typography
        emit('change', JSON.parse(JSON.stringify(localContent.value)));
    }

    if (syncDefaultLogoInitials()) {
        emit('change', JSON.parse(JSON.stringify(localContent.value)));
    }
}, { deep: true });

/**
 * Emit changes to parent
 */
const emitChange = () => {
    emit('change', JSON.parse(JSON.stringify(localContent.value)));
};

/**
 * Update a field and emit change
 */
const updateField = (field, value) => {
    localContent.value[field] = value;
    emitChange();
};

/**
 * Update logo field
 */
const updateLogo = (field, value) => {
    if (!localContent.value.logo) {
        localContent.value.logo = { url: '', alt: '' };
    }
    localContent.value.logo[field] = value;
    emitChange();
};

/**
 * Update style field
 */
const updateStyle = (field, value) => {
    if (!localContent.value.style) {
        localContent.value.style = {};
    }
    localContent.value.style[field] = value;
    emitChange();
};

const updateStyleValues = (values) => {
    if (!localContent.value.style) {
        localContent.value.style = {};
    }

    Object.entries(values).forEach(([field, value]) => {
        localContent.value.style[field] = value;
    });

    emitChange();
};

const pickBackgroundColorFromScreen = async () => {
    if (!isEyeDropperSupported.value) {
        return;
    }

    try {
        const eyeDropper = new window.EyeDropper();
        const { sRGBHex } = await eyeDropper.open();

        if (typeof sRGBHex === 'string' && sRGBHex) {
            updateStyleValues({
                backgroundColor: sRGBHex.toLowerCase(),
                transparent: false,
            });
        }
    } catch (error) {
        if (error?.name !== 'AbortError') {
            console.warn('EyeDropper falhou:', error);
        }
    }
};

/**
 * Initialize navigation from sections if not exists
 */
const buildNavigationItem = (section) => ({
    sectionKey: section.key,
    label: section.label,
    target: `#${SECTION_IDS[section.key]}`,
    type: 'anchor',
    showInMenu: false,
});

const normalizeGuestsTarget = (target) => {
    const normalized = typeof target === 'string' ? target.trim() : '';

    if (!normalized || normalized === '#rsvp' || normalized === '#confirmar-presenca') {
        return '#guests-v2';
    }

    return normalized;
};

const initializeNavigation = () => {
    if (!localContent.value.navigation || !Array.isArray(localContent.value.navigation)) {
        localContent.value.navigation = navigableSections.value.map(section => buildNavigationItem(section));
    } else {
        const hasGuestsV2InSource = localContent.value.navigation.some((item) => item?.sectionKey === 'guestsV2');

        // Normalize existing items that were created before target/type fields existed
        localContent.value.navigation = localContent.value.navigation
            .map(item => {
                if (!item?.sectionKey) {
                    return item;
                }

                if (item.sectionKey === 'rsvp' && hasGuestsV2InSource) {
                    return null;
                }

                const migratedSectionKey = item.sectionKey === 'rsvp' ? 'guestsV2' : item.sectionKey;
                const isLegacyHeroLabel = migratedSectionKey === 'hero' && (!item.label || item.label === 'Hero');

                return {
                    ...item,
                    sectionKey: migratedSectionKey,
                    label: isLegacyHeroLabel
                        ? 'Destaque'
                        : (item.label || SECTION_LABELS[migratedSectionKey] || migratedSectionKey),
                    target: normalizeGuestsTarget(item.target || `#${SECTION_IDS[migratedSectionKey] || ''}`),
                    type: item.type || 'anchor',
                };
            })
            .filter(Boolean);

        // Ensure all sections are present
        navigableSections.value.forEach(section => {
            const exists = localContent.value.navigation.find(nav => nav.sectionKey === section.key);
            if (!exists) {
                localContent.value.navigation.push(buildNavigationItem(section));
            }
        });
    }
};

// Initialize navigation on mount
initializeNavigation();

/**
 * Update navigation item
 */
const updateNavigationItem = (sectionKey, field, value) => {
    const item = localContent.value.navigation.find(nav => nav.sectionKey === sectionKey);
    if (item) {
        item[field] = value;
        emitChange();
    }
};

/**
 * Get navigation item for a section
 */
const getNavigationItem = (sectionKey) => {
    return localContent.value.navigation?.find(nav => nav.sectionKey === sectionKey) || {
        sectionKey: sectionKey,
        label: SECTION_LABELS[sectionKey] || '',
        target: `#${SECTION_IDS[sectionKey] || ''}`,
        type: 'anchor',
        showInMenu: false,
    };
};

// Modal state
const showMediaGallery = ref(false);

/**
 * Abrir galeria de mídia
 */
const openMediaGallery = () => {
    showMediaGallery.value = true;
};

/**
 * Selecionar imagem da galeria
 */
const onImageSelected = (imageData) => {
    updateLogo('url', imageData.url);
    updateLogo('alt', imageData.alt);
    showMediaGallery.value = false;
};

/**
 * Atualizar tipo de logo
 */
const updateLogoType = (type) => {
    if (!localContent.value.logo) {
        localContent.value.logo = {
            type,
            url: '',
            alt: '',
            text: {
                initials: ['', ''],
                connector: '&',
            },
        };
    }
    localContent.value.logo.type = type;
    
    // Inicializar campos do tipo selecionado se não existirem
    if (type === 'text') {
        if (!localContent.value.logo.text) {
            localContent.value.logo.text = {
                initials: ['', ''],
                connector: '&',
                typography: {
                    fontFamily: 'Playfair Display',
                    fontColor: '#333333',
                    fontSize: 48,
                    fontWeight: 700,
                    fontItalic: false,
                    fontUnderline: false,
                },
            };
        } else if (!localContent.value.logo.text.typography) {
            // Se text existe mas typography não, inicializar typography
            localContent.value.logo.text.typography = {
                fontFamily: 'Playfair Display',
                fontColor: '#333333',
                fontSize: 48,
                fontWeight: 700,
                fontItalic: false,
                fontUnderline: false,
            };
        }

        applyDefaultLogoInitialsIfEmpty();
    } else if (type === 'image') {
        // Manter url e alt existentes, apenas garantir que existam
        if (!localContent.value.logo.url) {
            localContent.value.logo.url = '';
        }
        if (!localContent.value.logo.alt) {
            localContent.value.logo.alt = '';
        }
    }
    
    emitChange();
};

/**
 * Atualizar texto do logo
 */
const updateLogoText = (field, value) => {
    if (!localContent.value.logo) {
        localContent.value.logo = { type: 'text', text: { initials: ['', ''], connector: '&' } };
    }
    if (!localContent.value.logo.text) {
        localContent.value.logo.text = { initials: ['', ''], connector: '&' };
    }
    
    // Ensure typography is initialized
    if (!localContent.value.logo.text.typography) {
        localContent.value.logo.text.typography = {
            fontFamily: 'Playfair Display',
            fontColor: '#333333',
            fontSize: 48,
            fontWeight: 700,
            fontItalic: false,
            fontUnderline: false,
        };
    }
    
    if (field === 'initial1' || field === 'initial2') {
        const index = field === 'initial1' ? 0 : 1;
        localContent.value.logo.text.initials[index] = sanitizeInitial(value);
    } else {
        localContent.value.logo.text[field] = value;
    }
    
    emitChange();
};

/**
 * Atualizar tipografia do logo texto
 */
const updateLogoTextTypography = (field, value) => {
    if (!localContent.value.logo) {
        localContent.value.logo = { type: 'text', text: { initials: ['', ''], connector: '&' } };
    }
    if (!localContent.value.logo.text) {
        localContent.value.logo.text = { initials: ['', ''], connector: '&' };
    }
    if (!localContent.value.logo.text.typography) {
        localContent.value.logo.text.typography = {
            fontFamily: 'Playfair Display',
            fontColor: '#333333',
            fontSize: 32,
            fontWeight: 700,
            fontItalic: false,
            fontUnderline: false,
        };
    }
    
    localContent.value.logo.text.typography[field] = value;
    emitChange();
};

/**
 * Atualizar tipografia do menu
 */
const updateMenuTypography = (field, value) => {
    if (!localContent.value.menuTypography) {
        localContent.value.menuTypography = {
            fontFamily: 'Montserrat',
            fontColor: '#374151',
            fontSize: 14,
            fontWeight: 400,
            fontItalic: false,
            fontUnderline: false,
        };
    }

    localContent.value.menuTypography[field] = value;
    emitChange();
};

/**
 * Atualizar tipografia de hover do menu
 */
const updateMenuHoverTypography = (field, value) => {
    if (!localContent.value.menuHoverTypography) {
        localContent.value.menuHoverTypography = {
            fontFamily: 'Montserrat',
            fontColor: '#f97373',
            fontSize: 14,
            fontWeight: 500,
            fontItalic: false,
            fontUnderline: false,
        };
    }

    localContent.value.menuHoverTypography[field] = value;
    emitChange();
};

const normalizeHexColor = (color, fallback = '#ffffff') => {
    if (typeof color !== 'string' || !color.trim()) {
        return fallback;
    }

    const value = color.trim();

    if (/^#[0-9a-f]{6}$/i.test(value)) {
        return value;
    }

    if (/^#[0-9a-f]{3}$/i.test(value)) {
        const [r, g, b] = value.slice(1).split('');
        return `#${r}${r}${g}${g}${b}${b}`;
    }

    const rgbaMatch = value.match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*[\d.]+\s*)?\)$/i);

    if (!rgbaMatch) {
        return fallback;
    }

    const toHex = (raw) => {
        const n = Number.parseInt(raw, 10);
        if (Number.isNaN(n)) return '00';
        return Math.max(0, Math.min(255, n)).toString(16).padStart(2, '0');
    };

    return `#${toHex(rgbaMatch[1])}${toHex(rgbaMatch[2])}${toHex(rgbaMatch[3])}`;
};

const hexToRgb = (hexColor) => {
    const normalized = normalizeHexColor(hexColor, '');
    if (!normalized) {
        return null;
    }

    return {
        r: Number.parseInt(normalized.slice(1, 3), 16),
        g: Number.parseInt(normalized.slice(3, 5), 16),
        b: Number.parseInt(normalized.slice(5, 7), 16),
    };
};

const getRelativeLuminance = (hexColor) => {
    const rgb = hexToRgb(hexColor);
    if (!rgb) {
        return null;
    }

    const channels = [rgb.r, rgb.g, rgb.b].map((channel) => {
        const srgb = channel / 255;

        if (srgb <= 0.03928) {
            return srgb / 12.92;
        }

        return ((srgb + 0.055) / 1.055) ** 2.4;
    });

    return (0.2126 * channels[0]) + (0.7152 * channels[1]) + (0.0722 * channels[2]);
};

const getContrastRatio = (foreground, background) => {
    const foregroundLuminance = getRelativeLuminance(foreground);
    const backgroundLuminance = getRelativeLuminance(background);

    if (foregroundLuminance === null || backgroundLuminance === null) {
        return 0;
    }

    const lighter = Math.max(foregroundLuminance, backgroundLuminance);
    const darker = Math.min(foregroundLuminance, backgroundLuminance);

    return (lighter + 0.05) / (darker + 0.05);
};

const normalizePercentage = (value, fallback = 0) => {
    const parsed = typeof value === 'number'
        ? value
        : Number.parseFloat(value);

    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return Math.max(0, Math.min(100, Math.round(parsed)));
};

const normalizeBlurAmount = (value, fallback = 14) => {
    const parsed = typeof value === 'number'
        ? value
        : Number.parseFloat(value);

    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return Math.max(0, Math.min(32, Math.round(parsed)));
};

// Computed properties
const navigation = computed(() => localContent.value.navigation || []);
const logo = computed(() => localContent.value.logo || {
    type: 'text',
    url: '',
    alt: '',
    text: {
        initials: ['', ''],
        connector: '&',
    },
});
const logoType = computed(() => logo.value.type || 'text');
const logoText = computed(() => logo.value.text || { initials: ['', ''], connector: '&' });
const logoTextTypography = computed(() => logoText.value.typography || {
    fontFamily: 'Playfair Display',
    fontColor: '#333333',
    fontSize: 48,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
});
const menuTypography = computed(() => localContent.value.menuTypography || {
    fontFamily: 'Montserrat',
    fontColor: '#374151',
    fontSize: 14,
    fontWeight: 400,
    fontItalic: false,
    fontUnderline: false,
});
const menuHoverTypography = computed(() => localContent.value.menuHoverTypography || {
    fontFamily: 'Montserrat',
    fontColor: '#f97373',
    fontSize: 14,
    fontWeight: 500,
    fontItalic: false,
    fontUnderline: false,
});
const style = computed(() => localContent.value.style || {});
const headerBackgroundColorHex = computed(() => normalizeHexColor(style.value.backgroundColor, '#ffffff'));
const mobileMenuBackgroundColorHex = computed(() => normalizeHexColor(
    style.value.mobileMenuBackgroundColor,
    '#111827'
));
const mobileMenuTransparency = computed(() => normalizePercentage(style.value.mobileMenuTransparency, 18));
const mobileMenuBlur = computed(() => normalizeBlurAmount(style.value.mobileMenuBlur, 14));
const typographyPreviewSurfaces = {
    light: '#f9fafb',
    dark: '#111827',
};

const parseStyleBoolean = (value) => {
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

const showBackToTop = computed(() => {
    if (Object.prototype.hasOwnProperty.call(localContent.value, 'showBackToTop')) {
        return parseStyleBoolean(localContent.value.showBackToTop);
    }

    return true;
});
const backToTopButton = computed(() => localContent.value.backToTopButton || {});
const backToTopBackgroundColorHex = computed(() => normalizeHexColor(
    backToTopButton.value.backgroundColor,
    '#111827'
));
const backToTopIconColorHex = computed(() => normalizeHexColor(
    backToTopButton.value.iconColor,
    '#ffffff'
));

const isTransparentBackground = computed(() => {
    if (Object.prototype.hasOwnProperty.call(style.value, 'transparent')) {
        return parseStyleBoolean(style.value.transparent);
    }

    if (typeof style.value.backgroundColor === 'string') {
        return style.value.backgroundColor.trim().toLowerCase() === 'transparent';
    }

    return false;
});

const getTypographyPreviewBackgroundColor = (fontColor, fallback = '#374151') => {
    if (!isTransparentBackground.value) {
        return headerBackgroundColorHex.value;
    }

    const normalizedFontColor = normalizeHexColor(fontColor, fallback);
    const lightContrast = getContrastRatio(normalizedFontColor, typographyPreviewSurfaces.light);
    const darkContrast = getContrastRatio(normalizedFontColor, typographyPreviewSurfaces.dark);

    return darkContrast >= lightContrast
        ? typographyPreviewSurfaces.dark
        : typographyPreviewSurfaces.light;
};

const logoTypographyPreviewBackgroundColor = computed(() => getTypographyPreviewBackgroundColor(
    logoTextTypography.value.fontColor,
    '#333333'
));
const menuTypographyPreviewBackgroundColor = computed(() => getTypographyPreviewBackgroundColor(
    menuTypography.value.fontColor,
    '#374151'
));
const menuHoverTypographyPreviewBackgroundColor = computed(() => getTypographyPreviewBackgroundColor(
    menuHoverTypography.value.fontColor,
    '#f97373'
));

const isStickyEnabled = computed(() => parseStyleBoolean(style.value.sticky));

const setBackgroundMode = (mode) => {
    if (mode === 'transparent') {
        updateStyleValues({
            transparent: true,
            backgroundColor: 'transparent',
            sticky: false,
        });
        return;
    }

    const currentBackground = typeof style.value.backgroundColor === 'string'
        ? style.value.backgroundColor.trim().toLowerCase()
        : '';
    const nextBackground = currentBackground === 'transparent'
        ? '#ffffff'
        : (style.value.backgroundColor || '#ffffff');

    updateStyleValues({
        transparent: false,
        backgroundColor: nextBackground,
    });
};

const handleBackgroundColorInput = (value) => {
    updateStyleValues({
        transparent: false,
        backgroundColor: value,
    });
};

const handleMobileMenuBackgroundColorInput = (value) => {
    updateStyle('mobileMenuBackgroundColor', value);
};

const handleMobileMenuTransparencyInput = (value) => {
    updateStyle('mobileMenuTransparency', normalizePercentage(value, 18));
};

const handleMobileMenuBlurInput = (value) => {
    updateStyle('mobileMenuBlur', normalizeBlurAmount(value, 14));
};

const updateBackToTopButton = (field, value) => {
    if (!localContent.value.backToTopButton || typeof localContent.value.backToTopButton !== 'object') {
        localContent.value.backToTopButton = {};
    }

    localContent.value.backToTopButton[field] = value;
    emitChange();
};

watch(isTransparentBackground, (transparent) => {
    if (transparent && isStickyEnabled.value) {
        updateStyle('sticky', false);
    }
}, { immediate: true });

onMounted(() => {
    isEyeDropperSupported.value = typeof window !== 'undefined' && 'EyeDropper' in window;

    if (syncDefaultLogoInitials()) {
        emitChange();
    }
});
</script>

<template>
    <div v-bind="attrs" class="space-y-6 h-full overflow-y-auto">
        <!-- Logo Section -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Logo</h3>
            
            <!-- Tipo de Logo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Logo</label>
                <div class="logo-type-tabs" role="tablist" aria-label="Tipo de Logo">
                    <button
                        type="button"
                        @click="updateLogoType('text')"
                        class="logo-type-tab"
                        :class="{ 'logo-type-tab-active': logoType === 'text' }"
                        :aria-selected="logoType === 'text'"
                        role="tab"
                    >
                        Usar Iniciais
                    </button>
                    <button
                        type="button"
                        @click="updateLogoType('image')"
                        class="logo-type-tab"
                        :class="{ 'logo-type-tab-active': logoType === 'image' }"
                        :aria-selected="logoType === 'image'"
                        role="tab"
                    >
                        Usar Imagem
                    </button>
                </div>
            </div>

            <!-- Logo como Imagem -->
            <div v-if="logoType === 'image'" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Imagem do Logo</label>
                    <button
                        @click="openMediaGallery"
                        class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-md hover:border-wedding-500 transition-colors flex items-center justify-center gap-2 text-gray-600 hover:text-wedding-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Selecionar da Galeria (máx. 512×512px)
                    </button>
                    <p class="mt-1 text-xs text-gray-500">Escolha uma imagem da sua galeria de fotos</p>
                </div>

                <!-- Preview da imagem -->
                <div v-if="logo.url" class="p-3 bg-gray-50 rounded-md">
                    <div class="flex items-center gap-3">
                        <img :src="logo.url" :alt="logo.alt" class="w-12 h-12 object-contain border border-gray-200 rounded" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ logo.alt || 'Logo' }}</p>
                        </div>
                        <button
                            @click="updateLogo('url', '')"
                            class="p-1 text-red-400 hover:text-red-600"
                            title="Remover"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Texto Alternativo (Alt)</label>
                    <input
                        type="text"
                        :value="logo.alt"
                        @input="updateLogo('alt', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        placeholder="Descrição do logo para acessibilidade"
                    />
                </div>
            </div>

            <!-- Logo como Texto (Iniciais) -->
            <div v-else class="space-y-3">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Inicial 1</label>
                        <input
                            type="text"
                            :value="logoText.initials[0]"
                            @input="updateLogoText('initial1', $event.target.value)"
                            maxlength="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-center text-lg font-semibold uppercase"
                            placeholder="V"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Conector</label>
                        <select
                            :value="logoText.connector"
                            @change="updateLogoText('connector', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-center"
                        >
                            <option value="&">&</option>
                            <option value="e">e</option>
                            <option value="-">-</option>
                            <option value="/">/</option>
                            <option value="+">+</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Inicial 2</label>
                        <input
                            type="text"
                            :value="logoText.initials[1]"
                            @input="updateLogoText('initial2', $event.target.value)"
                            maxlength="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-center text-lg font-semibold uppercase"
                            placeholder="L"
                        />
                    </div>
                </div>

                <!-- Tipografia do Logo Texto -->
                <TypographyControl
                    :font-family="logoTextTypography.fontFamily"
                    :font-color="logoTextTypography.fontColor"
                    :font-size="logoTextTypography.fontSize"
                    :font-weight="logoTextTypography.fontWeight"
                    :font-italic="logoTextTypography.fontItalic"
                    :font-underline="logoTextTypography.fontUnderline"
                    :preview-background-color="logoTypographyPreviewBackgroundColor"
                    @update:font-family="updateLogoTextTypography('fontFamily', $event)"
                    @update:font-color="updateLogoTextTypography('fontColor', $event)"
                    @update:font-size="updateLogoTextTypography('fontSize', $event)"
                    @update:font-weight="updateLogoTextTypography('fontWeight', $event)"
                    @update:font-italic="updateLogoTextTypography('fontItalic', $event)"
                    @update:font-underline="updateLogoTextTypography('fontUnderline', $event)"
                    label="Tipografia das Iniciais"
                />

                <p class="text-xs text-gray-500">
                    Ex: Vinícius e Lilian = V & L
                </p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Menu de Navegação</h3>
            
            <p class="text-sm text-gray-600">Configure quais seções aparecerão no menu de navegação e personalize seus rótulos.</p>

            <TypographyControl
                :font-family="menuTypography.fontFamily"
                :font-color="menuTypography.fontColor"
                :font-size="menuTypography.fontSize"
                :font-weight="menuTypography.fontWeight"
                :font-italic="menuTypography.fontItalic"
                :font-underline="menuTypography.fontUnderline"
                :preview-background-color="menuTypographyPreviewBackgroundColor"
                @update:font-family="updateMenuTypography('fontFamily', $event)"
                @update:font-color="updateMenuTypography('fontColor', $event)"
                @update:font-size="updateMenuTypography('fontSize', $event)"
                @update:font-weight="updateMenuTypography('fontWeight', $event)"
                @update:font-italic="updateMenuTypography('fontItalic', $event)"
                @update:font-underline="updateMenuTypography('fontUnderline', $event)"
                label="Tipografia do Menu"
            />

            <TypographyControl
                :font-family="menuHoverTypography.fontFamily"
                :font-color="menuHoverTypography.fontColor"
                :font-size="menuHoverTypography.fontSize"
                :font-weight="menuHoverTypography.fontWeight"
                :font-italic="menuHoverTypography.fontItalic"
                :font-underline="menuHoverTypography.fontUnderline"
                :preview-background-color="menuHoverTypographyPreviewBackgroundColor"
                @update:font-family="updateMenuHoverTypography('fontFamily', $event)"
                @update:font-color="updateMenuHoverTypography('fontColor', $event)"
                @update:font-size="updateMenuHoverTypography('fontSize', $event)"
                @update:font-weight="updateMenuHoverTypography('fontWeight', $event)"
                @update:font-italic="updateMenuHoverTypography('fontItalic', $event)"
                @update:font-underline="updateMenuHoverTypography('fontUnderline', $event)"
                label="Tipografia do Menu (Hover)"
            />

            <div class="space-y-2">
                <div
                    v-for="section in navigableSections"
                    :key="section.key"
                    class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg"
                    :class="{ 'opacity-50': !section.enabled }"
                >
                    <!-- Switch para mostrar/ocultar no menu -->
                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                :checked="getNavigationItem(section.key).showInMenu"
                                @change="updateNavigationItem(section.key, 'showInMenu', $event.target.checked)"
                                :disabled="!section.enabled"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-wedding-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-wedding-600 peer-disabled:opacity-50 peer-disabled:cursor-not-allowed"></div>
                        </label>
                    </div>

                    <!-- Label da seção -->
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-medium text-gray-500">{{ section.label }}</span>
                            <span v-if="!section.enabled" class="text-xs text-amber-600">(Seção desabilitada)</span>
                        </div>
                        <input
                            type="text"
                            :value="getNavigationItem(section.key).label"
                            @input="updateNavigationItem(section.key, 'label', $event.target.value)"
                            :disabled="!section.enabled"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                            :placeholder="`Texto do menu (padrão: ${section.label})`"
                        />
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-gray-800">Menu mobile</h4>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cor de fundo</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                :value="mobileMenuBackgroundColorHex"
                                @input="handleMobileMenuBackgroundColorInput($event.target.value)"
                                @change="handleMobileMenuBackgroundColorInput($event.target.value)"
                                class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                            />
                            <input
                                type="text"
                                :value="style.mobileMenuBackgroundColor || '#111827'"
                                @input="handleMobileMenuBackgroundColorInput($event.target.value)"
                                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Transparência
                            <span class="ml-1 text-gray-500">{{ mobileMenuTransparency }}%</span>
                        </label>
                        <input
                            type="range"
                            min="0"
                            max="100"
                            step="1"
                            :value="mobileMenuTransparency"
                            @input="handleMobileMenuTransparencyInput($event.target.value)"
                            class="w-full accent-wedding-600"
                        />
                        <p class="mt-1 text-xs text-gray-500">0% = sólido, 100% = totalmente transparente.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Intensidade do desfoque
                        <span class="ml-1 text-gray-500">{{ mobileMenuBlur }}px</span>
                    </label>
                    <input
                        type="range"
                        min="0"
                        max="32"
                        step="1"
                        :value="mobileMenuBlur"
                        @input="handleMobileMenuBlurInput($event.target.value)"
                        class="w-full accent-wedding-600"
                    />
                    <p class="mt-1 text-xs text-gray-500">0px desativa o blur. Valores maiores aumentam o efeito de vidro.</p>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-sm font-semibold text-gray-800">Botão flutuante "Voltar ao topo"</h4>

                <div class="flex items-center">
                    <input
                        type="checkbox"
                        :checked="showBackToTop"
                        @change="updateField('showBackToTop', $event.target.checked)"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                    />
                    <label class="ml-2 text-sm text-gray-700">Exibir botão no canto inferior direito</label>
                </div>

                <div v-if="showBackToTop" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cor do fundo</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                :value="backToTopBackgroundColorHex"
                                @input="updateBackToTopButton('backgroundColor', $event.target.value)"
                                @change="updateBackToTopButton('backgroundColor', $event.target.value)"
                                class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                            />
                            <input
                                type="text"
                                :value="backToTopButton.backgroundColor || '#111827'"
                                @input="updateBackToTopButton('backgroundColor', $event.target.value)"
                                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cor do símbolo</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                :value="backToTopIconColorHex"
                                @input="updateBackToTopButton('iconColor', $event.target.value)"
                                @change="updateBackToTopButton('iconColor', $event.target.value)"
                                class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                            />
                            <input
                                type="text"
                                :value="backToTopButton.iconColor || '#ffffff'"
                                @input="updateBackToTopButton('iconColor', $event.target.value)"
                                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Style Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Altura</label>
                <input
                    type="text"
                    :value="style.height"
                    @input="updateStyle('height', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="80px"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fundo do menu</label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="setBackgroundMode('color')"
                        class="px-3 py-2 text-sm font-medium border rounded-md transition-colors"
                        :class="isTransparentBackground
                            ? 'border-gray-300 text-gray-600 hover:bg-gray-50'
                            : 'border-wedding-500 bg-wedding-50 text-wedding-700'"
                    >
                        Cor
                    </button>
                    <button
                        type="button"
                        @click="setBackgroundMode('transparent')"
                        class="px-3 py-2 text-sm font-medium border rounded-md transition-colors"
                        :class="isTransparentBackground
                            ? 'border-wedding-500 bg-wedding-50 text-wedding-700'
                            : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                    >
                        Transparente
                    </button>
                </div>
            </div>

            <div :class="{ 'opacity-60': isTransparentBackground }">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Fundo</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="headerBackgroundColorHex"
                        @input="handleBackgroundColorInput($event.target.value)"
                        @change="handleBackgroundColorInput($event.target.value)"
                        :disabled="isTransparentBackground"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        @click="pickBackgroundColorFromScreen"
                        :disabled="isTransparentBackground"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="isTransparentBackground ? 'transparent' : (style.backgroundColor || '#ffffff')"
                        @input="handleBackgroundColorInput($event.target.value)"
                        :disabled="isTransparentBackground"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>

            <p v-if="isTransparentBackground" class="text-xs text-gray-500">
                Modo transparente ativo. O menu ficará sobreposto à seção de Destaque.
            </p>

            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="isStickyEnabled"
                    :disabled="isTransparentBackground"
                    @change="updateStyle('sticky', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                />
                <label
                    class="ml-2 text-sm"
                    :class="isTransparentBackground ? 'text-gray-400' : 'text-gray-700'"
                >
                    Menu fixo (sticky)
                </label>
            </div>
        </div>
    </div>

    <!-- Media Gallery Modal -->
    <MediaGalleryModal
        :show="showMediaGallery"
        :max-width="512"
        :max-height="512"
        :allow-crop="true"
        title="Selecionar Logo"
        @close="showMediaGallery = false"
        @select="onImageSelected"
    />
</template>

<style scoped>
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #d87a8d;
}
.focus\:border-wedding-500:focus {
    border-color: #d87a8d;
}
.text-wedding-600 {
    color: #b9163a;
}
.text-wedding-700 {
    color: #4A2F39;
}
.border-wedding-500 {
    border-color: #ffccd9;
}
.bg-wedding-50 {
    background-color: #fff1f2;
}
.text-wedding-700 {
    color: #4A2F39;
}
.hover\:border-wedding-500:hover {
    border-color: #e11d48;
}
.hover\:text-wedding-600:hover {
    color: #b9163a;
}

.logo-type-tabs {
    @apply flex w-full items-end gap-2 border-b border-gray-300;
    border-bottom-color: #ffccd9;
}

.logo-type-tab {
    @apply flex-1 px-4 py-2 text-sm font-semibold text-gray-500 transition-all duration-150;
    border: 1px solid transparent;
    border-bottom: none;
    border-radius: 0.65rem 0.65rem 0 0;
    margin-bottom: -1px;
    background: transparent;
}

.logo-type-tab:hover {
    @apply text-gray-700;
    border-color: #ffccd9;
    background: #fff1f2;
}

.logo-type-tab-active {
    @apply text-wedding-700;
    background: #ffffff;
    border-color: #e11d48;
    box-shadow: inset 0 2px 0 #e11d48;
}
</style>
