<script setup>
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    content: {
        type: Object,
        default: () => ({}),
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    wedding: {
        type: Object,
        default: () => null,
    },
    siteSlug: {
        type: String,
        default: '',
    },
    inviteTokenState: {
        type: String,
        default: null,
    },
    isPreview: {
        type: Boolean,
        default: false,
    },
});

const style = computed(() => props.content?.style || {});
const sectionStyles = computed(() => ({
    backgroundColor: style.value.backgroundColor || props.theme?.surfaceBackgroundColor || '#f5f5f5',
}));
const containerClass = 'max-w-6xl';
const showCard = computed(() => style.value.showCard !== false);

const layoutClass = computed(() => {
    switch (style.value.layout) {
        case 'clean':
            return 'rounded-none border-0 shadow-none bg-transparent p-0';
        case 'compact':
            return 'rounded-xl border border-gray-200 bg-white/90 p-5 sm:p-6';
        default:
            return 'rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8';
    }
});
const cardContainerStyle = computed(() => {
    if (style.value.layout === 'clean') {
        return {};
    }

    return {
        backgroundColor: style.value.cardBackgroundColor || '#ffffff',
        borderColor: style.value.cardBorderColor || '#e5e7eb',
    };
});

const title = computed(() => props.content?.title || 'Convidados');
const description = computed(() => props.content?.description || 'Utilize o código do seu convite para continuar.');
const titleTypography = computed(() => props.content?.titleTypography || {});
const descriptionTypography = computed(() => props.content?.descriptionTypography || {});
const titleStyle = computed(() => ({
    fontFamily: titleTypography.value.fontFamily || props.theme?.fontFamily || 'Playfair Display',
    color: titleTypography.value.fontColor || props.theme?.primaryColor || '#d87a8d',
    fontSize: titleTypography.value.fontSize ? `${titleTypography.value.fontSize}px` : undefined,
    fontWeight: titleTypography.value.fontWeight || 700,
    fontStyle: titleTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: titleTypography.value.fontUnderline ? 'underline' : 'none',
}));
const descriptionStyle = computed(() => ({
    fontFamily: descriptionTypography.value.fontFamily || props.theme?.fontFamily || 'Playfair Display',
    color: descriptionTypography.value.fontColor || '#4b5563',
    fontSize: descriptionTypography.value.fontSize ? `${descriptionTypography.value.fontSize}px` : undefined,
    fontWeight: descriptionTypography.value.fontWeight || 400,
    fontStyle: descriptionTypography.value.fontItalic ? 'italic' : 'normal',
    textDecoration: descriptionTypography.value.fontUnderline ? 'underline' : 'none',
}));
const descriptionTextColor = computed(() => descriptionStyle.value.color || '#4b5563');

const ALERT_VARIANTS = {
    error: {
        normal: {
            background: '#fef2f2',
            border: '#fecaca',
            text: '#991b1b',
            icon: '#dc2626',
        },
        highContrast: {
            background: '#7f1d1d',
            border: '#fca5a5',
            text: '#ffffff',
            icon: '#fecaca',
        },
    },
    warning: {
        normal: {
            background: '#fffbeb',
            border: '#fde68a',
            text: '#92400e',
            icon: '#d97706',
        },
        highContrast: {
            background: '#78350f',
            border: '#f59e0b',
            text: '#ffffff',
            icon: '#fbbf24',
        },
    },
    success: {
        normal: {
            background: '#ecfdf5',
            border: '#a7f3d0',
            text: '#065f46',
            icon: '#059669',
        },
        highContrast: {
            background: '#064e3b',
            border: '#6ee7b7',
            text: '#ffffff',
            icon: '#6ee7b7',
        },
    },
    info: {
        normal: {
            background: '#eff6ff',
            border: '#bfdbfe',
            text: '#1e3a8a',
            icon: '#2563eb',
        },
        highContrast: {
            background: '#1e3a8a',
            border: '#93c5fd',
            text: '#ffffff',
            icon: '#bfdbfe',
        },
    },
};

