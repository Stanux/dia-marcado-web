<script setup>
/**
 * Site Editor Page
 * 
 * Main editor interface for wedding site customization.
 * Features sidebar navigation, section editing, and auto-save.
 * 
 * @Requirements: 1.2, 3.1
 */
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, defineAsyncComponent, nextTick } from 'vue';
import axios from 'axios';
import SectionSidebar from '@/Components/Site/SectionSidebar.vue';
import SectionEditor from '@/Components/Site/SectionEditor.vue';
import Toast from '@/Components/Site/Toast.vue';
import QAPanel from '@/Components/Site/QAPanel.vue';
import useSiteEditor from '@/Composables/useSiteEditor';
import useVersionHistory from '@/Composables/useVersionHistory';
import { applyThemePresetToContent, getThemePresetById } from '@/Components/Site/themePresets';

const FullscreenPreview = defineAsyncComponent(() => import('@/Components/Site/FullscreenPreview.vue'));
const PublishDialog = defineAsyncComponent(() => import('@/Components/Site/PublishDialog.vue'));

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    weddingId: {
        type: String,
        default: null,
    },
    logoInitials: {
        type: Array,
        default: () => ['', ''],
    },
    templateContext: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const isTemplateEditor = computed(() => Boolean(props.templateContext?.id));
const isAdminUser = computed(() => page.props?.auth?.user?.role === 'admin');
const canUseTemplateCatalog = computed(() => !isTemplateEditor.value && !isAdminUser.value);
const editorBackHref = computed(() => {
    if (isTemplateEditor.value) {
        return `/admin/site-templates/${props.templateContext.id}/edit`;
    }

    return '/admin';
});
const editorTitle = computed(() => {
    if (isTemplateEditor.value) {
        return `Editor de Template - ${props.templateContext.name}`;
    }

    return 'Editor de Site';
});
const currentWedding = computed(() => page.props?.wedding || {});

// Ensure wedding ID is available globally for API requests
onMounted(() => {
    const weddingId = props.weddingId || page.props.weddingId;
    if (weddingId) {
        window.__weddingId = weddingId;
    }
});

// Initialize composables
const {
    site,
    draftContent,
    isDirty,
    isSaving,
    isPublishing,
    lastSaved,
    error: editorError,
    updateSection,
    updateMeta,
    updateTheme,
    touchMutation,
    save,
    createVersionSnapshot,
    applyServerSiteData,
} = useSiteEditor(props.site);

const {
    versions,
    isLoading: isLoadingVersions,
    isRestoring: isRestoringVersion,
    loadVersions,
    restore,
} = useVersionHistory(props.site.id);

// Local state
const activeSection = ref('header');
const showPreview = ref(false);
const showVersionHistory = ref(false);
const showPublishDialog = ref(false);
const showMobileSidebar = ref(false);
const isMobileViewport = ref(false);
const historyFilter = ref('all');
const showRestoreDialog = ref(false);
const versionToRestore = ref(null);
const isApplyingThemePreset = ref(false);
const showThemePresetResultDialog = ref(false);
const isRestoringThemeSnapshot = ref(false);
const quickRestoreVersionId = ref(null);
const showTemplateCatalogModal = ref(false);
const templateCatalogItems = ref([]);
const templateThumbnailErrors = ref({});
const isLoadingTemplateCatalog = ref(false);
const templateCatalogError = ref('');
const isApplyingTemplateCatalog = ref(false);
const templateApplyTarget = ref({
    templateId: null,
    mode: null,
});
const themePresetResult = ref({
    presetName: '',
    presetDescription: '',
    preVersionId: null,
    qaResult: null,
});
const THEME_PRE_SNAPSHOT_PREFIX = 'Snapshot antes do tema:';
const TEMPLATE_FALLBACK_THUMBNAIL = `data:image/svg+xml;utf8,${encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500"><defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#fff1f4"/><stop offset="100%" stop-color="#fde8ee"/></linearGradient></defs><rect width="800" height="500" fill="url(#bg)"/><rect x="88" y="72" width="624" height="356" rx="18" fill="#ffffff" stroke="#ffccd9" stroke-width="4"/><rect x="120" y="112" width="560" height="32" rx="8" fill="#fde8ee"/><rect x="120" y="164" width="360" height="20" rx="8" fill="#fff1f4"/><rect x="120" y="198" width="280" height="20" rx="8" fill="#fff1f4"/><rect x="120" y="246" width="170" height="128" rx="10" fill="#fff1f4" stroke="#ffccd9" stroke-width="2"/><rect x="314" y="246" width="170" height="128" rx="10" fill="#fff1f4" stroke="#ffccd9" stroke-width="2"/><rect x="508" y="246" width="170" height="128" rx="10" fill="#fff1f4" stroke="#ffccd9" stroke-width="2"/><circle cx="400" cy="302" r="36" fill="#ffccd9"/><path d="M382 314l14-15 11 10 13-14 16 19h-54z" fill="#b9163a"/><text x="400" y="422" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="26" fill="#4A2F39">Template sem imagem</text></svg>')}`;
const TEMPLATE_PARTIAL_TOOLTIP = 'Merge: aplica seções, estilo e textos, preservando mídias que você já definiu.';
const TEMPLATE_TOTAL_TOOLTIP = 'Aplicação total: substitui todo o conteúdo atual pelo conteúdo completo do template.';

// Toast state
const toast = ref({
    show: false,
    type: 'success',
    message: '',
});

const SECTION_DEFINITIONS = {
    header: { label: 'Cabeçalho', icon: 'header' },
    hero: { label: 'Destaque', icon: 'image' },
    saveTheDate: { label: 'Save the Date', icon: 'calendar' },
    giftRegistry: { label: 'Lista de Presentes', icon: 'gift' },
    rsvp: { label: 'Confirme Presença', icon: 'users' },
    photoGallery: { label: 'Galeria de Fotos', icon: 'images' },
    footer: { label: 'Rodapé', icon: 'footer' },
};

