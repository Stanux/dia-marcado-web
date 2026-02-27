<script setup>
/**
 * RsvpEditor Component
 *
 * Guided editor for RSVP section configuration.
 * Bridges guest operations (access rules, events, questions) with visual setup
 * in a language suitable for non-technical users.
 */
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useColorField } from '@/Composables/useColorField';
import TypographyControl from '../TypographyControl.vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);
const page = usePage();
const { isEyeDropperSupported, normalizeHexColor, pickColorFromScreen } = useColorField();

const DEFAULT_NAVIGATION = {
    label: 'Confirme Presença',
    showInMenu: true,
};

const DEFAULTS = {
    titleTypography: {
        fontFamily: 'Playfair Display',
        fontColor: '#d87a8d',
        fontSize: 36,
        fontWeight: 700,
        fontItalic: false,
        fontUnderline: false,
    },
    subtitleTypography: {
        fontFamily: 'Playfair Display',
        fontColor: '#4b5563',
        fontSize: 18,
        fontWeight: 400,
        fontItalic: false,
        fontUnderline: false,
    },
    access: {
        mode: 'inherit',
        allowResponseUpdate: true,
        requireInviteToken: false,
    },
    eventSelection: {
        mode: 'all_active',
        selectedEventIds: [],
        featuredEventId: null,
    },
    fields: {
        collectName: true,
        collectEmail: true,
        collectPhone: true,
        requireEmail: false,
        requirePhone: false,
        showDynamicQuestions: true,
    },
    messages: {
        success: 'RSVP enviado com sucesso!',
        genericError: 'Erro ao enviar RSVP.',
        nameRequired: 'Informe seu nome.',
        eventRequired: 'Selecione um evento.',
        tokenLimitReached: 'Este token já atingiu o limite de uso. Solicite um novo link para alterar sua confirmação.',
        tokenInvalid: 'Este link de convite é inválido.',
        tokenExpired: 'Este link de convite expirou.',
        tokenRevoked: 'Este link de convite foi revogado.',
        tokenRequired: 'Este convite exige um link com token para confirmação.',
        restrictedAccess: 'Não encontramos seu cadastro na lista de convidados para este evento.',
        submitLabel: 'Confirmar Presença',
        submitLoadingLabel: 'Enviando...',
        submitDisabledTokenLabel: 'Token sem novos usos',
    },
    labels: {
        name: 'Nome completo',
        email: 'Email',
        phone: 'Telefone',
        event: 'Evento',
        status: 'Confirmação',
    },
    statusOptions: {
        showConfirmed: true,
        showMaybe: true,
        showDeclined: true,
        confirmedLabel: 'Confirmo presença',
        maybeLabel: 'Talvez',
        declinedLabel: 'Não poderei comparecer',
    },
    style: {
        backgroundColor: '#f5f5f5',
        layout: 'card',
        containerMaxWidth: 'max-w-xl',
        showCard: true,
    },
    preview: {
        scenario: 'default',
    },
};

const ACCESS_OPTIONS = [
    {
        value: 'inherit',
        title: 'Usar configuração da plataforma',
        description: 'Segue automaticamente o modo definido em Configurações RSVP.',
    },
    {
        value: 'open',
        title: 'Aberto para qualquer pessoa',
        description: 'Qualquer pessoa com o link do site consegue confirmar presença.',
    },
    {
        value: 'restricted',
        title: 'Somente lista de convidados',
        description: 'Só confirma quem já estiver cadastrado na lista de convidados.',
    },
    {
        value: 'token_only',
        title: 'Somente por link de convite',
        description: 'A confirmação só acontece com token válido enviado no convite.',
    },
];

const localContent = ref(cloneDeep(props.content));

const wedding = computed(() => page.props.wedding || {});

const guestEvents = computed(() => {
    const events = wedding.value?.guest_events;

    if (!Array.isArray(events)) {
        return [];
    }

    return events.map((event) => ({
        ...event,
        id: String(event.id),
    }));
});

