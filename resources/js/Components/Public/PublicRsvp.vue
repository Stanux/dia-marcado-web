<script setup>
/**
 * PublicRsvp Component
 *
 * Renders the RSVP section with real submission on public pages
 * and realistic simulation when used in editor preview.
 */
import { computed, reactive, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    wedding: {
        type: Object,
        default: () => ({}),
    },
    siteSlug: {
        type: String,
        default: null,
    },
    inviteTokenState: {
        type: String,
        default: null,
    },
    isPreview: {
        type: Boolean,
        default: false,
    },
    previewScenario: {
        type: String,
        default: null,
    },
});

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
    style: {
        backgroundColor: '#f8f6f4',
        layout: 'card',
        containerMaxWidth: 'max-w-xl',
        showCard: true,
    },
    access: {
        mode: 'inherit',
        allowResponseUpdate: true,
        requireInviteToken: false,
    },
    fields: {
        collectName: true,
        collectEmail: true,
        collectPhone: true,
        requireEmail: false,
        requirePhone: false,
        showDynamicQuestions: true,
    },
    labels: {
        name: 'Nome completo',
        email: 'Email',
        phone: 'Telefone',
        event: 'Evento',
        status: 'Confirmação',
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
    statusOptions: {
        showConfirmed: true,
        showMaybe: true,
        showDeclined: true,
        confirmedLabel: 'Confirmo presença',
        maybeLabel: 'Talvez',
        declinedLabel: 'Não poderei comparecer',
    },
    eventSelection: {
        mode: 'all_active',
        selectedEventIds: [],
        featuredEventId: null,
    },
    preview: {
        scenario: 'default',
    },
};

const localContent = computed(() => mergeWithDefaults(DEFAULTS, props.content || {}));

const style = computed(() => localContent.value.style || DEFAULTS.style);
const fields = computed(() => localContent.value.fields || DEFAULTS.fields);
const labels = computed(() => localContent.value.labels || DEFAULTS.labels);
const messages = computed(() => localContent.value.messages || DEFAULTS.messages);
const statusOptions = computed(() => localContent.value.statusOptions || DEFAULTS.statusOptions);
const eventSelection = computed(() => localContent.value.eventSelection || DEFAULTS.eventSelection);
const access = computed(() => localContent.value.access || DEFAULTS.access);
const titleTypography = computed(() => localContent.value.titleTypography || DEFAULTS.titleTypography);
const subtitleTypography = computed(() => localContent.value.subtitleTypography || DEFAULTS.subtitleTypography);

const resolveSurfaceBackgroundColor = (value) => {
    const fallback = props.theme?.surfaceBackgroundColor || '#f8f6f4';

    if (typeof value !== 'string' || !value.trim()) {
        return fallback;
    }

    const normalized = value.trim().toLowerCase();
    if (normalized === '#f8f6f4' || normalized === '#f5f5f5') {
        return fallback;
    }

    return value;
};

const sectionBackgroundColor = computed(() => resolveSurfaceBackgroundColor(style.value.backgroundColor));

const selectedEventId = ref('');
const token = ref('');
const isSubmitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const form = reactive({
    name: '',
    email: '',
    phone: '',
    status: 'confirmed',
    responses: {},
});

const allActiveEvents = computed(() => {
    const list = props.wedding?.guest_events;

    if (!Array.isArray(list)) {
        return [];
    }

    return list
        .filter((event) => event.is_active)
        .map((event) => ({ ...event, id: String(event.id) }));
});

const selectedEventIds = computed(() => {
    const ids = eventSelection.value.selectedEventIds;
    return Array.isArray(ids) ? ids.map(String) : [];
});

const events = computed(() => {
    if (eventSelection.value.mode !== 'selected') {
        return allActiveEvents.value;
    }

    const allowed = new Set(selectedEventIds.value);

    return allActiveEvents.value.filter((event) => allowed.has(String(event.id)));
});

const selectedEvent = computed(() => {
    return events.value.find((event) => String(event.id) === String(selectedEventId.value));
});

const showDynamicQuestions = computed(() => fields.value.showDynamicQuestions !== false);
const questions = computed(() => {
    if (!showDynamicQuestions.value) {
        return [];
    }

    return selectedEvent.value?.questions || [];
});

const effectivePreviewScenario = computed(() => {
    if (!props.isPreview) {
        return null;
    }

    return props.previewScenario || localContent.value.preview?.scenario || 'default';
});