const FIXED_SECTION_KEYS = ['header', 'footer'];
const DEFAULT_MOVABLE_SECTION_ORDER = ['hero', 'saveTheDate', 'giftRegistry', 'rsvp', 'photoGallery'];
const BLOCKED_EDITOR_SECTION_KEYS = new Set(['meta', 'theme']);

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
    const sectionMap = draftContent.value?.sections || {};
    const availableSectionKeys = Object.keys(sectionMap);

    if (availableSectionKeys.length === 0) {
        return [];
    }

    const movableOrder = sanitizeMovableSectionOrder(draftContent.value?.sectionOrder, availableSectionKeys);

    return [
        ...(availableSectionKeys.includes('header') ? ['header'] : []),
        ...movableOrder,
        ...(availableSectionKeys.includes('footer') ? ['footer'] : []),
    ];
});

// Section list for sidebar
const sections = computed(() => {
    if (!draftContent.value?.sections) return [];

    return orderedSectionKeys.value.map((sectionKey) => {
        const definition = SECTION_DEFINITIONS[sectionKey] || {
            label: sectionKey,
            icon: 'header',
        };

        return {
            key: sectionKey,
            label: definition.label,
            icon: definition.icon,
            enabled: draftContent.value.sections[sectionKey]?.enabled ?? false,
        };
    });
});

// Current section content
const currentSectionContent = computed(() => {
    // Special sections (meta, theme, settings) are stored at root level
    if (activeSection.value === 'meta') {
        return draftContent.value?.meta || {
            title: '{primeiro_nome_noivo} & {primeiro_nome_noiva} em {data_curta}',
            description: '{primeiro_nome_noivo} & {primeiro_nome_noiva} em {data_curta}',
            ogImage: '',
            canonical: '',
        };
    }

    if (activeSection.value === 'theme') {
        return {
            primaryColor: '#e11d48',
            secondaryColor: '#be123c',
            baseBackgroundColor: '#ffffff',
            surfaceBackgroundColor: '#f9fafb',
            fontFamily: 'Figtree',
            fontSize: '14px',
            ...(draftContent.value?.theme || {}),
        };
    }

    if (activeSection.value === 'settings') {
        // Settings include site-level configuration
        return {
            slug: site.value?.slug || '',
            custom_domain: site.value?.custom_domain || '',
            access_token: site.value?.access_token || '',
            has_password: site.value?.has_password || false,
            is_published: site.value?.is_published || false,
            published_at: site.value?.published_at || null,
            ...(draftContent.value?.settings || {}),
        };
    }

    // Regular sections
    if (!draftContent.value?.sections) return null;
    return draftContent.value.sections[activeSection.value] || null;
});

// Enabled sections map for CTA targets
const enabledSections = computed(() => {
    if (!draftContent.value?.sections) return {};
    const result = {};
    Object.keys(draftContent.value.sections).forEach(key => {
        result[key] = draftContent.value.sections[key]?.enabled ?? false;
    });
    return result;
});

// Handle section selection
const selectSection = (sectionKey) => {
    if (BLOCKED_EDITOR_SECTION_KEYS.has(sectionKey)) {
        sectionKey = orderedSectionKeys.value[0] || 'header';
    }

    const sectionExists = sectionKey === 'settings' || Boolean(draftContent.value?.sections?.[sectionKey]);
    activeSection.value = sectionExists ? sectionKey : (orderedSectionKeys.value[0] || 'header');

    if (isMobileViewport.value) {
        showMobileSidebar.value = false;
    }
};

// Handle section toggle (enable/disable)
const toggleSection = (sectionKey) => {
    const section = draftContent.value.sections[sectionKey];
    if (section) {
        updateSection(sectionKey, { ...section, enabled: !section.enabled });
    }
};

const handleSectionReorder = (nextOrder) => {
    if (!draftContent.value?.sections) {
        return;
    }

    const availableSectionKeys = Object.keys(draftContent.value.sections);
    const currentOrder = sanitizeMovableSectionOrder(draftContent.value.sectionOrder, availableSectionKeys);
    const normalizedOrder = sanitizeMovableSectionOrder(nextOrder, availableSectionKeys);

    if (JSON.stringify(currentOrder) === JSON.stringify(normalizedOrder)) {
        return;
    }

    draftContent.value.sectionOrder = normalizedOrder;
    touchMutation();
};

// Handle section content update
const handleSectionUpdate = (payload) => {
    const sectionType = payload?.sectionType || activeSection.value;
    const data = payload?.content ?? payload;

    // Special sections use dedicated update methods
    if (sectionType === 'meta') {
        updateMeta(data);
    } else if (sectionType === 'theme') {
        updateTheme(data);
    } else if (sectionType === 'settings') {
        // Keep settings edits in draft while typing.
        // Real site settings persistence (slug/domain/password) happens on top save/autosave.
        const sanitizedSettings = {};
        Object.keys(data).forEach((key) => {
            if (key === 'is_published' || key === 'published_at' || key === 'has_password' || key === '__saveSettings') {
                return;
            }

            sanitizedSettings[key] = data[key];
        });

        if (!draftContent.value.settings) {
            draftContent.value.settings = {};
        }

        draftContent.value.settings = {
            ...draftContent.value.settings,
            ...sanitizedSettings,
        };
        touchMutation();
    } else {
        // Regular sections
        updateSection(sectionType, data);
    }
};

const runQaChecklist = async () => {
    try {
        const response = await axios.get(`/admin/sites/${site.value.id}/qa`);
        return response.data?.data || null;
    } catch (err) {
        console.error('QA checklist error after theme apply:', err);
        return null;
    }
};

