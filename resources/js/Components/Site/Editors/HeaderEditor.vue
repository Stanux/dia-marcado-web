<script setup>
/**
 * HeaderEditor Component
 * 
 * Editor for the Header section of the wedding site.
 * Supports logo upload, title, subtitle, navigation menu, and action button.
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

const pickBackgroundColorFromScreen = async () => {
    if (!isEyeDropperSupported.value) {
        return;
    }

    try {
        const eyeDropper = new window.EyeDropper();
        const { sRGBHex } = await eyeDropper.open();

        if (typeof sRGBHex === 'string' && sRGBHex) {
            updateStyle('backgroundColor', sRGBHex.toLowerCase());
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

const initializeNavigation = () => {
    if (!localContent.value.navigation || !Array.isArray(localContent.value.navigation)) {
        localContent.value.navigation = navigableSections.value.map(section => buildNavigationItem(section));
    } else {
        // Normalize existing items that were created before target/type fields existed
        localContent.value.navigation = localContent.value.navigation.map(item => {
            if (!item?.sectionKey) {
                return item;
            }

            const isLegacyHeroLabel = item.sectionKey === 'hero' && (!item.label || item.label === 'Hero');

            return {
                ...item,
                label: isLegacyHeroLabel ? 'Destaque' : (item.label || SECTION_LABELS[item.sectionKey] || item.sectionKey),
                target: item.target || `#${SECTION_IDS[item.sectionKey] || ''}`,
                type: item.type || 'anchor',
            };
        });

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
 * Inserir tag no título
 */
const insertTag = (tag) => {
    const currentTitle = localContent.value.title || '';
    updateField('title', currentTitle + tag);
};

/**
 * Atualizar tipo de logo
 */
