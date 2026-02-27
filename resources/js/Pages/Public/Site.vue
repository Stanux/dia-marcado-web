<script setup>
/**
 * PublicSite Page
 * 
 * Renders the public wedding site with all enabled sections.
 * Receives published_content via Inertia with placeholders already substituted.
 * 
 * @Requirements: 5.7
 */
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import PublicSiteLayout from '@/Layouts/PublicSiteLayout.vue';
import PublicHeader from '@/Components/Public/PublicHeader.vue';
import PublicHero from '@/Components/Public/PublicHero.vue';
import PublicSaveTheDate from '@/Components/Public/PublicSaveTheDate.vue';
import PublicGiftRegistry from '@/Components/Public/PublicGiftRegistry.vue';
import PublicRsvp from '@/Components/Public/PublicRsvp.vue';
import PublicPhotoGallery from '@/Components/Public/PublicPhotoGallery.vue';
import PublicFooter from '@/Components/Public/PublicFooter.vue';
const FIXED_SECTION_KEYS = ['header', 'footer'];
const DEFAULT_MOVABLE_SECTION_ORDER = ['hero', 'saveTheDate', 'giftRegistry', 'rsvp', 'photoGallery'];

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    wedding: {
        type: Object,
        required: true,
    },
    content: {
        type: Object,
        required: true,
    },
    inviteTokenState: {
        type: String,
        default: null,
    },
    isTemplatePreview: {
        type: Boolean,
        default: false,
    },
    templateApplyContext: {
        type: Object,
        default: null,
    },
});

// Extract sections from content
const sections = computed(() => props.content.sections || {});

// Extract theme from content
const theme = computed(() => ({
    primaryColor: '#e11d48',
    secondaryColor: '#be123c',
    baseBackgroundColor: '#ffffff',
    surfaceBackgroundColor: '#f9fafb',
    fontFamily: 'Figtree',
    fontSize: '14px',
    ...(props.content.theme || {}),
}));

// Check if section is enabled
const isSectionEnabled = (sectionKey) => {
    return sections.value[sectionKey]?.enabled ?? false;
};

// Get section content
const getSectionContent = (sectionKey) => {
    return sections.value[sectionKey] || {};
};

// Get enabled sections map for header navigation filtering
const enabledSections = computed(() => {
    const result = {};
    Object.keys(sections.value).forEach(key => {
        result[key] = sections.value[key]?.enabled ?? false;
    });
    return result;
});

const sanitizeMovableSectionOrder = (rawOrder, availableSectionKeys) => {
    const availableMovableKeys = availableSectionKeys.filter((key) => !FIXED_SECTION_KEYS.includes(key));
    const requestedOrder = Array.isArray(rawOrder) ? rawOrder : [];

    const sanitized = [];

    requestedOrder.forEach((key) => {
        if (!availableMovableKeys.includes(key)) {
            return;
        }

        if (!sanitized.includes(key)) {
            sanitized.push(key);
        }
    });

    DEFAULT_MOVABLE_SECTION_ORDER.forEach((key) => {
        if (availableMovableKeys.includes(key) && !sanitized.includes(key)) {
            sanitized.push(key);
        }
    });

    availableMovableKeys.forEach((key) => {
        if (!sanitized.includes(key)) {
            sanitized.push(key);
        }
    });

    return sanitized;
};

const orderedSectionKeys = computed(() => {
    const availableSectionKeys = Object.keys(sections.value);
    const movableOrder = sanitizeMovableSectionOrder(props.content?.sectionOrder, availableSectionKeys);

    return [
        ...(availableSectionKeys.includes('header') ? ['header'] : []),
        ...movableOrder,
        ...(availableSectionKeys.includes('footer') ? ['footer'] : []),
    ];
});

const sectionComponentMap = {
    header: PublicHeader,
    hero: PublicHero,
    saveTheDate: PublicSaveTheDate,
    giftRegistry: PublicGiftRegistry,
    rsvp: PublicRsvp,
    photoGallery: PublicPhotoGallery,
    footer: PublicFooter,
};

const renderedSections = computed(() => {
    return orderedSectionKeys.value
        .filter((sectionKey) => isSectionEnabled(sectionKey))
        .map((sectionKey) => {
            const component = sectionComponentMap[sectionKey];
            if (!component) {
                return null;
            }

            const content = getSectionContent(sectionKey);
            const baseProps = {
                content,
                theme: theme.value,
            };

            if (sectionKey === 'header') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        enabledSections: enabledSections.value,
                    },
                };
            }

            if (sectionKey === 'saveTheDate') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        wedding: props.wedding,
                    },
                };
            }

            if (sectionKey === 'giftRegistry') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        eventId: props.wedding.id,
                        config: props.wedding.gift_registry_config,
                        isPreview: props.isTemplatePreview,
                    },
                };
            }

            if (sectionKey === 'rsvp') {
                return {
                    key: sectionKey,
                    component,
                    props: {
                        ...baseProps,
                        wedding: props.wedding,
                        siteSlug: props.site.slug,
                        inviteTokenState: props.inviteTokenState,
                        isPreview: props.isTemplatePreview,
                    },
                };
            }

            return {
                key: sectionKey,
                component,
                props: baseProps,
            };
        })
        .filter(Boolean);
});

