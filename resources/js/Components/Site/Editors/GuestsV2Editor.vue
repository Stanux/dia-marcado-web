<script setup>
/**
 * GuestsV2Editor Component
 *
 * Editor for guests V2 section visual settings.
 */
import { computed, ref, watch } from 'vue';
import { useColorField } from '@/Composables/useColorField';
import TypographyControl from '@/Components/Site/TypographyControl.vue';

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
    titleTypography: {
        fontFamily: 'Playfair Display',
        fontColor: '#d87a8d',
        fontSize: 36,
        fontWeight: 700,
        fontItalic: false,
        fontUnderline: false,
    },
    descriptionTypography: {
        fontFamily: 'Playfair Display',
        fontColor: '#4b5563',
        fontSize: 18,
        fontWeight: 400,
        fontItalic: false,
        fontUnderline: false,
    },
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

const guestsBackgroundColorHex = computed(() => normalizeHexColor(localContent.value?.style?.backgroundColor, DEFAULTS.style.backgroundColor));
const titleTypography = computed(() => localContent.value?.titleTypography || DEFAULTS.titleTypography);
const descriptionTypography = computed(() => localContent.value?.descriptionTypography || DEFAULTS.descriptionTypography);

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

    if (!localContent.value.titleTypography || typeof localContent.value.titleTypography !== 'object') {
        localContent.value.titleTypography = {};
    }

    if (!localContent.value.descriptionTypography || typeof localContent.value.descriptionTypography !== 'object') {
        localContent.value.descriptionTypography = {};
    }

    localContent.value.titleTypography = {
        ...DEFAULTS.titleTypography,
        ...localContent.value.titleTypography,
    };

    localContent.value.descriptionTypography = {
        ...DEFAULTS.descriptionTypography,
        ...localContent.value.descriptionTypography,
    };
}

function emitChange() {
    emit('change', cloneDeep(localContent.value));
}

function updateField(field, value) {
    localContent.value[field] = value;
    emitChange();
}

function updateTypography(typographyKey, field, value) {
    if (!localContent.value[typographyKey] || typeof localContent.value[typographyKey] !== 'object') {
        localContent.value[typographyKey] = {};
    }

    localContent.value[typographyKey][field] = value;
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

                <div class="mt-3">
                    <TypographyControl
                        :font-family="titleTypography.fontFamily"
                        :font-color="titleTypography.fontColor"
                        :font-size="titleTypography.fontSize"
                        :font-weight="titleTypography.fontWeight"
                        :font-italic="titleTypography.fontItalic"
                        :font-underline="titleTypography.fontUnderline"
                        :preview-background-color="guestsBackgroundColorHex"
                        label="Tipografia do Título"
                        @update:font-family="updateTypography('titleTypography', 'fontFamily', $event)"
                        @update:font-color="updateTypography('titleTypography', 'fontColor', $event)"
                        @update:font-size="updateTypography('titleTypography', 'fontSize', $event)"
                        @update:font-weight="updateTypography('titleTypography', 'fontWeight', $event)"
                        @update:font-italic="updateTypography('titleTypography', 'fontItalic', $event)"
                        @update:font-underline="updateTypography('titleTypography', 'fontUnderline', $event)"
                    />
                </div>
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
                <div class="mt-3">
                    <TypographyControl
                        :font-family="descriptionTypography.fontFamily"
                        :font-color="descriptionTypography.fontColor"
                        :font-size="descriptionTypography.fontSize"
                        :font-weight="descriptionTypography.fontWeight"
                        :font-italic="descriptionTypography.fontItalic"
                        :font-underline="descriptionTypography.fontUnderline"
                        :preview-background-color="guestsBackgroundColorHex"
                        label="Tipografia da Descrição"
                        @update:font-family="updateTypography('descriptionTypography', 'fontFamily', $event)"
                        @update:font-color="updateTypography('descriptionTypography', 'fontColor', $event)"
                        @update:font-size="updateTypography('descriptionTypography', 'fontSize', $event)"
                        @update:font-weight="updateTypography('descriptionTypography', 'fontWeight', $event)"
                        @update:font-italic="updateTypography('descriptionTypography', 'fontItalic', $event)"
                        @update:font-underline="updateTypography('descriptionTypography', 'fontUnderline', $event)"
                    />
                </div>
            </div>
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
                        :value="guestsBackgroundColorHex"
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