const simulatedInviteTokenState = computed(() => {
    if (!props.isPreview) {
        return null;
    }

    switch (effectivePreviewScenario.value) {
        case 'valid_token':
            return 'valid';
        case 'invalid_token':
            return 'invalid';
        case 'token_limit_reached':
            return 'limit_reached';
        case 'restricted_denied':
            return 'restricted_denied';
        default:
            return null;
    }
});

const effectiveInviteTokenState = computed(() => {
    return simulatedInviteTokenState.value || props.inviteTokenState;
});

const effectiveAccessMode = computed(() => {
    const configuredMode = access.value.mode || 'inherit';

    if (configuredMode === 'inherit') {
        return props.wedding?.settings?.rsvp_access === 'restricted'
            ? 'restricted'
            : 'open';
    }

    return configuredMode;
});

const requiresInviteToken = computed(() => {
    return access.value.requireInviteToken === true || effectiveAccessMode.value === 'token_only';
});

const isTokenMissingRequired = computed(() => {
    if (!requiresInviteToken.value) {
        return false;
    }

    return String(token.value || '').trim() === '';
});

const isTokenLimitReached = computed(() => effectiveInviteTokenState.value === 'limit_reached');
const isTokenInvalid = computed(() => effectiveInviteTokenState.value === 'invalid');
const isTokenExpired = computed(() => effectiveInviteTokenState.value === 'expired');
const isTokenRevoked = computed(() => effectiveInviteTokenState.value === 'revoked');
const isRestrictedDenied = computed(() => effectiveInviteTokenState.value === 'restricted_denied');
const hasBlockingAccessState = computed(() => {
    return isTokenLimitReached.value
        || isTokenInvalid.value
        || isTokenExpired.value
        || isTokenRevoked.value
        || isRestrictedDenied.value
        || isTokenMissingRequired.value;
});

const showName = computed(() => fields.value.collectName !== false);
const showEmail = computed(() => fields.value.collectEmail !== false);
const showPhone = computed(() => fields.value.collectPhone !== false);
const requireEmail = computed(() => showEmail.value && fields.value.requireEmail === true);
const requirePhone = computed(() => showPhone.value && fields.value.requirePhone === true);

const statusChoices = computed(() => {
    const result = [];

    if (statusOptions.value.showConfirmed !== false) {
        result.push({ value: 'confirmed', label: statusOptions.value.confirmedLabel || 'Confirmo presença' });
    }

    if (statusOptions.value.showMaybe !== false) {
        result.push({ value: 'maybe', label: statusOptions.value.maybeLabel || 'Talvez' });
    }

    if (statusOptions.value.showDeclined !== false) {
        result.push({ value: 'declined', label: statusOptions.value.declinedLabel || 'Não poderei comparecer' });
    }

    if (result.length === 0) {
        result.push({ value: 'confirmed', label: 'Confirmo presença' });
    }

    return result;
});

const sectionClass = computed(() => {
    if (style.value.layout === 'compact') {
        return 'py-12 px-4';
    }

    return 'py-20 px-4';
});

const sectionContainerClass = computed(() => {
    return style.value.containerMaxWidth || 'max-w-xl';
});

const formContainerClass = computed(() => {
    if (style.value.showCard === false) {
        return style.value.layout === 'compact' ? 'p-0' : 'p-2';
    }

    switch (style.value.layout) {
        case 'clean':
            return 'bg-white border border-gray-200 rounded-xl p-6 md:p-8';
        case 'compact':
            return 'bg-white rounded-xl shadow-md p-5 md:p-6';
        default:
            return 'bg-white rounded-2xl shadow-lg p-8 md:p-10';
    }
});

const titleTextStyle = computed(() => ({
    fontFamily: titleTypography.value.fontFamily || props.theme?.fontFamily || 'Playfair Display',
    color: titleTypography.value.fontColor || props.theme?.primaryColor || '#d87a8d',
    fontSize: titleTypography.value.fontSize ? `${titleTypography.value.fontSize}px` : undefined,
    fontWeight: titleTypography.value.fontWeight || 700,
    fontStyle: titleTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: titleTypography.value.fontUnderline ? 'underline' : 'none',
}));

const subtitleTextStyle = computed(() => ({
    fontFamily: subtitleTypography.value.fontFamily || props.theme?.fontFamily || 'Playfair Display',
    color: subtitleTypography.value.fontColor || '#4b5563',
    fontSize: subtitleTypography.value.fontSize ? `${subtitleTypography.value.fontSize}px` : undefined,
    fontWeight: subtitleTypography.value.fontWeight || 400,
    fontStyle: subtitleTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: subtitleTypography.value.fontUnderline ? 'underline' : 'none',
}));

const submitDisabled = computed(() => {
    return isSubmitting.value || hasBlockingAccessState.value;
});