const DEFAULT_SECTION_BACKGROUND_RGB = { r: 245, g: 245, b: 245 };
const MIN_TEXT_CONTRAST = 4.5;
const MIN_BORDER_CONTRAST = 2;
const MIN_BACKGROUND_CONTRAST = 1.2;

function clampRgbChannel(value) {
    const parsed = Number.parseInt(String(value), 10);
    if (Number.isNaN(parsed)) {
        return 0;
    }

    return Math.max(0, Math.min(255, parsed));
}

function parseHexColor(value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (!/^#([0-9a-f]{3}|[0-9a-f]{6})$/.test(normalized)) {
        return null;
    }

    const hex = normalized.length === 4
        ? `#${normalized[1]}${normalized[1]}${normalized[2]}${normalized[2]}${normalized[3]}${normalized[3]}`
        : normalized;

    return {
        r: Number.parseInt(hex.slice(1, 3), 16),
        g: Number.parseInt(hex.slice(3, 5), 16),
        b: Number.parseInt(hex.slice(5, 7), 16),
    };
}

function parseRgbOrRgbaColor(value) {
    const normalized = String(value || '').trim().toLowerCase();
    const rgbaMatch = normalized.match(
        /^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([+-]?\d*\.?\d+)\s*\)$/
    );

    if (rgbaMatch) {
        const alpha = Math.max(0, Math.min(1, Number.parseFloat(rgbaMatch[4])));
        if (!Number.isFinite(alpha)) {
            return null;
        }

        const foreground = {
            r: clampRgbChannel(rgbaMatch[1]),
            g: clampRgbChannel(rgbaMatch[2]),
            b: clampRgbChannel(rgbaMatch[3]),
        };

        // Blend semi-transparent color against white to estimate rendered color.
        return {
            r: Math.round((foreground.r * alpha) + (255 * (1 - alpha))),
            g: Math.round((foreground.g * alpha) + (255 * (1 - alpha))),
            b: Math.round((foreground.b * alpha) + (255 * (1 - alpha))),
        };
    }

    const rgbMatch = normalized.match(
        /^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/
    );

    if (!rgbMatch) {
        return null;
    }

    return {
        r: clampRgbChannel(rgbMatch[1]),
        g: clampRgbChannel(rgbMatch[2]),
        b: clampRgbChannel(rgbMatch[3]),
    };
}

function parseCssColor(value, fallback = DEFAULT_SECTION_BACKGROUND_RGB) {
    const parsedHex = parseHexColor(value);
    if (parsedHex) {
        return parsedHex;
    }

    const parsedRgb = parseRgbOrRgbaColor(value);
    if (parsedRgb) {
        return parsedRgb;
    }

    return fallback;
}

function toLinearChannel(value) {
    const normalized = value / 255;
    if (normalized <= 0.03928) {
        return normalized / 12.92;
    }

    return ((normalized + 0.055) / 1.055) ** 2.4;
}

function luminance(rgb) {
    return (0.2126 * toLinearChannel(rgb.r))
        + (0.7152 * toLinearChannel(rgb.g))
        + (0.0722 * toLinearChannel(rgb.b));
}

function contrastRatio(firstRgb, secondRgb) {
    const l1 = luminance(firstRgb);
    const l2 = luminance(secondRgb);
    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);

    return (lighter + 0.05) / (darker + 0.05);
}

