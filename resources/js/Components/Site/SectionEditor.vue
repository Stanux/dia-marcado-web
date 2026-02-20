<script setup>
/**
 * SectionEditor Component
 * 
 * Dynamic section editor that renders the appropriate editor
 * based on the section type. Emits change events for updates.
 * 
 * @Requirements: 8.1-14.6
 */
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { useColorField } from '@/Composables/useColorField';

const props = defineProps({
    sectionType: {
        type: String,
        required: true,
    },
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

const emit = defineEmits(['change']);
const { isEyeDropperSupported, normalizeHexColor, pickColorFromScreen } = useColorField();

const HeaderEditor = defineAsyncComponent(() => import('./Editors/HeaderEditor.vue'));
const HeroEditor = defineAsyncComponent(() => import('./Editors/HeroEditor.vue'));
const SaveTheDateEditor = defineAsyncComponent(() => import('./Editors/SaveTheDateEditor.vue'));
const GiftRegistryEditor = defineAsyncComponent(() => import('./Editors/GiftRegistryEditor.vue'));
const RsvpEditor = defineAsyncComponent(() => import('./Editors/RsvpEditor.vue'));
const PhotoGalleryEditor = defineAsyncComponent(() => import('./Editors/PhotoGalleryEditor.vue'));
const FooterEditor = defineAsyncComponent(() => import('./Editors/FooterEditor.vue'));

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));
const showPassword = ref(false);

// Watch for external content changes
watch(() => props.content, (newContent) => {
    localContent.value = JSON.parse(JSON.stringify(newContent));
}, { deep: true });

/**
 * Handle change from child editor components
 */
const handleChange = (data) => {
    localContent.value = { ...data };
    emit('change', { ...localContent.value });
};

/**
 * Update a field and emit change (for meta/theme/settings)
 */
const updateField = (field, value) => {
    localContent.value[field] = value;
    emit('change', { ...localContent.value });
};

const emitSettingsSave = () => {
    emit('change', { ...localContent.value, __saveSettings: true });
};

/**
 * Update a nested field and emit change
 */
const updateNestedField = (parent, field, value) => {
    if (!localContent.value[parent]) {
        localContent.value[parent] = {};
    }
    localContent.value[parent][field] = value;
    emit('change', { ...localContent.value });
};

/**
 * Update style field
 */
const updateStyle = (field, value) => {
    updateNestedField('style', field, value);
};

/**
 * Format date for display
 */
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

/**
 * Section titles for display
 */
const sectionTitles = {
    header: 'Cabeçalho',
    hero: 'Seção de Destaque',
    saveTheDate: 'Save the Date',
    giftRegistry: 'Lista de Presentes',
    rsvp: 'Confirmação de Presença (RSVP)',
    photoGallery: 'Galeria de Fotos',
    footer: 'Rodapé',
    meta: 'SEO & Meta Tags',
    theme: 'Tema & Cores',
    settings: 'Configurações',
};

const sectionTitle = computed(() => sectionTitles[props.sectionType] || 'Editor');
const siteOrigin = typeof window !== 'undefined' ? window.location.origin : '';
const themePrimaryColorHex = computed(() => normalizeHexColor(localContent.value.primaryColor, '#d4a574'));
const themeSecondaryColorHex = computed(() => normalizeHexColor(localContent.value.secondaryColor, '#8b7355'));

const pickThemePrimaryColor = () => {
    pickColorFromScreen((hex) => updateField('primaryColor', hex));
};

const pickThemeSecondaryColor = () => {
    pickColorFromScreen((hex) => updateField('secondaryColor', hex));
};

/**
 * Map section types to their editor components
 */
const editorComponents = {
    header: HeaderEditor,
    hero: HeroEditor,
    saveTheDate: SaveTheDateEditor,
    giftRegistry: GiftRegistryEditor,
    rsvp: RsvpEditor,
    photoGallery: PhotoGalleryEditor,
    footer: FooterEditor,
};