const activeGuestEvents = computed(() => {
    return guestEvents.value.filter((event) => event.is_active);
});

const inheritedAccessMode = computed(() => {
    return wedding.value?.settings?.rsvp_access === 'restricted'
        ? 'restricted'
        : 'open';
});

const inheritedAccessLabel = computed(() => {
    return inheritedAccessMode.value === 'restricted'
        ? 'Lista de convidados (restrito)'
        : 'Aberto';
});

watch(
    () => props.content,
    (newContent) => {
        localContent.value = cloneDeep(newContent);
        ensureStructure();
        harmonize();
    },
    { deep: true }
);

watch(
    activeGuestEvents,
    () => {
        if (harmonize()) {
            emitChange();
        }
    },
    { deep: true }
);

const access = computed(() => localContent.value.access || DEFAULTS.access);
const eventSelection = computed(() => localContent.value.eventSelection || DEFAULTS.eventSelection);
const fields = computed(() => localContent.value.fields || DEFAULTS.fields);
const messages = computed(() => localContent.value.messages || DEFAULTS.messages);
const labels = computed(() => localContent.value.labels || DEFAULTS.labels);
const statusOptions = computed(() => localContent.value.statusOptions || DEFAULTS.statusOptions);
const style = computed(() => localContent.value.style || DEFAULTS.style);
const preview = computed(() => localContent.value.preview || DEFAULTS.preview);
const titleTypography = computed(() => localContent.value.titleTypography || DEFAULTS.titleTypography);
const subtitleTypography = computed(() => localContent.value.subtitleTypography || DEFAULTS.subtitleTypography);

const rsvpBackgroundColorHex = computed(() => {
    return normalizeHexColor(style.value.backgroundColor, '#f5f5f5');
});
const rsvpTypographyPreviewBackgroundColor = computed(() => rsvpBackgroundColorHex.value);

const selectedEventIds = computed(() => {
    if (!Array.isArray(eventSelection.value.selectedEventIds)) {
        return [];
    }

    return eventSelection.value.selectedEventIds.map(String);
});

const selectableEvents = computed(() => {
    return activeGuestEvents.value;
});

const availableEventsForForm = computed(() => {
    if (eventSelection.value.mode !== 'selected') {
        return selectableEvents.value;
    }

    const selected = new Set(selectedEventIds.value);

    return selectableEvents.value.filter((event) => selected.has(String(event.id)));
});

const hasActiveEvents = computed(() => selectableEvents.value.length > 0);

const previewScenarioHelp = computed(() => {
    const current = preview.value.scenario || 'default';

    const map = {
        default: 'Mostra o fluxo padrão de confirmação.',
        valid_token: 'Simula convidado acessando com token válido.',
        invalid_token: 'Simula token inválido.',
        token_limit_reached: 'Simula token com limite de uso atingido.',
        restricted_denied: 'Simula acesso negado no modo restrito.',
        success: 'Simula formulário enviado com sucesso.',
    };

    return map[current] || map.default;
});

function cloneDeep(value) {
    return JSON.parse(JSON.stringify(value || {}));
}

function ensureStructure() {
    if (!localContent.value.navigation || typeof localContent.value.navigation !== 'object') {
        localContent.value.navigation = cloneDeep(DEFAULT_NAVIGATION);
    }

    assignMissing(localContent.value, {
        title: 'Confirme sua Presença',
        description: '',
    });

    assignMissing(localContent.value, {
        access: DEFAULTS.access,
        eventSelection: DEFAULTS.eventSelection,
        fields: DEFAULTS.fields,
        messages: DEFAULTS.messages,
        labels: DEFAULTS.labels,
        statusOptions: DEFAULTS.statusOptions,
        style: DEFAULTS.style,
        preview: DEFAULTS.preview,
        titleTypography: DEFAULTS.titleTypography,
        subtitleTypography: DEFAULTS.subtitleTypography,
    });
}