function resolveAlertPalette(type) {
    const variants = ALERT_VARIANTS[type] || ALERT_VARIANTS.info;
    const sectionBackground = parseCssColor(sectionStyles.value.backgroundColor, DEFAULT_SECTION_BACKGROUND_RGB);

    const candidates = [variants.normal, variants.highContrast];
    let best = candidates[0];
    let bestScore = Number.NEGATIVE_INFINITY;

    candidates.forEach((candidate) => {
        const background = parseCssColor(candidate.background);
        const border = parseCssColor(candidate.border);
        const text = parseCssColor(candidate.text);

        const textContrast = contrastRatio(text, background);
        const borderContrast = contrastRatio(border, sectionBackground);
        const backgroundContrast = contrastRatio(background, sectionBackground);

        const passes = textContrast >= MIN_TEXT_CONTRAST
            && (borderContrast >= MIN_BORDER_CONTRAST || backgroundContrast >= MIN_BACKGROUND_CONTRAST);

        const score = (passes ? 1000 : 0)
            + (textContrast * 10)
            + (borderContrast * 2)
            + backgroundContrast;

        if (score > bestScore) {
            best = candidate;
            bestScore = score;
        }
    });

    return best;
}

const inlineAlertPalettes = computed(() => ({
    error: resolveAlertPalette('error'),
    warning: resolveAlertPalette('warning'),
    success: resolveAlertPalette('success'),
    info: resolveAlertPalette('info'),
}));

function getInlineAlertPalette(type) {
    return inlineAlertPalettes.value[type] || inlineAlertPalettes.value.info;
}

function inlineAlertCardStyle(type) {
    const palette = getInlineAlertPalette(type);

    return {
        backgroundColor: palette.background,
        borderColor: palette.border,
        color: palette.text,
    };
}

function inlineAlertIconStyle(type) {
    const palette = getInlineAlertPalette(type);

    return {
        color: palette.icon,
    };
}

const events = computed(() => {
    const list = props.wedding?.wedding_events_v2;

    if (!Array.isArray(list)) {
        return [];
    }

    return list.map((event) => ({
        id: String(event.id),
        name: event.name,
        event_type: event.event_type || 'open',
        event_date: event.event_date,
        event_time: event.event_time,
    }));
});

const selectedEventId = ref(resolveEventFromUrl() || '');
const confirmationCode = ref('');
const resolving = ref(false);
const submitting = ref(false);
const resolveError = ref('');
const submitError = ref('');
const submitSuccess = ref('');
const resolvedData = ref(null);
const closedGuests = ref([]);
const openGuests = ref([]);
const highlightedOpenRows = ref([]);
const localGuestError = ref('');
const confirmModal = reactive({
    isOpen: false,
    title: '',
    message: '',
    action: null,
});

const form = reactive({
    name: '',
    email: '',
    phone: '',
    side: 'both',
    is_child: false,
    is_primary_contact: false,
});

const isResolved = computed(() => resolvedData.value !== null);
const resolvedMode = computed(() => resolvedData.value?.event?.mode || null);
const isClosedMode = computed(() => resolvedMode.value === 'closed');
const isOpenMode = computed(() => resolvedMode.value === 'open_with_quota' || resolvedMode.value === 'open_without_quota');
const hasQuota = computed(() => Boolean(resolvedData.value?.quota?.has_quota));
const inviteAlreadySubmitted = computed(() => Boolean(resolvedData.value?.invite?.already_submitted));
const allowOpenAdd = computed(() => Boolean(resolvedData.value?.rules?.allow_add ?? true));
const openBlockedReason = computed(() => String(resolvedData.value?.rules?.blocked_reason || ''));
const openBlockedMessage = computed(() => {
    if (inviteAlreadySubmitted.value) {
        return 'Este convite já possui uma lista cadastrada. Para ajustes, peça suporte ao casal organizador.';
    }

    if (openBlockedReason.value === 'quota_reached') {
        return 'Este convite atingiu o limite de cota disponível.';
    }

    return 'Este convite não permite novas adições no momento.';
});

const quotaInfo = computed(() => resolvedData.value?.quota || {
    has_quota: false,
    adult_limit: null,
    child_limit: null,
    adult_used: 0,
    child_used: 0,
    adult_remaining: null,
    child_remaining: null,
});