const currentEditor = computed(() => editorComponents[props.sectionType] || null);
const hasCustomEditor = computed(() => !!currentEditor.value);
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col h-full min-h-0">
        <!-- Section Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <h2 class="text-lg font-semibold text-gray-900">{{ sectionTitle }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                Configure os elementos desta seção
            </p>
        </div>

        <!-- Section Content -->
        <div class="p-6 flex-1 flex flex-col overflow-hidden min-h-0">
            <!-- Use dedicated editor component if available -->
            <component
                v-if="hasCustomEditor"
                :is="currentEditor"
                :content="localContent"
                :enabled-sections="enabledSections"
                :logo-initials="logoInitials"
                @change="handleChange"
                class="flex-1 min-h-0 premium-editor-shell"
            />

            <!-- Meta Section Editor -->
            <template v-else-if="sectionType === 'meta'">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Título da Página (SEO)</label>
                        <input
                            type="text"
                            :value="localContent.title"
                            @input="updateField('title', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="Ex: Casamento de João & Maria"
                        />
                        <p class="mt-1 text-xs text-gray-500">Aparece na aba do navegador e nos resultados de busca</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição (SEO)</label>
                        <textarea
                            :value="localContent.description"
                            @input="updateField('description', $event.target.value)"
                            rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="Breve descrição do site para mecanismos de busca"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Imagem Open Graph</label>
                        <input
                            type="text"
                            :value="localContent.ogImage"
                            @input="updateField('ogImage', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="URL da imagem para compartilhamento em redes sociais"
                        />
                        <p class="mt-1 text-xs text-gray-500">Recomendado: 1200x630 pixels</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Canônica</label>
                        <input
                            type="text"
                            :value="localContent.canonical"
                            @input="updateField('canonical', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="https://seusite.com/casamento"
                        />
                    </div>
                </div>
            </template>

            <!-- Theme Section Editor -->
            <template v-else-if="sectionType === 'theme'">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cor Primária</label>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="color"
                                    :value="themePrimaryColorHex"
                                    @input="updateField('primaryColor', $event.target.value)"
                                    @change="updateField('primaryColor', $event.target.value)"
                                    class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                                />
                                <button
                                    v-if="isEyeDropperSupported"
                                    type="button"
                                    @click="pickThemePrimaryColor"
                                    class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                                    title="Capturar cor da tela"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                                    </svg>
                                </button>
                                <input
                                    type="text"
                                    :value="localContent.primaryColor || '#d4a574'"
                                    @input="updateField('primaryColor', $event.target.value)"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cor Secundária</label>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="color"
                                    :value="themeSecondaryColorHex"
                                    @input="updateField('secondaryColor', $event.target.value)"
                                    @change="updateField('secondaryColor', $event.target.value)"
                                    class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                                />
                                <button
                                    v-if="isEyeDropperSupported"
                                    type="button"
                                    @click="pickThemeSecondaryColor"
                                    class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                                    title="Capturar cor da tela"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                                    </svg>
                                </button>
                                <input
                                    type="text"
                                    :value="localContent.secondaryColor || '#8b7355'"
                                    @input="updateField('secondaryColor', $event.target.value)"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                                />
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Família de Fonte</label>
                        <select
                            :value="localContent.fontFamily || 'Playfair Display'"
                            @change="updateField('fontFamily', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        >
                            <option value="Playfair Display">Playfair Display (Elegante)</option>
                            <option value="Cormorant Garamond">Cormorant Garamond (Clássico)</option>
                            <option value="Lora">Lora (Moderno)</option>
                            <option value="Merriweather">Merriweather (Legível)</option>
                            <option value="Roboto">Roboto (Clean)</option>
                            <option value="Open Sans">Open Sans (Neutro)</option>
                            <option value="Montserrat">Montserrat (Contemporâneo)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tamanho Base da Fonte</label>
                        <select
                            :value="localContent.fontSize || '16px'"
                            @change="updateField('fontSize', $event.target.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        >
                            <option value="14px">Pequeno (14px)</option>
                            <option value="16px">Médio (16px)</option>
                            <option value="18px">Grande (18px)</option>
                            <option value="20px">Extra Grande (20px)</option>
                        </select>
                    </div>
                </div>
            </template>

            <!-- Settings Section Editor -->
            <template v-else-if="sectionType === 'settings'">
                <div class="space-y-6 overflow-y-auto pr-2 min-h-0">
                    <!-- Site Configuration Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Configurações do Site</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL do Site</label>
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-3 py-2 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    {{ siteOrigin }}/site/
                                </span>
                                <input
                                    type="text"
                                    :value="localContent.slug"
                                    @input="updateField('slug', $event.target.value)"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-r-md focus:ring-wedding-500 focus:border-wedding-500"
                                    placeholder="meu-casamento"
                                    pattern="[a-z0-9-]+"
                                />
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Apenas letras minúsculas, números e hífens</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Domínio Personalizado</label>
                            <input
                                type="text"
                                inputmode="url"
                                :value="localContent.custom_domain"
                                @input="updateField('custom_domain', $event.target.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="https://meusite.com.br"
                            />
                            <p class="mt-1 text-xs text-gray-500">Opcional: domínio próprio para o site</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senha de Acesso</label>
                            <div class="relative">
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    :value="localContent.access_token"
                                    @input="updateField('access_token', $event.target.value)"
                                    class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                    placeholder="Deixe em branco para site público"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                >
                                    <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Deixe em branco para site público</p>
                            <div v-if="localContent.has_password" class="mt-2">
                                <button
                                    type="button"
                                    @click="updateField('access_token', null)"
                                    class="text-xs text-red-600 hover:text-red-700"
                                >
                                    Remover senha
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Status Section -->
                    <div class="space-y-4 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Status</h3>
                        
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Status de Publicação</span>
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="localContent.is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                >
                                    {{ localContent.is_published ? 'Publicado' : 'Rascunho' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">Use o botão "Publicar" no topo da página para publicar o site</p>
                            
                            <div v-if="localContent.published_at" class="mt-3 pt-3 border-t border-gray-200">
                                <p class="text-xs text-gray-600">
                                    <strong>Publicado em:</strong> {{ formatDate(localContent.published_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Info Alert -->
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-medium mb-1">Dica</p>
                                <p>As alterações nas configurações são salvas automaticamente. Para aplicar as mudanças no site público, não esqueça de publicar usando o botão no topo da página.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-200">
                        <button
                            type="button"
                            @click="emitSettingsSave"
                            class="px-4 py-2 text-sm font-medium text-white bg-wedding-600 rounded-md hover:bg-wedding-700"
                        >
                            Salvar Configurações
                        </button>
                    </div>
                </div>
            </template>

            <!-- Default/Unknown Section -->
            <template v-else>
                <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <p class="text-sm text-gray-600">
                        Editor para esta seção ainda não implementado.
                    </p>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
:deep(.premium-editor-shell) {
    padding-right: 0.25rem;
}

:deep(.premium-editor-shell > div) {
    position: relative;
    border: 1px solid #e5d5c9 !important;
    border-radius: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #fdfaf7 100%);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.95),
        0 10px 28px -24px rgba(58, 34, 18, 0.55);
    padding: 1rem !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

:deep(.premium-editor-shell > div::before) {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    border-radius: 14px 0 0 14px;
    background: linear-gradient(180deg, #d1a787 0%, #a18072 100%);
}

:deep(.premium-editor-shell > div:focus-within) {
    border-color: #c29f89 !important;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 1),
        0 0 0 3px rgba(161, 128, 114, 0.14),
        0 14px 32px -24px rgba(58, 34, 18, 0.65);
}

:deep(.premium-editor-shell > div > h3) {
    margin: 0;
    padding-left: 0.625rem;
    color: #6b5347;
    font-size: 0.72rem;
    letter-spacing: 0.12em;
}

:deep(.premium-editor-shell > div > h3 + *) {
    margin-top: 0.85rem;
}

@media (max-width: 768px) {
    :deep(.premium-editor-shell) {
        padding-right: 0;
    }

    :deep(.premium-editor-shell > div) {
        border-radius: 12px;
        padding: 0.85rem !important;
    }

    :deep(.premium-editor-shell > div::before) {
        width: 3px;
        border-radius: 12px 0 0 12px;
    }

    :deep(.premium-editor-shell > div > h3) {
        padding-left: 0.5rem;
        font-size: 0.68rem;
    }
}
</style>

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
</style>