const themeQaCounts = computed(() => {
    const checks = themePresetResult.value?.qaResult?.checks || [];
    return checks.reduce((accumulator, check) => {
        if (check.status === 'pass') {
            accumulator.passed += 1;
        } else if (check.status === 'warning') {
            accumulator.warning += 1;
        } else if (check.status === 'fail') {
            accumulator.failed += 1;
        }

        return accumulator;
    }, {
        passed: 0,
        warning: 0,
        failed: 0,
    });
});

const isThemePreSnapshot = (version) => {
    const summary = typeof version?.summary === 'string' ? version.summary : '';
    return summary.startsWith(THEME_PRE_SNAPSHOT_PREFIX);
};

const getThemeSnapshotLabel = (version) => {
    if (!isThemePreSnapshot(version)) {
        return '';
    }

    return version.summary.replace(THEME_PRE_SNAPSHOT_PREFIX, '').trim();
};

const closeThemePresetResultDialog = () => {
    if (isRestoringThemeSnapshot.value) {
        return;
    }

    showThemePresetResultDialog.value = false;
};

const navigateFromThemeQa = (sectionKey) => {
    if (sectionKey) {
        selectSection(sectionKey);
    }

    closeThemePresetResultDialog();
};

const navigateFromPublishQa = (sectionKey) => {
    if (!sectionKey) {
        return;
    }

    selectSection(sectionKey);
};

const restoreThemePresetPreviousVersion = async () => {
    const versionId = themePresetResult.value?.preVersionId;
    if (!versionId || isRestoringThemeSnapshot.value) {
        return;
    }

    isRestoringThemeSnapshot.value = true;

    const restoredSite = await restore(versionId);
    if (restoredSite) {
        applyServerSiteData(restoredSite);
        await loadVersions();
        showToast('success', 'Configuração anterior restaurada com sucesso.');
        showThemePresetResultDialog.value = false;
    } else {
        showToast('error', 'Não foi possível restaurar a versão anterior do tema.');
    }

    isRestoringThemeSnapshot.value = false;
};

const quickRestoreThemeSnapshot = async (version) => {
    if (!version?.id || isRestoringVersion.value || quickRestoreVersionId.value) {
        return;
    }

    quickRestoreVersionId.value = version.id;
    try {
        const restoredSite = await restore(version.id);
        if (restoredSite) {
            applyServerSiteData(restoredSite);
            await loadVersions();
            showToast('success', `Tema anterior restaurado (${getThemeSnapshotLabel(version) || 'snapshot'}).`);
        } else {
            showToast('error', 'Não foi possível restaurar o snapshot do tema.');
        }
    } finally {
        quickRestoreVersionId.value = null;
    }
};

const flushFocusedFieldBeforeAction = async () => {
    if (typeof window === 'undefined') {
        return;
    }

    const activeElement = document.activeElement;
    if (!activeElement || typeof activeElement.blur !== 'function') {
        return;
    }

    const tagName = activeElement.tagName?.toLowerCase();
    if (!['input', 'textarea', 'select'].includes(tagName)) {
        return;
    }

    activeElement.blur();
    await nextTick();
    await new Promise((resolve) => setTimeout(resolve, 0));
};

const handleApplyThemePreset = async ({ presetId }) => {
    const preset = getThemePresetById(presetId);
    if (!preset) {
        showToast('error', 'Tema selecionado não foi encontrado.');
        return;
    }

    if (isApplyingThemePreset.value) {
        return;
    }

    isApplyingThemePreset.value = true;

    try {
        const saveCompleted = await waitForPendingSave();
        if (!saveCompleted) {
            showToast('error', 'O salvamento automático está demorando. Aguarde alguns segundos e tente novamente.');
            return;
        }

        await loadVersions();
        const existingVersionIds = new Set(versions.value.map((version) => version.id));

        const preSnapshotSummary = `Snapshot antes do tema: ${preset.name}`;
        const preSnapshotCreated = await createVersionSnapshot(preSnapshotSummary);
        if (!preSnapshotCreated) {
            const details = typeof editorError.value === 'string'
                ? editorError.value
                : editorError.value?.message;
            showToast('error', details || 'Não foi possível salvar a versão de segurança antes de aplicar o tema.');
            return;
        }

        await loadVersions();
        const preVersion = versions.value.find((version) => !existingVersionIds.has(version.id)) || versions.value[0] || null;
        const preVersionId = preVersion?.id || null;

        draftContent.value = applyThemePresetToContent(draftContent.value, preset);
        touchMutation();

        const postSnapshotSummary = `Tema aplicado: ${preset.name}`;
        const postSnapshotCreated = await createVersionSnapshot(postSnapshotSummary);
        if (!postSnapshotCreated) {
            const details = typeof editorError.value === 'string'
                ? editorError.value
                : editorError.value?.message;
            showToast('error', details || 'O tema foi aplicado, mas não foi possível salvar a versão final.');
            return;
        }

        const qaResult = await runQaChecklist();
        await loadVersions();

        themePresetResult.value = {
            presetName: preset.name,
            presetDescription: preset.description,
            preVersionId,
            qaResult,
        };

        showThemePresetResultDialog.value = true;

        if (!qaResult) {
            showToast('success', `Tema "${preset.name}" aplicado. Não foi possível carregar o checklist agora.`);
            return;
        }

        if (qaResult.passed) {
            showToast('success', `Tema "${preset.name}" aplicado com checklist aprovado.`);
        } else {
            showToast('success', `Tema "${preset.name}" aplicado. Revise os alertas do checklist.`);
        }
    } finally {
        isApplyingThemePreset.value = false;
    }
};