const draftAdults = computed(() => openGuests.value.filter((guest) => !guest.is_child).length);
const draftChildren = computed(() => openGuests.value.filter((guest) => guest.is_child).length);
const adultRemainingAfterDraft = computed(() => {
    if (quotaInfo.value.adult_remaining === null) {
        return null;
    }

    return quotaInfo.value.adult_remaining - draftAdults.value;
});
const childRemainingAfterDraft = computed(() => {
    if (quotaInfo.value.child_remaining === null) {
        return null;
    }

    return quotaInfo.value.child_remaining - draftChildren.value;
});

const changedClosedResponses = computed(() => closedGuests.value
    .filter((guest) => guest.status !== guest.original_status)
    .filter((guest) => guest.status === 'confirmed' || guest.status === 'declined')
    .map((guest) => ({
        guest_id: guest.id,
        status: guest.status,
    })));

function resolveEventFromUrl() {
    if (typeof window === 'undefined') {
        return '';
    }

    const value = new URLSearchParams(window.location.search).get('event');
    return value ? String(value) : '';
}

function normalizeCode(value) {
    return String(value || '')
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '')
        .slice(0, 6);
}

function modeLabel(mode) {
    if (mode === 'closed') return 'Evento Fechado';
    if (mode === 'open_with_quota') return 'Evento Aberto com Cota';
    if (mode === 'open_without_quota') return 'Evento Aberto sem Cota';
    return 'Evento';
}

function statusLabel(status) {
    if (status === 'confirmed') return 'Confirmado';
    if (status === 'declined') return 'Recusado';
    return 'Pendente';
}

function resetAfterResolveChange() {
    resolvedData.value = null;
    closedGuests.value = [];
    openGuests.value = [];
    highlightedOpenRows.value = [];
    resolveError.value = '';
    submitError.value = '';
    submitSuccess.value = '';
    localGuestError.value = '';
}

function onCodeInput(event) {
    confirmationCode.value = normalizeCode(event.target.value);
    resetAfterResolveChange();
}

function onEventChange(event) {
    selectedEventId.value = String(event.target.value || '');
    resetAfterResolveChange();
}

function enforceSinglePrimary() {
    const current = [...openGuests.value];
    const primaryIndex = current.findIndex((guest) => guest.is_primary_contact);

    if (primaryIndex === -1) {
        return;
    }

    openGuests.value = current.map((guest, index) => ({
        ...guest,
        is_primary_contact: index === primaryIndex,
    }));
}

function addOpenGuest() {
    localGuestError.value = '';
    highlightedOpenRows.value = [];

    const name = String(form.name || '').trim();
    const email = String(form.email || '').trim();

    if (name === '') {
        localGuestError.value = 'Informe o nome do convidado.';
        return;
    }

    if (form.is_primary_contact && email === '') {
        localGuestError.value = 'Para contato principal, o e-mail é obrigatório.';
        return;
    }

    const projectedAdults = draftAdults.value + (form.is_child ? 0 : 1);
    const projectedChildren = draftChildren.value + (form.is_child ? 1 : 0);

    if (quotaInfo.value.adult_remaining !== null && projectedAdults > quotaInfo.value.adult_remaining) {
        localGuestError.value = 'A cota de adultos foi atingida para este convite.';
        return;
    }

    if (quotaInfo.value.child_remaining !== null && projectedChildren > quotaInfo.value.child_remaining) {
        localGuestError.value = 'A cota de crianças foi atingida para este convite.';
        return;
    }

    if (form.is_primary_contact) {
        openGuests.value = openGuests.value.map((guest) => ({
            ...guest,
            is_primary_contact: false,
        }));
    }

    openGuests.value.push({
        local_id: `${Date.now()}-${Math.random()}`,
        name,
        email: email || null,
        phone: String(form.phone || '').trim() || null,
        side: form.side || 'both',
        status: 'confirmed',
        is_child: Boolean(form.is_child),
        is_primary_contact: Boolean(form.is_primary_contact),
    });

    enforceSinglePrimary();

    form.name = '';
    form.email = '';
    form.phone = '';
    form.side = 'both';
    form.is_child = false;
    form.is_primary_contact = false;
}