const submitLabel = computed(() => {
    if (
        isTokenLimitReached.value
        || isTokenInvalid.value
        || isTokenExpired.value
        || isTokenRevoked.value
        || isRestrictedDenied.value
    ) {
        return messages.value.submitDisabledTokenLabel || 'Token sem novos usos';
    }

    if (isSubmitting.value) {
        return messages.value.submitLoadingLabel || 'Enviando...';
    }

    return messages.value.submitLabel || 'Confirmar Presença';
});

const getQuestionKey = (question, index) => {
    return resolveQuestionKey(question, index);
};

const resolveQuestionKey = (question, index) => {
    const explicitKey = String(question?.key || '').trim();

    if (explicitKey !== '') {
        return explicitKey;
    }

    const label = String(question?.label || question?.title || '').trim();
    const slug = slugifyQuestionLabel(label);

    if (slug !== '') {
        return slug;
    }

    // Keep parity with backend fallback in GuestEventQuestionValidationService.
    return `q_${index + 1}`;
};

const slugifyQuestionLabel = (value) => {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
};

const setStateMessage = () => {
    if (isSubmitting.value) {
        return;
    }

    successMessage.value = '';
    errorMessage.value = '';

    if (props.isPreview && effectivePreviewScenario.value === 'success') {
        successMessage.value = messages.value.success;
        return;
    }

    if (isTokenLimitReached.value) {
        errorMessage.value = messages.value.tokenLimitReached;
        return;
    }

    if (isTokenInvalid.value) {
        errorMessage.value = messages.value.tokenInvalid;
        return;
    }

    if (isTokenExpired.value) {
        errorMessage.value = messages.value.tokenExpired;
        return;
    }

    if (isTokenRevoked.value) {
        errorMessage.value = messages.value.tokenRevoked;
        return;
    }

    if (isRestrictedDenied.value) {
        errorMessage.value = messages.value.restrictedAccess;
        return;
    }

    if (isTokenMissingRequired.value) {
        errorMessage.value = messages.value.tokenRequired;
    }
};

const syncPreviewToken = () => {
    if (!props.isPreview) {
        return;
    }

    if (['valid_token', 'invalid_token', 'token_limit_reached'].includes(effectivePreviewScenario.value)) {
        token.value = 'preview-token';
        return;
    }

    token.value = '';
};

const submit = async () => {
    successMessage.value = '';
    errorMessage.value = '';

    if (hasBlockingAccessState.value) {
        setStateMessage();
        return;
    }

    if (showName.value && !form.name.trim()) {
        errorMessage.value = messages.value.nameRequired;
        return;
    }

    if (requireEmail.value && !form.email.trim()) {
        errorMessage.value = 'Informe seu e-mail.';
        return;
    }

    if (requirePhone.value && !form.phone.trim()) {
        errorMessage.value = 'Informe seu telefone.';
        return;
    }

    if (!selectedEventId.value) {
        errorMessage.value = messages.value.eventRequired;
        return;
    }

    if (props.isPreview) {
        isSubmitting.value = true;

        setTimeout(() => {
            isSubmitting.value = false;
            successMessage.value = messages.value.success;
        }, 250);

        return;
    }

    isSubmitting.value = true;

    try {
        const fallbackName = form.email?.trim() || form.phone?.trim() || 'Convidado';

        const payload = {
            token: token.value || null,
            site_slug: props.siteSlug || null,
            event_id: selectedEventId.value,
            status: form.status,
            responses: form.responses,
            guest: {
                name: showName.value ? form.name.trim() : fallbackName,
                email: showEmail.value ? (form.email || null) : null,
                phone: showPhone.value ? (form.phone || null) : null,
            },
        };

        await axios.post('/api/public/rsvp', payload);
        successMessage.value = messages.value.success;
    } catch (err) {
        errorMessage.value = err?.response?.data?.message || messages.value.genericError;
    } finally {
        isSubmitting.value = false;
    }
};

watch(
    events,
    (next) => {
        if (next.length === 0) {
            selectedEventId.value = '';
            return;
        }

        const hasCurrent = next.some((event) => String(event.id) === String(selectedEventId.value));
        if (hasCurrent) {
            return;
        }

        const featured = eventSelection.value.featuredEventId
            ? String(eventSelection.value.featuredEventId)
            : null;

        if (featured && next.some((event) => String(event.id) === featured)) {
            selectedEventId.value = featured;
            return;
        }

        selectedEventId.value = String(next[0].id);
    },
    { immediate: true }
);