// Handle publish - open dialog
const waitForPendingSave = async (timeoutMs = 10000) => {
    const startedAt = Date.now();

    while (isSaving.value) {
        if (Date.now() - startedAt > timeoutMs) {
            return false;
        }

        await new Promise((resolve) => setTimeout(resolve, 100));
    }

    return true;
};

const syncDraftBeforeActions = async (actionLabel = 'continuar') => {
    await flushFocusedFieldBeforeAction();

    const saveCompleted = await waitForPendingSave();

    if (!saveCompleted) {
        showToast('error', 'O salvamento automático está demorando. Aguarde alguns segundos e tente novamente.');
        return false;
    }

    if (isDirty.value) {
        const saved = await save();
        if (!saved && isDirty.value) {
            const details = typeof editorError.value === 'string'
                ? editorError.value
                : editorError.value?.message;
            showToast('error', details || `Não foi possível salvar as alterações antes de ${actionLabel}.`);
            return false;
        }
    }

    return true;
};

const loadTemplateCatalog = async ({ force = false } = {}) => {
    if (!canUseTemplateCatalog.value) {
        return;
    }

    if (!force && templateCatalogItems.value.length > 0) {
        return;
    }

    isLoadingTemplateCatalog.value = true;
    templateCatalogError.value = '';

    try {
        const response = await axios.get('/api/sites/templates');
        templateCatalogItems.value = Array.isArray(response.data?.data) ? response.data.data : [];
        templateThumbnailErrors.value = {};
    } catch (error) {
        templateCatalogError.value = error?.response?.data?.message
            || error?.message
            || 'Não foi possível carregar os templates agora.';
    } finally {
        isLoadingTemplateCatalog.value = false;
    }
};

const openTemplateCatalog = async () => {
    if (!canUseTemplateCatalog.value || isApplyingTemplateCatalog.value) {
        return;
    }

    const isReady = await syncDraftBeforeActions('abrir templates');
    if (!isReady) {
        return;
    }

    showTemplateCatalogModal.value = true;
    await loadTemplateCatalog();
};

const closeTemplateCatalog = () => {
    if (isApplyingTemplateCatalog.value) {
        return;
    }

    showTemplateCatalogModal.value = false;
};

const resolveTemplateThumbnail = (template) => {
    const templateId = template?.id;
    if (templateId && templateThumbnailErrors.value[templateId]) {
        return TEMPLATE_FALLBACK_THUMBNAIL;
    }

    const thumbnail = typeof template?.thumbnail === 'string'
        ? template.thumbnail.trim()
        : '';

    return thumbnail !== '' ? thumbnail : TEMPLATE_FALLBACK_THUMBNAIL;
};

const onTemplateImageError = (event, templateId) => {
    if (templateId) {
        templateThumbnailErrors.value = {
            ...templateThumbnailErrors.value,
            [templateId]: true,
        };
    }

    const image = event?.target;
    if (!image) {
        return;
    }

    image.onerror = null;
    image.src = TEMPLATE_FALLBACK_THUMBNAIL;
    image.alt = 'Imagem padrao de template';
};

const isTemplateApplyRunning = (templateId, mode) => {
    return isApplyingTemplateCatalog.value
        && templateApplyTarget.value.templateId === templateId
        && templateApplyTarget.value.mode === mode;
};

const applyTemplateFromCatalog = async (template, mode) => {
    if (!template?.id || isApplyingTemplateCatalog.value) {
        return;
    }

    if (template.is_locked || template.can_apply === false) {
        showToast('error', 'Este template não está disponível para o seu plano atual.');
        return;
    }

    const isReady = await syncDraftBeforeActions('aplicar o template');
    if (!isReady) {
        return;
    }

    isApplyingTemplateCatalog.value = true;
    templateApplyTarget.value = {
        templateId: template.id,
        mode,
    };

    try {
        const response = await axios.post(
            `/api/sites/${site.value.id}/apply-template/${template.id}`,
            { mode }
        );

        showTemplateCatalogModal.value = false;
        showToast('success', response.data?.message || 'Template aplicado com sucesso.');
        window.location.reload();
    } catch (error) {
        const message = error?.response?.data?.message
            || error?.message
            || 'Não foi possível aplicar o template selecionado.';
        showToast('error', message);
    } finally {
        isApplyingTemplateCatalog.value = false;
        templateApplyTarget.value = {
            templateId: null,
            mode: null,
        };
    }
};

const openTemplateNavigation = (template) => {
    if (!template?.preview_url) {
        showToast('error', 'Navegação indisponível para este template.');
        return;
    }

    const previewUrl = new URL(template.preview_url, window.location.origin);
    previewUrl.searchParams.set('site', String(site.value.id));
    previewUrl.searchParams.set('session', `${site.value.id}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`);

    window.open(previewUrl.toString(), '_blank');
};

const handleTemplateAppliedMessage = (event) => {
    if (event.origin !== window.location.origin) {
        return;
    }

    const payload = event.data;
    if (!payload || payload.type !== 'site-template-applied') {
        return;
    }

    if (String(payload.siteId) !== String(site.value.id)) {
        return;
    }

    showTemplateCatalogModal.value = false;
    window.location.reload();
};

const handlePublish = async () => {
    const isReady = await syncDraftBeforeActions('publicar');
    if (!isReady) {
        return;
    }

    showPublishDialog.value = true;
};

const openPreview = async () => {
    const isReady = await syncDraftBeforeActions('abrir o preview');
    if (!isReady) {
        return;
    }

    showPreview.value = true;
};

const handleTopSave = async () => {
    await flushFocusedFieldBeforeAction();

    const saveCompleted = await waitForPendingSave();
    if (!saveCompleted) {
        showToast('error', 'O salvamento automático está demorando. Aguarde alguns segundos e tente novamente.');
        return;
    }

    const saved = await save();

    if (saved) {
        showToast('success', 'Alterações salvas com sucesso.');
        return;
    }

    if (!isDirty.value) {
        return;
    }

    const details = typeof editorError.value === 'string'
        ? editorError.value
        : editorError.value?.message;
    showToast('error', details || 'Não foi possível salvar as alterações.');
};