function removeOpenGuest(localId) {
    openGuests.value = openGuests.value.filter((guest) => guest.local_id !== localId);
    highlightedOpenRows.value = [];
}

function openConfirmModal(action) {
    if (action === 'open') {
        confirmModal.title = 'Salvar lista de convidados';
        confirmModal.message = 'Deseja salvar esta lista de convidados?';
    } else {
        confirmModal.title = 'Salvar confirmações';
        confirmModal.message = 'Deseja salvar as alterações de confirmação?';
    }

    confirmModal.action = action;
    confirmModal.isOpen = true;
}

function closeConfirmModal() {
    if (submitting.value) {
        return;
    }

    confirmModal.isOpen = false;
    confirmModal.action = null;
}

function setClosedStatus(guestId, status) {
    closedGuests.value = closedGuests.value.map((guest) => {
        if (guest.id !== guestId) {
            return guest;
        }

        return {
            ...guest,
            status,
        };
    });
}

async function resolveCode() {
    resolveError.value = '';
    submitError.value = '';
    submitSuccess.value = '';
    localGuestError.value = '';
    highlightedOpenRows.value = [];

    const code = normalizeCode(confirmationCode.value);
    confirmationCode.value = code;

    if (!selectedEventId.value) {
        resolveError.value = 'Selecione um evento antes de validar o código.';
        return;
    }

    if (code.length !== 6) {
        resolveError.value = 'O código deve ter 6 caracteres.';
        return;
    }

    resolving.value = true;

    try {
        const response = await axios.post(`/api/public/weddings/sites/${encodeURIComponent(props.siteSlug)}/guests-v2/resolve`, {
            event_id: selectedEventId.value,
            confirmation_code: code,
        });

        resolvedData.value = response?.data?.data || null;
        submitSuccess.value = '';
        submitError.value = '';

        if (resolvedData.value?.event?.mode === 'closed') {
            closedGuests.value = (resolvedData.value.guests || []).map((guest) => ({
                ...guest,
                original_status: guest.status || 'pending',
                status: guest.status || 'pending',
            }));
            openGuests.value = [];
        } else {
            closedGuests.value = [];
            openGuests.value = [];
        }
    } catch (error) {
        resolvedData.value = null;
        closedGuests.value = [];
        openGuests.value = [];
        resolveError.value = error?.response?.data?.message || 'Não foi possível validar o código.';
    } finally {
        resolving.value = false;
    }
}

async function performOpenGuestsSubmit() {
    submitting.value = true;

    try {
        const response = await axios.post(`/api/public/weddings/sites/${encodeURIComponent(props.siteSlug)}/guests-v2/submit`, {
            event_id: selectedEventId.value,
            confirmation_code: confirmationCode.value,
            guests: openGuests.value.map((guest) => ({
                name: guest.name,
                email: guest.email,
                phone: guest.phone,
                side: guest.side,
                is_child: guest.is_child,
                is_primary_contact: guest.is_primary_contact,
            })),
        });

        submitSuccess.value = response?.data?.data?.message || 'Lista salva com sucesso.';
        openGuests.value = [];
        highlightedOpenRows.value = [];
        await resolveCode();
    } catch (error) {
        const rowsToHighlight = error?.response?.data?.details?.highlight_rows;
        if (Array.isArray(rowsToHighlight)) {
            highlightedOpenRows.value = rowsToHighlight
                .map((value) => Number(value))
                .filter((value) => Number.isInteger(value) && value >= 0);
        } else {
            highlightedOpenRows.value = [];
        }

        submitError.value = error?.response?.data?.message || 'Não foi possível salvar a lista.';
    } finally {
        submitting.value = false;
    }
}

