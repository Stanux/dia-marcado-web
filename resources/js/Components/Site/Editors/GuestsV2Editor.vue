<script setup>
/**
 * GuestsV2Editor Component
 *
 * Initial editor for the new guests section.
 * The legacy RSVP section remains hidden/disabled and this section becomes
 * the new entry point for future Guests V2 rules.
 */
import { ref, watch } from 'vue';
import { useColorField } from '@/Composables/useColorField';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);
const { isEyeDropperSupported, normalizeHexColor, pickColorFromScreen } = useColorField();

const DEFAULTS = {
    navigation: {
        label: 'Convidados',
        showInMenu: true,
    },
    title: 'Convidados',
    description: 'Utilize o convite recebido para confirmar presença e manter seus dados atualizados.',
    helperText: 'Este formulário utiliza as regras do módulo de convidados V2.',
    style: {
        backgroundColor: '#f5f5f5',
        layout: 'card',
        containerMaxWidth: 'max-w-6xl',
        showCard: true,
    },
};

const localContent = ref(cloneDeep(props.content));

watch(
    () => props.content,
    (newContent) => {
        localContent.value = cloneDeep(newContent);
        ensureStructure();
    },
    { deep: true }
);

const guestsBackgroundColorHex = computedBackgroundColor();

function cloneDeep(value) {
    return JSON.parse(JSON.stringify(value || {}));
}

function ensureStructure() {
    if (!localContent.value || typeof localContent.value !== 'object') {
        localContent.value = {};
    }

    if (!localContent.value.navigation || typeof localContent.value.navigation !== 'object') {
        localContent.value.navigation = {};
    }

    if (!localContent.value.style || typeof localContent.value.style !== 'object') {
        localContent.value.style = {};
    }

    if (localContent.value.navigation.label === undefined) {
        localContent.value.navigation.label = DEFAULTS.navigation.label;
    }

    if (localContent.value.navigation.showInMenu === undefined) {
        localContent.value.navigation.showInMenu = DEFAULTS.navigation.showInMenu;
    }

    if (localContent.value.title === undefined) {
        localContent.value.title = DEFAULTS.title;
    }

    if (localContent.value.description === undefined) {
        localContent.value.description = DEFAULTS.description;
    }

    if (localContent.value.helperText === undefined) {
        localContent.value.helperText = DEFAULTS.helperText;
    }

    if (localContent.value.style.backgroundColor === undefined) {
        localContent.value.style.backgroundColor = DEFAULTS.style.backgroundColor;
    }

    if (localContent.value.style.layout === undefined) {
        localContent.value.style.layout = DEFAULTS.style.layout;
    }

    if (localContent.value.style.containerMaxWidth === undefined) {
        localContent.value.style.containerMaxWidth = DEFAULTS.style.containerMaxWidth;
    }

    if (localContent.value.style.showCard === undefined) {
        localContent.value.style.showCard = DEFAULTS.style.showCard;
    }
}

function computedBackgroundColor() {
    return () => normalizeHexColor(localContent.value?.style?.backgroundColor, DEFAULTS.style.backgroundColor);
}

function emitChange() {
    emit('change', cloneDeep(localContent.value));
}

function updateField(field, value) {
    localContent.value[field] = value;
    emitChange();
}

function updateNavigation(field, value) {
    localContent.value.navigation[field] = value;
    emitChange();
}

function updateStyle(field, value) {
    localContent.value.style[field] = value;
    emitChange();
}

function pickBackgroundColor() {
    pickColorFromScreen((hex) => updateStyle('backgroundColor', hex));
}

ensureStructure();
</script>

<template>
    <div class="space-y-6 h-full overflow-y-auto">
        <div class="p-4 rounded-lg border border-emerald-200 bg-emerald-50">
            <h4 class="text-sm font-semibold text-emerald-900">Nova seção de convidados (V2)</h4>
            <p class="mt-1 text-sm text-emerald-800">
                Esta seção foi criada do zero para substituir a versão antiga.
                A seção legada permanece oculta e desabilitada.
            </p>
        </div>

        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Conteúdo</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input
                    type="text"
                    :value="localContent.title"
                    @input="updateField('title', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Convidados"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea
                    :value="localContent.description"
                    @input="updateField('description', $event.target.value)"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Descreva como os convidados devem confirmar."
                ></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Texto de apoio</label>
                <input
                    type="text"
                    :value="localContent.helperText"
                    @input="updateField('helperText', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Este formulário utiliza as regras do módulo de convidados V2."
                />
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Navegação</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rótulo no menu</label>
                <input
                    type="text"
                    :value="localContent.navigation.label"
                    @input="updateNavigation('label', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Convidados"
                />
            </div>

            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                <input
                    type="checkbox"
                    class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                    :checked="localContent.navigation.showInMenu"
                    @change="updateNavigation('showInMenu', $event.target.checked)"
                />
                <span class="text-sm text-gray-700">Exibir esta seção no menu do cabeçalho</span>
            </label>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Aparência</h3>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Layout visual</label>
                    <select
                        :value="localContent.style.layout"
                        @change="updateStyle('layout', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="card">Card destacado</option>
                        <option value="clean">Visual limpo</option>
                        <option value="compact">Visual compacto</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Largura do conteúdo</label>
                    <select
                        :value="localContent.style.containerMaxWidth"
                        @change="updateStyle('containerMaxWidth', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="max-w-6xl">Largura padrão</option>
                        <option value="max-w-7xl">Largura ampla</option>
                    </select>
                </div>
            </div>

            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                <input
                    type="checkbox"
                    class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                    :checked="localContent.style.showCard"
                    @change="updateStyle('showCard', $event.target.checked)"
                />
                <span class="text-sm text-gray-700">Exibir card interno para o conteúdo</span>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de fundo</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="guestsBackgroundColorHex()"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        @change="updateStyle('backgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        @click="pickBackgroundColor"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="localContent.style.backgroundColor || '#f5f5f5'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