// Handle publish success
const handlePublishSuccess = (data) => {
    showToast('success', 'Site publicado com sucesso!');
    // Reload page to update site status
    window.location.reload();
};

// Handle publish error
const handlePublishError = (error) => {
    const message = typeof error === 'string' ? error : error?.message || 'Erro ao publicar site';
    showToast('error', message);
};

// Show toast notification
const showToast = (type, message) => {
    toast.value = {
        show: true,
        type,
        message,
    };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
};

// Close toast
const closeToast = () => {
    toast.value.show = false;
};

// Handle version restore
const openRestoreDialog = (version) => {
    versionToRestore.value = version;
    showRestoreDialog.value = true;
};

const closeRestoreDialog = () => {
    if (isRestoringVersion.value) {
        return;
    }

    showRestoreDialog.value = false;
    versionToRestore.value = null;
};

const confirmRestore = async () => {
    if (!versionToRestore.value?.id) {
        closeRestoreDialog();
        return;
    }

    const restored = await restore(versionToRestore.value.id);
    if (restored) {
        applyServerSiteData(restored);
        showToast('success', 'Versão restaurada com sucesso.');
        showVersionHistory.value = false;
        closeRestoreDialog();
    } else {
        showToast('error', 'Não foi possível restaurar a versão selecionada.');
    }
};

// Toggle version history panel
const toggleVersionHistory = async () => {
    showVersionHistory.value = !showVersionHistory.value;
    if (showVersionHistory.value && versions.value.length === 0) {
        await loadVersions();
    }
    if (showVersionHistory.value) {
        showMobileSidebar.value = false;
    }
};

const toggleMobileSidebar = () => {
    showMobileSidebar.value = !showMobileSidebar.value;
    if (showMobileSidebar.value) {
        showVersionHistory.value = false;
    }
};

const closeMobileSidebar = () => {
    showMobileSidebar.value = false;
};

const updateViewportState = () => {
    isMobileViewport.value = window.innerWidth < 1024;
    if (!isMobileViewport.value) {
        showMobileSidebar.value = false;
    }
};

const publishedVersionsCount = computed(() => versions.value.filter((version) => version.is_published).length);

const filteredVersions = computed(() => {
    if (historyFilter.value === 'published') {
        return versions.value.filter((version) => version.is_published);
    }

    return versions.value;
});

const latestThemePreSnapshot = computed(() => {
    return versions.value.find((version) => isThemePreSnapshot(version)) || null;
});

// Format last saved time
const formattedLastSaved = computed(() => {
    if (!lastSaved.value) return null;
    return new Date(lastSaved.value).toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });
});

// Keyboard shortcuts
const handleKeydown = (e) => {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        handleTopSave();
    }
    // Ctrl/Cmd + P to publish
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        handlePublish();
    }
};

onMounted(() => {
    updateViewportState();
    window.addEventListener('resize', updateViewportState);
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('message', handleTemplateAppliedMessage);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateViewportState);
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('message', handleTemplateAppliedMessage);
});
</script>