const isApplyingTemplate = ref(false);
const applyingMode = ref(null);
const templateApplyError = ref('');

const canApplyTemplateFromPreview = computed(() => {
    return props.isTemplatePreview
        && Boolean(props.templateApplyContext?.enabled)
        && Boolean(props.templateApplyContext?.site_id)
        && Boolean(props.templateApplyContext?.template_id);
});

const templateApplyHint = computed(() => {
    const reason = props.templateApplyContext?.reason;

    if (!reason) {
        return 'Você pode aplicar este template sem sair da navegação.';
    }

    if (reason === 'template_locked_by_plan') {
        return 'Template indisponível para o plano atual.';
    }

    return 'Abra este template pela tela do Editor para aplicar no seu site.';
});

const notifyEditorAndClose = (mode) => {
    const openerWindow = window.opener;
    const payload = {
        type: 'site-template-applied',
        siteId: props.templateApplyContext?.site_id || null,
        templateId: props.templateApplyContext?.template_id || null,
        mode,
        session: props.templateApplyContext?.session || null,
    };

    if (openerWindow && !openerWindow.closed) {
        try {
            openerWindow.postMessage(payload, window.location.origin);
            openerWindow.focus();
        } catch (error) {
            console.error('Não foi possível notificar a janela do editor:', error);
        }
    }

    window.close();

    if (!window.closed && props.templateApplyContext?.return_url) {
        window.location.href = props.templateApplyContext.return_url;
    }
};

const applyTemplateFromPreview = async (mode) => {
    if (isApplyingTemplate.value || !canApplyTemplateFromPreview.value) {
        return;
    }

    isApplyingTemplate.value = true;
    applyingMode.value = mode;
    templateApplyError.value = '';

    try {
        const endpoint = `/api/sites/${props.templateApplyContext.site_id}/apply-template/${props.templateApplyContext.template_id}`;
        const response = await axios.post(endpoint, { mode });

        if (response?.data?.error) {
            throw new Error(response.data.message || 'Não foi possível aplicar o template.');
        }

        notifyEditorAndClose(mode);
    } catch (error) {
        templateApplyError.value = error?.response?.data?.message
            || error?.message
            || 'Não foi possível aplicar este template agora.';
    } finally {
        isApplyingTemplate.value = false;
        applyingMode.value = null;
    }
};

onMounted(() => {
    const weddingId = props.templateApplyContext?.wedding_id;
    if (weddingId) {
        window.__weddingId = weddingId;
    }
});
</script>

<template>
    <PublicSiteLayout :site="site" :content="content" :wedding="wedding">
        <component
            :is="section.component"
            v-for="section in renderedSections"
            :key="section.key"
            v-bind="section.props"
        />

        <div
            v-if="isTemplatePreview"
            class="fixed bottom-4 right-4 z-50 w-[calc(100vw-2rem)] max-w-xs rounded-xl border border-gray-200 bg-white/95 p-3 shadow-xl backdrop-blur sm:max-w-sm"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Template</p>
            <p class="mt-1 text-sm font-medium text-gray-900">
                Aplicar no site em edição
            </p>
            <p class="mt-1 text-xs text-gray-600">
                {{ templateApplyHint }}
            </p>

            <p v-if="templateApplyError" class="mt-2 text-xs font-medium text-red-600">
                {{ templateApplyError }}
            </p>

            <div class="mt-3 space-y-2">
                <button
                    @click="applyTemplateFromPreview('merge')"
                    :disabled="isApplyingTemplate || !canApplyTemplateFromPreview"
                    class="w-full rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
                    title="Merge: aplica seções, estilo e textos sem sobrescrever mídias já existentes."
                >
                    {{ isApplyingTemplate && applyingMode === 'merge' ? 'Aplicando...' : 'Aplicar parcial' }}
                </button>
                <button
                    @click="applyTemplateFromPreview('overwrite')"
                    :disabled="isApplyingTemplate || !canApplyTemplateFromPreview"
                    class="w-full rounded-md border border-rose-300 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50"
                    title="Aplicação total: sobrescreve o conteúdo atual com o conteúdo do template."
                >
                    {{ isApplyingTemplate && applyingMode === 'overwrite' ? 'Aplicando...' : 'Aplicar total' }}
                </button>
            </div>
        </div>
    </PublicSiteLayout>
</template>

<style scoped>
/* Page-specific styles if needed */
</style>
