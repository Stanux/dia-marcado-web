<script setup>
/**
 * HeaderEditor Component
 * 
 * Editor for the Header section of the wedding site.
 * Supports logo upload, title, subtitle, navigation menu, and action button.
 * 
 * @Requirements: 8.1, 8.5, 8.6, 8.7
 */
import { ref, watch, computed } from 'vue';
import { SECTION_IDS, SECTION_LABELS } from '@/Composables/useSiteEditor';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    enabledSections: {
        type: Object,
        default: () => ({}),
    },
});

/**
 * Get available sections for navigation/action button targets (only enabled sections, excluding header/footer)
 */
const availableSectionTargets = computed(() => {
    const targets = [];
    Object.keys(SECTION_IDS).forEach(key => {
        if (props.enabledSections[key]) {
            targets.push({
                value: `#${SECTION_IDS[key]}`,
                label: SECTION_LABELS[key],
            });
        }
    });
    return targets;
});

const emit = defineEmits(['change']);

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

// Watch for external content changes
watch(() => props.content, (newContent) => {
    localContent.value = JSON.parse(JSON.stringify(newContent));
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
 * Update action button field
 */
const updateActionButton = (field, value) => {
    if (!localContent.value.actionButton) {
        localContent.value.actionButton = { label: '', target: '', style: 'primary', icon: null };
    }
    localContent.value.actionButton[field] = value;
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

/**
 * Add navigation item
 */
const addNavigationItem = () => {
    if (!localContent.value.navigation) {
        localContent.value.navigation = [];
    }
    localContent.value.navigation.push({
        label: '',
        target: '',
        type: 'anchor',
    });
    emitChange();
};

/**
 * Update navigation item
 */
const updateNavigationItem = (index, field, value) => {
    if (localContent.value.navigation && localContent.value.navigation[index]) {
        localContent.value.navigation[index][field] = value;
        emitChange();
    }
};

/**
 * Remove navigation item
 */
const removeNavigationItem = (index) => {
    if (localContent.value.navigation) {
        localContent.value.navigation.splice(index, 1);
        emitChange();
    }
};

/**
 * Move navigation item up
 */
const moveNavigationUp = (index) => {
    if (index > 0 && localContent.value.navigation) {
        const item = localContent.value.navigation.splice(index, 1)[0];
        localContent.value.navigation.splice(index - 1, 0, item);
        emitChange();
    }
};

/**
 * Move navigation item down
 */
const moveNavigationDown = (index) => {
    if (localContent.value.navigation && index < localContent.value.navigation.length - 1) {
        const item = localContent.value.navigation.splice(index, 1)[0];
        localContent.value.navigation.splice(index + 1, 0, item);
        emitChange();
    }
};

// Computed properties
const navigation = computed(() => localContent.value.navigation || []);
const logo = computed(() => localContent.value.logo || { url: '', alt: '' });
const actionButton = computed(() => localContent.value.actionButton || { label: '', target: '', style: 'primary', icon: null });
const style = computed(() => localContent.value.style || {});
</script>

<template>
    <div class="space-y-6">
        <!-- Logo Section -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Logo</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL do Logo</label>
                <input
                    type="text"
                    :value="logo.url"
                    @input="updateLogo('url', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="https://exemplo.com/logo.png ou faça upload"
                />
                <p class="mt-1 text-xs text-gray-500">Cole uma URL ou use o gerenciador de mídia para upload</p>
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

        <!-- Title Section -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Textos</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input
                    type="text"
                    :value="localContent.title"
                    @input="updateField('title', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Ex: {noivo} & {noiva}"
                />
                <p class="mt-1 text-xs text-gray-500">Use {noivo}, {noiva}, {data} para placeholders</p>
            </div>

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

            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="localContent.showDate"
                    @change="updateField('showDate', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Exibir data do evento</label>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Menu de Navegação</h3>
                <button
                    @click="addNavigationItem"
                    class="text-sm text-wedding-600 hover:text-wedding-700 font-medium"
                >
                    + Adicionar item
                </button>
            </div>

            <div v-if="navigation.length === 0" class="p-4 bg-gray-50 rounded-lg text-center text-sm text-gray-500">
                Nenhum item de navegação. Clique em "Adicionar item" para começar.
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(item, index) in navigation"
                    :key="index"
                    class="p-4 bg-gray-50 rounded-lg space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Item {{ index + 1 }}</span>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="moveNavigationUp(index)"
                                :disabled="index === 0"
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                title="Mover para cima"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                            <button
                                @click="moveNavigationDown(index)"
                                :disabled="index === navigation.length - 1"
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                title="Mover para baixo"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <button
                                @click="removeNavigationItem(index)"
                                class="p-1 text-red-400 hover:text-red-600"
                                title="Remover"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Rótulo</label>
                            <input
                                type="text"
                                :value="item.label"
                                @input="updateNavigationItem(index, 'label', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="Ex: Sobre nós"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                            <select
                                :value="item.type"
                                @change="updateNavigationItem(index, 'type', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            >
                                <option value="anchor">Âncora (seção)</option>
                                <option value="url">URL externa</option>
                                <option value="action">Ação (RSVP)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Destino</label>
                        <template v-if="item.type === 'anchor'">
                            <select
                                :value="item.target"
                                @change="updateNavigationItem(index, 'target', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            >
                                <option value="">Selecione uma seção</option>
                                <option 
                                    v-for="target in availableSectionTargets" 
                                    :key="target.value" 
                                    :value="target.value"
                                >
                                    {{ target.label }}
                                </option>
                            </select>
                            <p v-if="availableSectionTargets.length === 0" class="mt-1 text-xs text-amber-600">
                                Ative outras seções para vincular
                            </p>
                        </template>
                        <input
                            v-else
                            type="text"
                            :value="item.target"
                            @input="updateNavigationItem(index, 'target', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="https://..."
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Botão de Ação</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rótulo</label>
                    <input
                        type="text"
                        :value="actionButton.label"
                        @input="updateActionButton('label', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        placeholder="Ex: Confirmar Presença"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estilo</label>
                    <select
                        :value="actionButton.style"
                        @change="updateActionButton('style', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="primary">Primário</option>
                        <option value="secondary">Secundário</option>
                        <option value="ghost">Ghost</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Destino</label>
                <select
                    :value="actionButton.target"
                    @change="updateActionButton('target', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="">Selecione uma seção</option>
                    <option 
                        v-for="target in availableSectionTargets" 
                        :key="target.value" 
                        :value="target.value"
                    >
                        {{ target.label }}
                    </option>
                </select>
                <p v-if="availableSectionTargets.length === 0" class="mt-1 text-xs text-amber-600">
                    Ative outras seções para vincular
                </p>
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
                        :value="style.backgroundColor || '#ffffff'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
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
</style>