async function performClosedGuestsSubmit() {
    submitting.value = true;

    try {
        const response = await axios.post(`/api/public/weddings/sites/${encodeURIComponent(props.siteSlug)}/guests-v2/submit`, {
            event_id: selectedEventId.value,
            confirmation_code: confirmationCode.value,
            responses: changedClosedResponses.value,
        });

        submitSuccess.value = response?.data?.data?.message || 'Confirmações salvas com sucesso.';
        closedGuests.value = closedGuests.value.map((guest) => ({
            ...guest,
            original_status: guest.status,
        }));
    } catch (error) {
        submitError.value = error?.response?.data?.message || 'Não foi possível salvar as confirmações.';
    } finally {
        submitting.value = false;
    }
}

async function submitOpenGuests() {
    submitError.value = '';
    submitSuccess.value = '';
    localGuestError.value = '';
    highlightedOpenRows.value = [];

    if (openGuests.value.length === 0) {
        submitError.value = 'Adicione pelo menos um convidado antes de salvar.';
        return;
    }

    openConfirmModal('open');
}

function submitClosedGuests() {
    submitError.value = '';
    submitSuccess.value = '';
    openConfirmModal('closed');
}

async function confirmModalSubmit() {
    if (!confirmModal.action) {
        return;
    }

    const action = confirmModal.action;
    confirmModal.isOpen = false;
    confirmModal.action = null;

    if (action === 'open') {
        await performOpenGuestsSubmit();
        return;
    }

    await performClosedGuestsSubmit();
}
</script>