const updateLogoType = (type) => {
    if (!localContent.value.logo) {
        localContent.value.logo = { type: 'image', url: '', alt: '' };
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
 * Atualizar tipografia do título
 */
const updateTitleTypography = (field, value) => {
    if (!localContent.value.titleTypography) {
        localContent.value.titleTypography = {
            fontFamily: 'Playfair Display',
            fontColor: '#333333',
            fontSize: 48,
            fontWeight: 700,
            fontItalic: false,
            fontUnderline: false,
        };
    }
    
    localContent.value.titleTypography[field] = value;
    emitChange();
};

/**
 * Atualizar tipografia do subtítulo
 */
const updateSubtitleTypography = (field, value) => {
    if (!localContent.value.subtitleTypography) {
        localContent.value.subtitleTypography = {
            fontFamily: 'Montserrat',
            fontColor: '#666666',
            fontSize: 24,
            fontWeight: 400,
            fontItalic: true,
            fontUnderline: false,
        };
    }
    
    localContent.value.subtitleTypography[field] = value;
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

// Computed properties
const navigation = computed(() => localContent.value.navigation || []);
const logo = computed(() => localContent.value.logo || { type: 'image', url: '', alt: '' });
const logoType = computed(() => logo.value.type || 'image');
const logoText = computed(() => logo.value.text || { initials: ['', ''], connector: '&' });
const logoTextTypography = computed(() => logoText.value.typography || {
    fontFamily: 'Playfair Display',
    fontColor: '#333333',
    fontSize: 48,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
});
const titleTypography = computed(() => localContent.value.titleTypography || {
    fontFamily: 'Playfair Display',
    fontColor: '#333333',
    fontSize: 48,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
});
const subtitleTypography = computed(() => localContent.value.subtitleTypography || {
    fontFamily: 'Montserrat',
    fontColor: '#666666',
    fontSize: 24,
    fontWeight: 400,
    fontItalic: true,
    fontUnderline: false,
});
const style = computed(() => localContent.value.style || {});
const headerBackgroundColorHex = computed(() => normalizeHexColor(style.value.backgroundColor, '#ffffff'));

onMounted(() => {
    isEyeDropperSupported.value = typeof window !== 'undefined' && 'EyeDropper' in window;
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
                        @click="updateLogoType('image')"
                        class="logo-type-tab"
                        :class="{ 'logo-type-tab-active': logoType === 'image' }"
                        :aria-selected="logoType === 'image'"
                        role="tab"
                    >
                        Usar Imagem
                    </button>
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
                    @update:font-family="updateLogoTextTypography('fontFamily', $event)"
                    @update:font-color="updateLogoTextTypography('fontColor', $event)"
                    @update:font-size="updateLogoTextTypography('fontSize', $event)"
                    @update:font-weight="updateLogoTextTypography('fontWeight', $event)"
                    @update:font-italic="updateLogoTextTypography('fontItalic', $event)"
                    @update:font-underline="updateLogoTextTypography('fontUnderline', $event)"
                    label="Tipografia das Iniciais"
                />

                <!-- Preview do texto -->
                <div v-if="logoText.initials[0] || logoText.initials[1]" class="p-4 bg-gray-50 rounded-md text-center">
                    <p 
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
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Preview do logo</p>
                </div>

                <p class="text-xs text-gray-500">
                    Ex: Vinícius e Lilian = V & L
                </p>
            </div>
        </div>

        <!-- Title Section -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Textos</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <div class="space-y-2">
                    <input
                        type="text"
                        :value="localContent.title"
                        @input="updateField('title', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        placeholder="Ex: {nome_1} & {nome_2}"
                    />
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="insertTag('{nome_1}')"
                            class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors"
                        >
                            + {nome_1}
                        </button>
                        <button
                            @click="insertTag('{nome_2}')"
                            class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors"
                        >
                            + {nome_2}
                        </button>
                        <button
                            @click="insertTag('{primeiro_nome_1}')"
                            class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors"
                        >
                            + {primeiro_nome_1}
                        </button>
                        <button
                            @click="insertTag('{primeiro_nome_2}')"
                            class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors"
                        >
                            + {primeiro_nome_2}
                        </button>
                        <button
                            @click="insertTag('{data_extenso}')"
                            class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors"
                        >
                            + {data_extenso}
                        </button>
                        <button
                            @click="insertTag('{data_simples}')"
                            class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors"
                        >
                            + {data_simples}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tipografia do Título -->
            <TypographyControl
                :font-family="titleTypography.fontFamily"
                :font-color="titleTypography.fontColor"
                :font-size="titleTypography.fontSize"
                :font-weight="titleTypography.fontWeight"
                :font-italic="titleTypography.fontItalic"
                :font-underline="titleTypography.fontUnderline"
                @update:font-family="updateTitleTypography('fontFamily', $event)"
                @update:font-color="updateTitleTypography('fontColor', $event)"
                @update:font-size="updateTitleTypography('fontSize', $event)"
                @update:font-weight="updateTitleTypography('fontWeight', $event)"
                @update:font-italic="updateTitleTypography('fontItalic', $event)"
                @update:font-underline="updateTitleTypography('fontUnderline', $event)"
                label="Tipografia do Título"
            />

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subtítulo</label>
                <input
                    type="text"
                    :value="localContent.subtitle"
                    @input="updateField('subtitle', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Ex: Nosso casamento"
                />
            </div>

            <!-- Tipografia do Subtítulo -->
            <TypographyControl
                :font-family="subtitleTypography.fontFamily"
                :font-color="subtitleTypography.fontColor"
                :font-size="subtitleTypography.fontSize"
                :font-weight="subtitleTypography.fontWeight"
                :font-italic="subtitleTypography.fontItalic"
                :font-underline="subtitleTypography.fontUnderline"
                @update:font-family="updateSubtitleTypography('fontFamily', $event)"
                @update:font-color="updateSubtitleTypography('fontColor', $event)"
                @update:font-size="updateSubtitleTypography('fontSize', $event)"
                @update:font-weight="updateSubtitleTypography('fontWeight', $event)"
                @update:font-italic="updateSubtitleTypography('fontItalic', $event)"
                @update:font-underline="updateSubtitleTypography('fontUnderline', $event)"
                label="Tipografia do Subtítulo"
            />
        </div>

        <!-- Navigation Menu -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Menu de Navegação</h3>
            
            <p class="text-sm text-gray-600">Configure quais seções aparecerão no menu de navegação e personalize seus rótulos.</p>

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
        </div>

        <!-- Style Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>
            
            <div class="grid grid-cols-2 gap-4">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alinhamento</label>
                    <select
                        :value="style.alignment"
                        @change="updateStyle('alignment', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="left">Esquerda</option>
                        <option value="center">Centro</option>
                        <option value="right">Direita</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Fundo</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="headerBackgroundColorHex"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        @change="updateStyle('backgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        @click="pickBackgroundColorFromScreen"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="style.backgroundColor || '#ffffff'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>

            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="style.sticky"
                    @change="updateStyle('sticky', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Menu fixo (sticky)</label>
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
    --tw-ring-color: #b8998a;
}
.focus\:border-wedding-500:focus {
    border-color: #b8998a;
}
.text-wedding-600 {
    color: #a18072;
}
.text-wedding-700 {
    color: #8b6b5d;
}
.border-wedding-500 {
    border-color: #b8998a;
}
.bg-wedding-50 {
    background-color: #f5f1ee;
}
.text-wedding-700 {
    color: #8b6b5d;
}
.hover\:border-wedding-500:hover {
    border-color: #b8998a;
}
.hover\:text-wedding-600:hover {
    color: #a18072;
}

.logo-type-tabs {
    @apply flex w-full items-end gap-2 border-b border-gray-300;
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
    border-color: #e5e7eb;
    background: #f9fafb;
}

.logo-type-tab-active {
    @apply text-wedding-700;
    background: #ffffff;
    border-color: #b8998a;
    box-shadow: inset 0 2px 0 #b8998a;
}
</style>