function assignMissing(target, defaults) {
    Object.entries(defaults).forEach(([key, defaultValue]) => {
        if (Array.isArray(defaultValue)) {
            if (!Array.isArray(target[key])) {
                target[key] = cloneDeep(defaultValue);
            }
            return;
        }

        if (defaultValue && typeof defaultValue === 'object') {
            if (!target[key] || typeof target[key] !== 'object' || Array.isArray(target[key])) {
                target[key] = {};
            }

            assignMissing(target[key], defaultValue);
            return;
        }

        if (target[key] === undefined) {
            target[key] = defaultValue;
        }
    });
}

function harmonize() {
    let changed = false;

    if (access.value.mode === 'token_only' && !access.value.requireInviteToken) {
        localContent.value.access.requireInviteToken = true;
        changed = true;
    }

    if (fields.value.requireEmail && !fields.value.collectEmail) {
        localContent.value.fields.collectEmail = true;
        changed = true;
    }

    if (fields.value.requirePhone && !fields.value.collectPhone) {
        localContent.value.fields.collectPhone = true;
        changed = true;
    }

    if (!Array.isArray(localContent.value.eventSelection.selectedEventIds)) {
        localContent.value.eventSelection.selectedEventIds = [];
        changed = true;
    }

    const activeIds = selectableEvents.value.map((event) => String(event.id));
    const currentSelected = selectedEventIds.value;
    const normalizedSelected = currentSelected.filter((id) => activeIds.includes(String(id)));

    if (JSON.stringify(currentSelected) !== JSON.stringify(normalizedSelected)) {
        localContent.value.eventSelection.selectedEventIds = normalizedSelected;
        changed = true;
    }

    if (
        eventSelection.value.mode === 'selected' &&
        localContent.value.eventSelection.selectedEventIds.length === 0 &&
        activeIds.length > 0
    ) {
        localContent.value.eventSelection.selectedEventIds = [activeIds[0]];
        changed = true;
    }

    const availableIds = availableEventsForForm.value.map((event) => String(event.id));
    const featured = localContent.value.eventSelection.featuredEventId
        ? String(localContent.value.eventSelection.featuredEventId)
        : null;

    if (availableIds.length === 0 && featured !== null) {
        localContent.value.eventSelection.featuredEventId = null;
        changed = true;
    }

    if (availableIds.length > 0 && !featured) {
        localContent.value.eventSelection.featuredEventId = availableIds[0];
        changed = true;
    }

    if (featured && !availableIds.includes(featured)) {
        localContent.value.eventSelection.featuredEventId = availableIds[0] || null;
        changed = true;
    }

    return changed;
}

function emitChange() {
    emit('change', cloneDeep(localContent.value));
}

function updateField(field, value) {
    localContent.value[field] = value;
    emitChange();
}

function updateNested(path, value) {
    const [group, field] = path;

    if (!localContent.value[group] || typeof localContent.value[group] !== 'object') {
        localContent.value[group] = {};
    }

    localContent.value[group][field] = value;

    if (harmonize()) {
        emitChange();
        return;
    }

    emitChange();
}

function updateAccessMode(mode) {
    localContent.value.access.mode = mode;

    if (mode === 'token_only') {
        localContent.value.access.requireInviteToken = true;
    }

    if (harmonize()) {
        emitChange();
        return;
    }

    emitChange();
}

function updateEventSelectionMode(mode) {
    localContent.value.eventSelection.mode = mode;

    if (harmonize()) {
        emitChange();
        return;
    }

    emitChange();
}

function toggleEvent(eventId, checked) {
    const selected = new Set(selectedEventIds.value);

    if (checked) {
        selected.add(String(eventId));
    } else {
        selected.delete(String(eventId));
    }

    localContent.value.eventSelection.selectedEventIds = Array.from(selected);

    if (harmonize()) {
        emitChange();
        return;
    }

    emitChange();
}

function updateStyle(field, value) {
    updateNested(['style', field], value);
}

function updateTypography(typographyKey, field, value) {
    if (!localContent.value[typographyKey] || typeof localContent.value[typographyKey] !== 'object') {
        localContent.value[typographyKey] = {};
    }

    localContent.value[typographyKey][field] = value;
    emitChange();
}