<template>
    <section id="guests-v2" class="py-16 sm:py-20" :style="sectionStyles">
        <div class="mx-auto px-4 sm:px-6 lg:px-8" :class="containerClass">
            <div class="text-center">
                <h2 class="text-3xl font-semibold sm:text-4xl" :style="titleStyle">{{ title }}</h2>
                <p class="mt-3 text-base sm:text-lg" :style="descriptionStyle">{{ description }}</p>
            </div>

            <div
                :class="[
                    'mt-8',
                    showCard ? layoutClass : 'p-0 bg-transparent border-0 shadow-none',
                ]"
                :style="showCard ? cardContainerStyle : undefined"
            >
                <div v-if="isPreview" class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-600">
                    No preview do editor, o fluxo completo de validação por código fica desabilitado.
                </div>

                <div v-else class="space-y-6">
                    <div>
                        <p class="text-sm font-semibold text-center" :style="{ color: descriptionTextColor }">
                            Para confirmar presença, escolha o evento e informe o código de 6 dígitos.
                        </p>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-center">
                            <select
                                :value="selectedEventId"
                                class="w-full max-w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-wedding-500 focus:outline-none sm:w-auto sm:min-w-[280px]"
                                @change="onEventChange"
                            >
                                <option value="">Selecione um evento</option>
                                <option
                                    v-for="event in events"
                                    :key="event.id"
                                    :value="event.id"
                                >
                                    {{ event.name }}
                                </option>
                            </select>

                            <input
                                :value="confirmationCode"
                                type="text"
                                maxlength="6"
                                placeholder="X X X X X X"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase tracking-[0.2em] focus:border-wedding-500 focus:outline-none sm:w-[220px]"
                                @input="onCodeInput"
                            />

                            <button
                                type="button"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-wedding-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-wedding-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                :disabled="resolving"
                                @click="resolveCode"
                            >
                                {{ resolving ? 'Validando...' : 'Validar Código' }}
                            </button>
                        </div>

                        <div
                            v-if="resolveError"
                            class="mt-3 rounded-lg border px-3 py-2 text-sm"
                            :style="inlineAlertCardStyle('error')"
                        >
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" :style="inlineAlertIconStyle('error')">
                                    <path fill-rule="evenodd" d="M18 10A8 8 0 114 4.934V4a1 1 0 00-2 0v4a1 1 0 001 1h4a1 1 0 000-2H5.798A6 6 0 1010 4a1 1 0 100-2 8 8 0 018 8zm-8-3a1 1 0 00-.993.883L9 8v3a1 1 0 001.993.117L11 11V8a1 1 0 00-1-1zm0 7a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 14z" clip-rule="evenodd" />
                                </svg>
                                <p class="leading-5">{{ resolveError }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="isResolved && isOpenMode" class="space-y-4">
                        <div
                            v-if="inviteAlreadySubmitted || !allowOpenAdd"
                            class="rounded-xl border p-4 text-sm"
                            :style="inlineAlertCardStyle('warning')"
                        >
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" :style="inlineAlertIconStyle('warning')">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.742-2.98l5.58-9.921zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <p class="leading-5">{{ openBlockedMessage }}</p>
                            </div>
                        </div>

                        <template v-else>
                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <h4 class="text-sm font-semibold text-gray-900">Adicionar Convidado</h4>

                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(140px,1.2fr)_minmax(170px,1.4fr)_minmax(120px,1fr)_minmax(100px,0.8fr)_auto_auto] lg:items-end">
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Nome *"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wedding-500 focus:outline-none"
                                    />
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="E-mail"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wedding-500 focus:outline-none"
                                    />
                                    <input
                                        v-model="form.phone"
                                        type="text"
                                        placeholder="Telefone"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wedding-500 focus:outline-none"
                                    />
                                    <select
                                        v-model="form.side"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wedding-500 focus:outline-none"
                                    >
                                        <option value="groom">Noivo</option>
                                        <option value="bride">Noiva</option>
                                        <option value="both">Ambos</option>
                                    </select>
                                    <label class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700">
                                        <input v-model="form.is_child" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                                        Criança
                                    </label>
                                    <label class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700">
                                        <input v-model="form.is_primary_contact" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                                        Contato principal
                                    </label>
                                </div>
                                <div class="mt-3 flex justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-wedding-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-wedding-700 sm:w-auto"
                                        @click="addOpenGuest"
                                    >
                                        Adicionar à lista
                                    </button>
                                </div>
                                <p
                                    v-if="hasQuota"
                                    class="mt-2 text-xs text-gray-500 sm:text-right"
                                >
                                    Restante: Adultos {{ adultRemainingAfterDraft ?? '∞' }},
                                    Crianças {{ childRemainingAfterDraft ?? '∞' }}.
                                </p>
                                <p class="mt-2 text-xs text-gray-500">
                                    Clique em <strong>Adicionar à lista</strong> para incluir cada convidado.
                                </p>
                                <div
                                    v-if="localGuestError"
                                    class="mt-3 rounded-lg border px-3 py-2 text-sm"
                                    :style="inlineAlertCardStyle('error')"
                                >
                                    <div class="flex items-start gap-2">
                                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" :style="inlineAlertIconStyle('error')">
                                            <path fill-rule="evenodd" d="M18 10A8 8 0 114 4.934V4a1 1 0 00-2 0v4a1 1 0 001 1h4a1 1 0 000-2H5.798A6 6 0 1010 4a1 1 0 100-2 8 8 0 018 8zm-8-3a1 1 0 00-.993.883L9 8v3a1 1 0 001.993.117L11 11V8a1 1 0 00-1-1zm0 7a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 14z" clip-rule="evenodd" />
                                        </svg>
                                        <p class="leading-5">{{ localGuestError }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <h4 class="text-sm font-semibold text-gray-900">Lista Montada</h4>
                                <div v-if="openGuests.length === 0" class="mt-2 text-sm text-gray-500">
                                    Nenhum convidado adicionado ainda.
                                </div>
                                <div v-else class="mt-3 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 font-medium text-gray-600">Nome</th>
                                                <th class="px-3 py-2 font-medium text-gray-600">E-mail</th>
                                                <th class="px-3 py-2 font-medium text-gray-600">Status</th>
                                                <th class="px-3 py-2 font-medium text-gray-600">Principal</th>
                                                <th class="px-3 py-2 font-medium text-gray-600"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr
                                                v-for="(guest, index) in openGuests"
                                                :key="guest.local_id"
                                                :class="highlightedOpenRows.includes(index) ? 'bg-rose-50' : ''"
                                            >
                                                <td class="px-3 py-2 text-gray-800">{{ guest.name }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ guest.email || '—' }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ statusLabel(guest.status) }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ guest.is_primary_contact ? 'Sim' : 'Não' }}</td>
                                                <td class="px-3 py-2 text-right">
                                                    <button
                                                        type="button"
                                                        class="rounded-md border border-rose-300 px-2 py-1 text-xs text-rose-700 transition hover:bg-rose-50"
                                                        @click="removeOpenGuest(guest.local_id)"
                                                    >
                                                        Excluir
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div
                                    v-if="highlightedOpenRows.length > 0"
                                    class="mt-2 rounded-lg border px-3 py-2 text-xs"
                                    :style="inlineAlertCardStyle('warning')"
                                >
                                    <div class="flex items-start gap-2">
                                        <svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" :style="inlineAlertIconStyle('warning')">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.742-2.98l5.58-9.921zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <p class="leading-5">As linhas destacadas em vermelho precisam de ajuste para continuar.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="submitting"
                                    @click="submitOpenGuests"
                                >
                                    {{ submitting ? 'Salvando...' : 'Salvar Lista' }}
                                </button>
                            </div>
                        </template>
                    </div>

                    <div v-if="isResolved && isClosedMode" class="space-y-4">
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <h4 class="text-sm font-semibold text-gray-900">Convidados do Convite</h4>
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 font-medium text-gray-600">Nome</th>
                                            <th class="px-3 py-2 font-medium text-gray-600">Status Atual</th>
                                            <th class="px-3 py-2 font-medium text-gray-600">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="guest in closedGuests" :key="guest.id">
                                            <td class="px-3 py-2 text-gray-800">
                                                {{ guest.name }}
                                                <span v-if="guest.is_primary_contact" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Principal</span>
                                            </td>
                                            <td class="px-3 py-2 text-gray-600">{{ statusLabel(guest.status) }}</td>
                                            <td class="px-3 py-2">
                                                <div class="flex flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        class="rounded-md border px-2 py-1 text-xs transition"
                                                        :class="guest.status === 'confirmed'
                                                            ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                                            : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                                        @click="setClosedStatus(guest.id, 'confirmed')"
                                                    >
                                                        Confirmar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="rounded-md border px-2 py-1 text-xs transition"
                                                        :class="guest.status === 'declined'
                                                            ? 'border-rose-500 bg-rose-50 text-rose-700'
                                                            : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                                                        @click="setClosedStatus(guest.id, 'declined')"
                                                    >
                                                        Recusar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="submitting"
                                @click="submitClosedGuests"
                            >
                                {{ submitting ? 'Salvando...' : 'Salvar Confirmações' }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="submitError"
                        class="rounded-lg border px-3 py-2 text-sm"
                        :style="inlineAlertCardStyle('error')"
                    >
                        <div class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" :style="inlineAlertIconStyle('error')">
                                <path fill-rule="evenodd" d="M18 10A8 8 0 114 4.934V4a1 1 0 00-2 0v4a1 1 0 001 1h4a1 1 0 000-2H5.798A6 6 0 1010 4a1 1 0 100-2 8 8 0 018 8zm-8-3a1 1 0 00-.993.883L9 8v3a1 1 0 001.993.117L11 11V8a1 1 0 00-1-1zm0 7a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 14z" clip-rule="evenodd" />
                            </svg>
                            <p class="leading-5">{{ submitError }}</p>
                        </div>
                    </div>
                    <div
                        v-if="submitSuccess"
                        class="rounded-lg border px-3 py-2 text-sm"
                        :style="inlineAlertCardStyle('success')"
                    >
                        <div class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" :style="inlineAlertIconStyle('success')">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <p class="leading-5">{{ submitSuccess }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="confirmModal.isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
            @click.self="closeConfirmModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900">{{ confirmModal.title }}</h3>
                <p class="mt-2 text-sm text-gray-600">{{ confirmModal.message }}</p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting"
                        @click="closeConfirmModal"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting"
                        @click="confirmModalSubmit"
                    >
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
