<script setup>
/**
 * SectionEditor Component
 * 
 * Dynamic section editor that renders the appropriate editor
 * based on the section type. Emits change events for updates.
 * 
 * @Requirements: 8.1-14.6
 */
import { computed, ref, watch } from 'vue';
import HeaderEditor from './Editors/HeaderEditor.vue';
import HeroEditor from './Editors/HeroEditor.vue';
import SaveTheDateEditor from './Editors/SaveTheDateEditor.vue';
import GiftRegistryEditor from './Editors/GiftRegistryEditor.vue';
import RsvpEditor from './Editors/RsvpEditor.vue';
import PhotoGalleryEditor from './Editors/PhotoGalleryEditor.vue';
import FooterEditor from './Editors/FooterEditor.vue';

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
});

const emit = defineEmits(['change']);

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

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
                @change="handleChange"
                class="flex-1 min-h-0"
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
                                    :value="localContent.primaryColor || '#d4a574'"
                                    @input="updateField('primaryColor', $event.target.value)"
                                    class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                                />
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
                                    :value="localContent.secondaryColor || '#8b7355'"
                                    @input="updateField('secondaryColor', $event.target.value)"
                                    class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                                />
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
                <div class="space-y-6">
                    <!-- Site Configuration Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Configurações do Site</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL do Site</label>
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-3 py-2 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    {{ window.location.origin }}/site/
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
                                type="url"
                                :value="localContent.custom_domain"
                                @input="updateField('custom_domain', $event.target.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="https://meusite.com.br"
                            />
                            <p class="mt-1 text-xs text-gray-500">Opcional: domínio próprio para o site</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senha de Acesso</label>
                            <input
                                type="password"
                                :value="localContent.access_token"
                                @input="updateField('access_token', $event.target.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="Deixe em branco para site público"
                            />
                            <p class="mt-1 text-xs text-gray-500">Deixe em branco para site público</p>
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