function pickRsvpBackgroundColor() {
    pickColorFromScreen((hex) => updateStyle('backgroundColor', hex));
}

ensureStructure();
harmonize();
</script>

<template>
    <div class="space-y-6 h-full overflow-y-auto">
        <div class="p-4 rounded-lg border border-rose-200 bg-rose-50">
            <h4 class="text-sm font-semibold text-rose-900">Configuração inteligente de RSVP</h4>
            <p class="mt-1 text-sm text-rose-800">
                Configure abaixo como os convidados vão confirmar presença no site.
                As regras já estão conectadas ao módulo de convidados da plataforma.
            </p>
        </div>

        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Conteúdo da Seção</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input
                    type="text"
                    :value="localContent.title"
                    @input="updateField('title', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Confirme sua Presença"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea
                    :value="localContent.description"
                    @input="updateField('description', $event.target.value)"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Explique em poucas palavras como a pessoa deve confirmar presença."
                ></textarea>
            </div>

            <TypographyControl
                :font-family="titleTypography.fontFamily || 'Playfair Display'"
                :font-color="titleTypography.fontColor || '#d87a8d'"
                :font-size="titleTypography.fontSize || 36"
                :font-weight="titleTypography.fontWeight || 700"
                :font-italic="titleTypography.fontItalic || false"
                :font-underline="titleTypography.fontUnderline || false"
                :preview-background-color="rsvpTypographyPreviewBackgroundColor"
                label="Tipografia do Título"
                @update:font-family="updateTypography('titleTypography', 'fontFamily', $event)"
                @update:font-color="updateTypography('titleTypography', 'fontColor', $event)"
                @update:font-size="updateTypography('titleTypography', 'fontSize', $event)"
                @update:font-weight="updateTypography('titleTypography', 'fontWeight', $event)"
                @update:font-italic="updateTypography('titleTypography', 'fontItalic', $event)"
                @update:font-underline="updateTypography('titleTypography', 'fontUnderline', $event)"
            />

            <TypographyControl
                :font-family="subtitleTypography.fontFamily || 'Playfair Display'"
                :font-color="subtitleTypography.fontColor || '#4b5563'"
                :font-size="subtitleTypography.fontSize || 18"
                :font-weight="subtitleTypography.fontWeight || 400"
                :font-italic="subtitleTypography.fontItalic || false"
                :font-underline="subtitleTypography.fontUnderline || false"
                :preview-background-color="rsvpTypographyPreviewBackgroundColor"
                label="Tipografia do Subtítulo"
                @update:font-family="updateTypography('subtitleTypography', 'fontFamily', $event)"
                @update:font-color="updateTypography('subtitleTypography', 'fontColor', $event)"
                @update:font-size="updateTypography('subtitleTypography', 'fontSize', $event)"
                @update:font-weight="updateTypography('subtitleTypography', 'fontWeight', $event)"
                @update:font-italic="updateTypography('subtitleTypography', 'fontItalic', $event)"
                @update:font-underline="updateTypography('subtitleTypography', 'fontUnderline', $event)"
            />
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">1. Quem Pode Confirmar?</h3>

            <div class="grid gap-3">
                <button
                    v-for="option in ACCESS_OPTIONS"
                    :key="option.value"
                    type="button"
                    @click="updateAccessMode(option.value)"
                    class="text-left px-4 py-3 rounded-lg border transition"
                    :class="access.mode === option.value
                        ? 'border-wedding-500 bg-rose-50 ring-1 ring-wedding-300'
                        : 'border-gray-300 bg-white hover:bg-gray-50'"
                >
                    <p class="text-sm font-semibold text-gray-900">{{ option.title }}</p>
                    <p class="text-xs text-gray-600 mt-1">{{ option.description }}</p>
                </button>
            </div>

            <div
                v-if="access.mode === 'inherit'"
                class="p-3 rounded-lg border border-sky-200 bg-sky-50 text-sm text-sky-900"
            >
                Modo herdado atualmente: <strong>{{ inheritedAccessLabel }}</strong>.
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="access.allowResponseUpdate"
                        @change="updateNested(['access', 'allowResponseUpdate'], $event.target.checked)"
                    />
                    <span class="text-sm text-gray-700">
                        Permitir que o convidado altere a resposta depois.
                    </span>
                </label>

                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="access.requireInviteToken"
                        :disabled="access.mode === 'token_only'"
                        @change="updateNested(['access', 'requireInviteToken'], $event.target.checked)"
                    />
                    <span class="text-sm text-gray-700">
                        Exigir token de convite para responder.
                    </span>
                </label>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">2. Quais Eventos Aparecem?</h3>

            <div v-if="!hasActiveEvents" class="p-4 rounded-lg border border-amber-200 bg-amber-50 text-sm text-amber-900">
                Nenhum evento RSVP ativo foi encontrado. Crie ou ative eventos em <strong>Eventos RSVP</strong> para exibir opções nesta seção.
            </div>

            <div v-else class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modo de seleção de eventos</label>
                    <select
                        :value="eventSelection.mode"
                        @change="updateEventSelectionMode($event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="all_active">Mostrar todos os eventos ativos</option>
                        <option value="selected">Escolher eventos específicos</option>
                    </select>
                </div>

                <div v-if="eventSelection.mode === 'selected'" class="space-y-2 p-4 rounded-lg border border-gray-200 bg-gray-50">
                    <p class="text-sm font-medium text-gray-800">Selecione os eventos:</p>

                    <label
                        v-for="event in selectableEvents"
                        :key="event.id"
                        class="flex items-start gap-3 text-sm text-gray-700"
                    >
                        <input
                            type="checkbox"
                            class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                            :checked="selectedEventIds.includes(String(event.id))"
                            @change="toggleEvent(event.id, $event.target.checked)"
                        />
                        <span>
                            {{ event.name }}
                            <span class="text-xs text-gray-500 block" v-if="event.event_date">
                                {{ new Date(event.event_date).toLocaleDateString('pt-BR') }}
                            </span>
                        </span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Evento principal (pré-selecionado)</label>
                    <select
                        :value="eventSelection.featuredEventId || ''"
                        @change="updateNested(['eventSelection', 'featuredEventId'], $event.target.value || null)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        :disabled="availableEventsForForm.length === 0"
                    >
                        <option value="" disabled>Selecione um evento</option>
                        <option v-for="event in availableEventsForForm" :key="event.id" :value="String(event.id)">
                            {{ event.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">3. Quais Dados Pedir do Convidado?</h3>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="fields.collectName"
                        @change="updateNested(['fields', 'collectName'], $event.target.checked)"
                    />
                    <span class="text-sm text-gray-700">Solicitar nome</span>
                </label>

                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="fields.collectEmail"
                        @change="updateNested(['fields', 'collectEmail'], $event.target.checked)"
                    />
                    <span class="text-sm text-gray-700">Solicitar e-mail</span>
                </label>

                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="fields.collectPhone"
                        @change="updateNested(['fields', 'collectPhone'], $event.target.checked)"
                    />
                    <span class="text-sm text-gray-700">Solicitar telefone</span>
                </label>

                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="fields.showDynamicQuestions"
                        @change="updateNested(['fields', 'showDynamicQuestions'], $event.target.checked)"
                    />
                    <span class="text-sm text-gray-700">Exibir perguntas personalizadas dos eventos</span>
                </label>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-white">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="fields.requireEmail"
                        @change="updateNested(['fields', 'requireEmail'], $event.target.checked)"
                    />
                    <span class="text-sm text-gray-700">Tornar e-mail obrigatório</span>
                </label>

                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-white">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="fields.requirePhone"
                        @change="updateNested(['fields', 'requirePhone'], $event.target.checked)"
                    />
                    <span class="text-sm text-gray-700">Tornar telefone obrigatório</span>
                </label>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">4. Opções de Resposta</h3>

            <div class="grid gap-3 md:grid-cols-3">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="statusOptions.showConfirmed"
                        @change="updateNested(['statusOptions', 'showConfirmed'], $event.target.checked)"
                    />
                    Exibir "Confirmo"
                </label>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="statusOptions.showMaybe"
                        @change="updateNested(['statusOptions', 'showMaybe'], $event.target.checked)"
                    />
                    Exibir "Talvez"
                </label>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        :checked="statusOptions.showDeclined"
                        @change="updateNested(['statusOptions', 'showDeclined'], $event.target.checked)"
                    />
                    Exibir "Não vou"
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Texto do botão</label>
                    <input
                        type="text"
                        :value="messages.submitLabel"
                        @input="updateNested(['messages', 'submitLabel'], $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Texto durante envio</label>
                    <input
                        type="text"
                        :value="messages.submitLoadingLabel"
                        @input="updateNested(['messages', 'submitLoadingLabel'], $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    />
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">5. Mensagens para o Convidado</h3>

            <div class="grid gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem de sucesso</label>
                    <input
                        type="text"
                        :value="messages.success"
                        @input="updateNested(['messages', 'success'], $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem de erro geral</label>
                    <input
                        type="text"
                        :value="messages.genericError"
                        @input="updateNested(['messages', 'genericError'], $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem de acesso restrito</label>
                    <input
                        type="text"
                        :value="messages.restrictedAccess"
                        @input="updateNested(['messages', 'restrictedAccess'], $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem de token no limite</label>
                    <input
                        type="text"
                        :value="messages.tokenLimitReached"
                        @input="updateNested(['messages', 'tokenLimitReached'], $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem quando token é obrigatório</label>
                    <input
                        type="text"
                        :value="messages.tokenRequired"
                        @input="updateNested(['messages', 'tokenRequired'], $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    />
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">6. Aparência da Seção</h3>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Layout visual</label>
                    <select
                        :value="style.layout"
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
                        :value="style.containerMaxWidth"
                        @change="updateStyle('containerMaxWidth', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="max-w-lg">Pequena</option>
                        <option value="max-w-xl">Média</option>
                        <option value="max-w-2xl">Larga</option>
                    </select>
                </div>
            </div>

            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                <input
                    type="checkbox"
                    class="mt-1 h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                    :checked="style.showCard"
                    @change="updateStyle('showCard', $event.target.checked)"
                />
                <span class="text-sm text-gray-700">Exibir card interno para o formulário</span>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de fundo</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="rsvpBackgroundColorHex"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        @change="updateStyle('backgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        @click="pickRsvpBackgroundColor"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="style.backgroundColor || '#f5f5f5'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">7. Simulador de Cenário no Preview</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cenário para simular</label>
                <select
                    :value="preview.scenario"
                    @change="updateNested(['preview', 'scenario'], $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="default">Padrão</option>
                    <option value="valid_token">Token válido</option>
                    <option value="invalid_token">Token inválido</option>
                    <option value="token_limit_reached">Token com limite atingido</option>
                    <option value="restricted_denied">Acesso negado (restrito)</option>
                    <option value="success">Resposta enviada com sucesso</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">{{ previewScenarioHelp }}</p>
            </div>
        </div>

        <div class="p-4 rounded-lg border border-gray-200 bg-gray-50 text-xs text-gray-600">
            Dica: para configurar regras operacionais avançadas de convites e tokens,
            mantenha os eventos ativos em <strong>Eventos RSVP</strong> e o modo geral em
            <strong>Configurações RSVP</strong>.
        </div>
    </div>
</template>

<style scoped>
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #be123c;
}

.focus\:border-wedding-500:focus {
    border-color: #e11d48;
}

.text-wedding-600 {
    color: #b9163a;
}

.border-wedding-500 {
    border-color: #e11d48;
}

.ring-wedding-300 {
    --tw-ring-color: #ffccd9;
}
</style>