watch(
    statusChoices,
    (choices) => {
        if (!choices.some((choice) => choice.value === form.status)) {
            form.status = choices[0]?.value || 'confirmed';
        }
    },
    { immediate: true }
);

watch(
    [effectiveInviteTokenState, effectivePreviewScenario, messages, requiresInviteToken, token],
    () => {
        setStateMessage();
    },
    { immediate: true, deep: true }
);

watch(
    effectivePreviewScenario,
    () => {
        syncPreviewToken();
    },
    { immediate: true }
);

if (typeof window !== 'undefined' && !props.isPreview) {
    const params = new URLSearchParams(window.location.search);
    token.value = params.get('token') || '';
}

function mergeWithDefaults(defaults, value) {
    if (Array.isArray(defaults)) {
        return Array.isArray(value) ? value : [...defaults];
    }

    if (defaults && typeof defaults === 'object') {
        const incoming = value && typeof value === 'object' ? value : {};
        const merged = { ...incoming };

        Object.keys(defaults).forEach((key) => {
            merged[key] = mergeWithDefaults(defaults[key], incoming[key]);
        });

        return merged;
    }

    return value === undefined || value === null ? defaults : value;
}
</script>

<template>
    <section
        :class="sectionClass"
        :style="{ backgroundColor: sectionBackgroundColor }"
        id="rsvp"
    >
        <div :class="['mx-auto', sectionContainerClass]">
            <div class="text-center mb-10">
                <h2
                    class="text-3xl md:text-4xl font-bold mb-4 break-words [overflow-wrap:anywhere]"
                    :style="titleTextStyle"
                >
                    {{ content.title || 'Confirme sua Presença' }}
                </h2>
                <p class="text-lg break-words [overflow-wrap:anywhere]" :style="subtitleTextStyle">
                    {{ content.description || 'Por favor, confirme sua presença para que possamos preparar tudo com carinho.' }}
                </p>
            </div>

            <div :class="formContainerClass">
                <form class="space-y-6" @submit.prevent="submit">
                    <div v-if="showName">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ labels.name || 'Nome completo' }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            placeholder="Seu nome"
                        />
                    </div>

                    <div
                        v-if="showEmail || showPhone"
                        class="grid grid-cols-1 gap-4"
                        :class="{ 'md:grid-cols-2': showEmail && showPhone }"
                    >
                        <div v-if="showEmail">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ labels.email || 'Email' }}</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                                placeholder="seu@email.com"
                            />
                        </div>
                        <div v-if="showPhone">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ labels.phone || 'Telefone' }}</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                                placeholder="(00) 00000-0000"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ labels.event || 'Evento' }}</label>
                        <select
                            v-model="selectedEventId"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            :disabled="events.length === 0"
                        >
                            <option value="" disabled>Selecione um evento</option>
                            <option v-for="event in events" :key="event.id" :value="String(event.id)">
                                {{ event.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ labels.status || 'Confirmação' }}</label>
                        <select
                            v-model="form.status"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                        >
                            <option v-for="choice in statusChoices" :key="choice.value" :value="choice.value">
                                {{ choice.label }}
                            </option>
                        </select>
                    </div>

                    <div v-if="questions.length" class="space-y-4">
                        <div v-for="(question, index) in questions" :key="index">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ question.label || question.title || 'Pergunta' }}
                            </label>

                            <input
                                v-if="question.type === 'text'"
                                v-model="form.responses[getQuestionKey(question, index)]"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            />

                            <textarea
                                v-else-if="question.type === 'textarea'"
                                v-model="form.responses[getQuestionKey(question, index)]"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            ></textarea>

                            <select
                                v-else-if="question.type === 'select'"
                                v-model="form.responses[getQuestionKey(question, index)]"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            >
                                <option value="" disabled>Selecione uma opção</option>
                                <option v-for="opt in (question.options || [])" :key="opt" :value="opt">
                                    {{ opt }}
                                </option>
                            </select>

                            <input
                                v-else-if="question.type === 'number'"
                                v-model.number="form.responses[getQuestionKey(question, index)]"
                                type="number"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            />

                            <input
                                v-else
                                v-model="form.responses[getQuestionKey(question, index)]"
                                type="text"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="submitDisabled"
                        class="w-full px-6 py-4 text-white font-semibold rounded-lg"
                        :style="{ backgroundColor: theme.primaryColor, opacity: submitDisabled ? 0.7 : 1 }"
                    >
                        {{ submitLabel }}
                    </button>
                </form>

                <div v-if="successMessage" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                    {{ successMessage }}
                </div>
                <div v-if="errorMessage" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                    {{ errorMessage }}
                </div>
            </div>
        </div>
    </section>
</template>