<template>
    <Head :title="editorTitle" />

    <div class="h-screen flex flex-col bg-gray-100">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 px-3 py-3 sm:px-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <a :href="editorBackHref" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>

                        <button
                            @click="toggleMobileSidebar"
                            class="lg:hidden inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-gray-700 hover:bg-gray-50"
                            aria-label="Abrir menu de seções"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <h1 class="truncate text-base font-semibold text-gray-900 sm:text-lg">{{ editorTitle }}</h1>
                    </div>
                </div>

                <div class="w-full lg:w-auto lg:ml-auto">
                    <div class="flex flex-col gap-2 lg:items-end">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <!-- Templates Button -->
                            <button
                                v-if="canUseTemplateCatalog"
                                @click="openTemplateCatalog"
                                :disabled="isApplyingTemplateCatalog || isSaving"
                                class="inline-flex items-center justify-center gap-1.5 rounded-md border border-wedding-700 bg-wedding-600 px-2.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-wedding-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:text-sm"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h10" />
                                </svg>
                                <span class="hidden sm:inline">Templates</span>
                                <span class="sr-only sm:hidden">Templates</span>
                            </button>

                            <!-- Save Button -->
                            <button
                                @click="handleTopSave"
                                :disabled="!isDirty || isSaving"
                                class="inline-flex items-center justify-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-300 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:text-sm"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21h14a2 2 0 002-2V7.414a2 2 0 00-.586-1.414l-2.414-2.414A2 2 0 0016.586 3H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-8H7v8M7 3v4h8V3" />
                                </svg>
                                <span class="hidden sm:inline">Salvar</span>
                                <span class="sr-only sm:hidden">Salvar</span>
                            </button>

                            <!-- Version History Button -->
                            <button
                                @click="toggleVersionHistory"
                                class="inline-flex items-center justify-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-300 sm:px-3 sm:text-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="hidden sm:inline">Histórico</span>
                                <span
                                    v-if="publishedVersionsCount > 0"
                                    class="ml-1 inline-flex items-center rounded-full bg-wedding-100 px-1.5 py-0.5 text-[10px] font-semibold text-wedding-700"
                                    :title="`${publishedVersionsCount} publicação(ões)`"
                                >
                                    {{ publishedVersionsCount }}
                                </span>
                            </button>

                            <!-- Preview Button -->
                            <button
                                @click="openPreview"
                                class="inline-flex items-center justify-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-300 sm:px-3 sm:text-sm"
                                :class="{ 'border-gray-400 bg-gray-50': showPreview }"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span class="hidden sm:inline">Preview</span>
                            </button>

                            <!-- Publish Button -->
                            <button
                                @click="handlePublish"
                                :disabled="isPublishing"
                                v-if="!isTemplateEditor"
                                class="publish-button inline-flex items-center justify-center gap-1.5 rounded-md border px-2.5 py-2 text-xs font-semibold text-white shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:text-sm"
                            >
                                <svg v-if="isPublishing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V6m0 0l-4 4m4-4l4 4M4 18h16" />
                                </svg>
                                <span class="hidden sm:inline">{{ isPublishing ? 'Publicando...' : 'Publicar' }}</span>
                                <span class="sr-only sm:hidden">{{ isPublishing ? 'Publicando...' : 'Publicar' }}</span>
                            </button>

                            <!-- View Site Button (only if published) -->
                            <a
                                v-if="site.is_published && !isTemplateEditor"
                                :href="`/site/${site.slug}`"
                                target="_blank"
                                class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 flex items-center sm:px-4 sm:text-sm"
                            >
                                <svg class="w-4 h-4 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span class="hidden sm:inline">Ver Site</span>
                            </a>
                        </div>

                        <!-- Status indicators -->
                        <div class="flex flex-wrap items-center justify-end gap-2 text-xs sm:text-sm">
                            <span v-if="isDirty" class="text-amber-600 flex items-center">
                                <span class="w-2 h-2 bg-amber-500 rounded-full mr-1"></span>
                                Alterações não salvas
                            </span>
                            <span v-else-if="lastSaved" class="text-gray-500">
                                Salvo às {{ formattedLastSaved }}
                            </span>
                            <span v-if="isSaving" class="text-blue-600 flex items-center">
                                <svg class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Salvando...
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden relative">
            <!-- Sidebar Desktop -->
            <div class="hidden lg:block">
                <SectionSidebar
                    :sections="sections"
                    :active-section="activeSection"
                    @select="selectSection"
                    @toggle="toggleSection"
                    @reorder="handleSectionReorder"
                />
            </div>

            <!-- Sidebar Mobile Drawer -->
            <div v-if="showMobileSidebar" class="fixed inset-0 z-40 lg:hidden">
                <div class="absolute inset-0 bg-black/40" @click="closeMobileSidebar"></div>
                <div class="absolute inset-y-0 left-0 w-[88vw] max-w-sm shadow-xl">
                    <SectionSidebar
                        mobile
                        :sections="sections"
                        :active-section="activeSection"
                        @select="selectSection"
                        @toggle="toggleSection"
                        @reorder="handleSectionReorder"
                    />
                </div>
            </div>

            <!-- Editor Area -->
            <main class="flex-1 overflow-hidden p-3 sm:p-4 lg:p-6 flex flex-col">
                <div class="w-full flex-1 flex flex-col min-h-0">
                    <!-- Draft Watermark -->
                    <div v-if="site.is_draft" class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center text-amber-800 flex-shrink-0">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-sm font-medium">RASCUNHO - Alterações não publicadas</span>
                    </div>

                    <!-- Section Editor -->
                    <SectionEditor
                        v-if="currentSectionContent"
                        :section-type="activeSection"
                        :content="currentSectionContent"
                        :wedding="currentWedding"
                        :enabled-sections="enabledSections"
                        :logo-initials="props.logoInitials"
                        :is-applying-theme-preset="isApplyingThemePreset"
                        @change="handleSectionUpdate"
                        @apply-theme-preset="handleApplyThemePreset"
                        class="flex-1 min-h-0 overflow-hidden"
                    />
                </div>
            </main>

            <!-- History Overlay (Mobile) -->
            <div
                v-if="showVersionHistory"
                class="fixed inset-0 z-40 bg-black/40 lg:hidden"
                @click="showVersionHistory = false"
            ></div>

            <!-- Version History Panel -->
            <aside
                v-if="showVersionHistory"
                class="fixed inset-y-0 right-0 z-50 h-full w-[92vw] max-w-sm bg-white border-l border-gray-200 flex flex-col shadow-xl lg:static lg:z-auto lg:w-80 lg:max-w-none lg:shadow-none"
            >
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h2 class="font-medium text-gray-900">Histórico de Versões</h2>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ publishedVersionsCount }} publicação(ões) registrada(s)
                        </p>
                    </div>
                    <button @click="showVersionHistory = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="border-b border-gray-200 p-3 flex gap-2">
                    <button
                        @click="historyFilter = 'all'"
                        class="px-3 py-1.5 text-xs rounded-md border"
                        :class="historyFilter === 'all' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                    >
                        Todas ({{ versions.length }})
                    </button>
                    <button
                        @click="historyFilter = 'published'"
                        class="px-3 py-1.5 text-xs rounded-md border"
                        :class="historyFilter === 'published' ? 'bg-wedding-600 text-white border-wedding-700' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                    >
                        Publicações ({{ publishedVersionsCount }})
                    </button>
                </div>

                <div class="flex-1 overflow-auto">
                    <div
                        v-if="latestThemePreSnapshot && historyFilter === 'all'"
                        class="m-3 rounded-lg border border-amber-200 bg-amber-50 p-3"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-800">Restauração Rápida</p>
                        <p class="mt-1 text-sm text-amber-900">
                            Snapshot antes do tema:
                            <span class="font-medium">{{ getThemeSnapshotLabel(latestThemePreSnapshot) || 'Tema' }}</span>
                        </p>
                        <p class="mt-1 text-xs text-amber-700">
                            {{ new Date(latestThemePreSnapshot.created_at).toLocaleString('pt-BR') }}
                        </p>
                        <button
                            @click="quickRestoreThemeSnapshot(latestThemePreSnapshot)"
                            :disabled="isRestoringVersion || quickRestoreVersionId === latestThemePreSnapshot.id"
                            class="mt-3 inline-flex items-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg
                                v-if="quickRestoreVersionId === latestThemePreSnapshot.id"
                                class="mr-2 h-3.5 w-3.5 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ quickRestoreVersionId === latestThemePreSnapshot.id ? 'Restaurando...' : 'Restaurar este snapshot' }}
                        </button>
                    </div>

                    <div v-if="isLoadingVersions" class="p-4 text-center text-gray-500">
                        <svg class="animate-spin w-6 h-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Carregando...
                    </div>

                    <div v-else-if="versions.length === 0" class="p-4 text-center text-gray-500 text-sm">
                        Nenhuma versão encontrada.
                    </div>

                    <div v-else-if="filteredVersions.length === 0" class="p-4 text-center text-gray-500 text-sm">
                        Nenhuma versão para este filtro.
                    </div>

                    <ul v-else class="divide-y divide-gray-200">
                        <li
                            v-for="version in filteredVersions"
                            :key="version.id"
                            class="p-4 hover:bg-gray-50"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ version.summary }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ version.user_name || 'Sistema' }} • {{ new Date(version.created_at).toLocaleString('pt-BR') }}
                                    </p>
                                    <span
                                        v-if="version.is_published"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mt-2"
                                    >
                                        Publicado
                                    </span>
                                    <span
                                        v-if="isThemePreSnapshot(version)"
                                        class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 mt-2"
                                    >
                                        Antes do tema {{ getThemeSnapshotLabel(version) ? `• ${getThemeSnapshotLabel(version)}` : '' }}
                                    </span>
                                </div>
                                <div class="ml-2 flex flex-col items-end gap-1.5">
                                    <button
                                        v-if="isThemePreSnapshot(version)"
                                        @click="quickRestoreThemeSnapshot(version)"
                                        :disabled="isRestoringVersion || quickRestoreVersionId === version.id"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-md border border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {{ quickRestoreVersionId === version.id ? 'Restaurando...' : 'Restaurar direto' }}
                                    </button>
                                    <button
                                        @click="openRestoreDialog(version)"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md border transition-colors"
                                        :class="version.is_published
                                            ? 'text-wedding-700 bg-wedding-100 border-wedding-200 hover:bg-rose-100'
                                            : 'text-wedding-600 bg-wedding-50 border-wedding-200 hover:bg-wedding-100'"
                                    >
                                        Restaurar
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>

        <!-- Template Catalog Modal -->
        <div
            v-if="showTemplateCatalogModal"
            class="fixed inset-0 z-[68] flex items-center justify-center bg-gray-950/60 p-4"
            @click.self="closeTemplateCatalog"
        >
            <div class="flex h-[90vh] w-[90vw] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Templates disponíveis</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Navegue e aplique um template sem sair do editor.
                        </p>
                    </div>
                    <button
                        @click="closeTemplateCatalog"
                        :disabled="isApplyingTemplateCatalog"
                        class="rounded-md p-1 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                        aria-label="Fechar modal de templates"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <div v-if="isLoadingTemplateCatalog" class="flex h-full items-center justify-center text-sm text-gray-500">
                        <svg class="mr-2 h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Carregando templates...
                    </div>

                    <div v-else-if="templateCatalogError" class="mx-auto max-w-xl rounded-xl border border-red-200 bg-red-50 p-4">
                        <p class="text-sm font-medium text-red-700">{{ templateCatalogError }}</p>
                        <button
                            @click="loadTemplateCatalog({ force: true })"
                            class="mt-3 rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                        >
                            Tentar novamente
                        </button>
                    </div>

                    <div v-else-if="templateCatalogItems.length === 0" class="flex h-full items-center justify-center">
                        <p class="text-sm text-gray-500">Nenhum template disponível no momento.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        <article
                            v-for="templateItem in templateCatalogItems"
                            :key="templateItem.id"
                            class="flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                        >
                            <div class="group relative aspect-[16/10] overflow-hidden bg-gray-100">
                                <img
                                    :src="resolveTemplateThumbnail(templateItem)"
                                    :alt="templateItem.name"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    @error="onTemplateImageError($event, templateItem.id)"
                                />

                                <button
                                    @click.stop="openTemplateNavigation(templateItem)"
                                    class="absolute inset-0 flex items-center justify-center bg-black/45 text-sm font-semibold text-white opacity-0 transition-opacity duration-200 hover:bg-black/55 group-hover:opacity-100"
                                >
                                    Navegar
                                </button>

                                <span
                                    v-if="templateItem.is_locked"
                                    class="absolute right-2 top-2 rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-800"
                                >
                                    Upgrade
                                </span>
                            </div>

                            <div class="flex flex-1 flex-col gap-2 p-4">
                                <h3 class="text-sm font-semibold text-gray-900">{{ templateItem.name }}</h3>
                                <p class="min-h-[2.25rem] text-xs text-gray-600">
                                    {{ templateItem.description || 'Sem descrição para este template.' }}
                                </p>

                                <p
                                    v-if="templateItem.is_locked"
                                    class="text-xs font-medium text-amber-700"
                                >
                                    Disponível para:
                                    {{ Array.isArray(templateItem.required_plans) && templateItem.required_plans.length > 0
                                        ? templateItem.required_plans.join(', ')
                                        : 'planos superiores' }}.
                                </p>

                                <div class="mt-auto space-y-2">
                                    <button
                                        @click="applyTemplateFromCatalog(templateItem, 'merge')"
                                        :disabled="isApplyingTemplateCatalog || templateItem.is_locked || templateItem.can_apply === false"
                                        class="w-full rounded-md border border-wedding-200 bg-wedding-100 px-3 py-2 text-xs font-semibold text-wedding-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50"
                                        :title="TEMPLATE_PARTIAL_TOOLTIP"
                                    >
                                        {{ isTemplateApplyRunning(templateItem.id, 'merge') ? 'Aplicando...' : 'Aplicar Parcial' }}
                                    </button>

                                    <button
                                        @click="applyTemplateFromCatalog(templateItem, 'overwrite')"
                                        :disabled="isApplyingTemplateCatalog || templateItem.is_locked || templateItem.can_apply === false"
                                        class="w-full rounded-md border border-wedding-700 bg-wedding-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-wedding-700 disabled:cursor-not-allowed disabled:opacity-50"
                                        :title="TEMPLATE_TOTAL_TOOLTIP"
                                    >
                                        {{ isTemplateApplyRunning(templateItem.id, 'overwrite') ? 'Aplicando...' : 'Aplicar Total' }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Version Dialog -->
        <div
            v-if="showRestoreDialog"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-950/50 p-4"
            @click.self="closeRestoreDialog"
        >
            <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Restaurar versão</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Esta ação substituirá o rascunho atual pelo conteúdo da versão selecionada.
                    </p>
                </div>

                <div class="px-5 py-4 text-sm text-gray-700">
                    <p><span class="font-medium">Resumo:</span> {{ versionToRestore?.summary || 'Sem resumo' }}</p>
                    <p class="mt-1">
                        <span class="font-medium">Data:</span>
                        {{ versionToRestore ? new Date(versionToRestore.created_at).toLocaleString('pt-BR') : '-' }}
                    </p>
                    <p v-if="versionToRestore?.is_published" class="mt-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                        Versão publicada
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button
                        @click="closeRestoreDialog"
                        :disabled="isRestoringVersion"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="confirmRestore"
                        :disabled="isRestoringVersion"
                        class="inline-flex items-center rounded-md bg-wedding-600 px-4 py-2 text-sm font-medium text-white hover:bg-wedding-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="isRestoringVersion"
                            class="mr-2 h-4 w-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ isRestoringVersion ? 'Restaurando...' : 'Restaurar versão' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Theme Preset Result Dialog -->
        <div
            v-if="showThemePresetResultDialog"
            class="fixed inset-0 z-[75] flex items-center justify-center bg-gray-950/50 p-4"
            @click.self="closeThemePresetResultDialog"
        >
            <div class="flex w-full max-w-3xl max-h-[92vh] flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Tema aplicado com sucesso</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Tema <span class="font-medium">{{ themePresetResult.presetName }}</span> aplicado em todo o fluxo visual configurado.
                    </p>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm text-gray-700">
                            {{ themePresetResult.presetDescription }}
                        </p>
                        <p class="mt-2 text-xs text-gray-600">
                            Uma versão de segurança foi salva antes da aplicação para restauração rápida.
                        </p>
                    </div>

                    <div v-if="themePresetResult.qaResult" class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 font-medium text-green-700">
                                {{ themeQaCounts.passed }} OK
                            </span>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 font-medium text-amber-700">
                                {{ themeQaCounts.warning }} Avisos
                            </span>
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 font-medium text-red-700">
                                {{ themeQaCounts.failed }} Erros
                            </span>
                        </div>

                        <QAPanel
                            :checks="themePresetResult.qaResult.checks || []"
                            :show-all="true"
                            @navigate-to-section="navigateFromThemeQa"
                        />
                    </div>

                    <div v-else class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Não foi possível carregar o checklist de qualidade neste momento. Você pode abrir o preview e publicar quando quiser.
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button
                        v-if="themePresetResult.preVersionId"
                        @click="restoreThemePresetPreviousVersion"
                        :disabled="isRestoringThemeSnapshot"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="isRestoringThemeSnapshot"
                            class="mr-2 h-4 w-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ isRestoringThemeSnapshot ? 'Restaurando...' : 'Restaurar versão anterior' }}
                    </button>
                    <button
                        @click="openPreview"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Abrir preview
                    </button>
                    <button
                        @click="closeThemePresetResultDialog"
                        :disabled="isRestoringThemeSnapshot"
                        class="rounded-md bg-wedding-600 px-4 py-2 text-sm font-semibold text-white hover:bg-wedding-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>

        <!-- Publish Dialog -->
        <PublishDialog
            :is-open="showPublishDialog"
            :site-id="site.id"
            :site-name="site.name || 'Site'"
            @close="showPublishDialog = false"
            @published="handlePublishSuccess"
            @error="handlePublishError"
            @navigate-to-section="navigateFromPublishQa"
        />

        <!-- Fullscreen Preview -->
        <FullscreenPreview
            :show="showPreview"
            :content="draftContent"
            :wedding="currentWedding"
            :site-id="site.id"
            @close="showPreview = false"
        />

        <!-- Toast Notification -->
        <Toast
            :show="toast.show"
            :type="toast.type"
            :message="toast.message"
            @close="closeToast"
        />
    </div>
</template>

<style scoped>
.bg-wedding-50 {
    background-color: #fff1f4;
}
.bg-wedding-100 {
    background-color: #fde8ee;
}
.bg-wedding-600 {
    background-color: #e11d48;
}
.bg-wedding-700 {
    background-color: #b9163a;
}
.text-wedding-600 {
    color: #b9163a;
}
.text-wedding-700 {
    color: #4A2F39;
}
.text-wedding-800 {
    color: #4A2F39;
}
.border-wedding-200 {
    border-color: #ffccd9;
}
.border-wedding-700 {
    border-color: #b9163a;
}
.hover\:bg-wedding-100:hover {
    background-color: #fde8ee;
}
.publish-button {
    background-color: #e11d48;
    border-color: #b9163a;
}
.publish-button:hover:not(:disabled) {
    background-color: #b9163a;
}
</style>
