/**
 * useSiteEditor Composable
 * 
 * Manages site editor state including draft content, auto-save,
 * and API interactions for saving, publishing, and rollback.
 * 
 * @Requirements: 3.1, 3.2, 19.1
 */
import { ref, watch } from 'vue';
import axios from 'axios';

/**
 * Section IDs for anchor links (excluding header and footer)
 * These are fixed IDs used for CTA targets
 */
export const SECTION_IDS = {
    hero: 'hero',
    saveTheDate: 'save-the-date',
    giftRegistry: 'gift-registry',
    rsvp: 'rsvp',
    photoGallery: 'photo-gallery',
};

/**
 * Section labels for display in select options
 */
export const SECTION_LABELS = {
    hero: 'Destaque',
    saveTheDate: 'Save the Date',
    giftRegistry: 'Lista de Presentes',
    rsvp: 'Confirme Presença',
    photoGallery: 'Galeria de Fotos',
};

/**
 * Debounce utility function
 */
function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

/**
 * Deep clone utility
 */
function deepClone(obj) {
    return JSON.parse(JSON.stringify(obj));
}

const DEFAULT_THEME_SETTINGS = {
    primaryColor: '#e11d48',
    secondaryColor: '#be123c',
    baseBackgroundColor: '#ffffff',
    surfaceBackgroundColor: '#f9fafb',
    fontFamily: 'Figtree',
    fontSize: '14px',
};

const LEGACY_BASE_BACKGROUND_COLORS = new Set([
    '#ffffff',
    '#fff',
]);

const LEGACY_SURFACE_BACKGROUND_COLORS = new Set([
    '#f9fafb',
    '#fde8ee',
    '#f8f6f4',
    '#f5f5f5',
    '#ffffff',
    '#fff',
]);

const THEME_BACKGROUND_BINDINGS = [
    { section: 'header', path: ['style', 'backgroundColor'], role: 'baseBackgroundColor' },
    { section: 'saveTheDate', path: ['style', 'backgroundColor'], role: 'surfaceBackgroundColor' },
    { section: 'giftRegistry', path: ['style', 'backgroundColor'], role: 'baseBackgroundColor' },
    { section: 'rsvp', path: ['style', 'backgroundColor'], role: 'surfaceBackgroundColor' },
    { section: 'photoGallery', path: ['style', 'backgroundColor'], role: 'baseBackgroundColor' },
];

const normalizeColorToken = (value) => {
    if (typeof value !== 'string') {
        return null;
    }

    const normalized = value.trim().toLowerCase();
    return normalized.length > 0 ? normalized : null;
};

const ensureThemeDefaults = (content = {}) => {
    const normalized = deepClone(content || {});
    normalized.theme = {
        ...DEFAULT_THEME_SETTINGS,
        ...(normalized.theme || {}),
    };

    return normalized;
};

const getNestedValue = (source, path) => {
    return path.reduce((cursor, segment) => {
        if (!cursor || typeof cursor !== 'object') {
            return undefined;
        }

        return cursor[segment];
    }, source);
};

const setNestedValue = (target, path, value) => {
    if (!target || typeof target !== 'object') {
        return;
    }

    let cursor = target;
    for (let index = 0; index < path.length - 1; index += 1) {
        const segment = path[index];
        if (!cursor[segment] || typeof cursor[segment] !== 'object') {
            cursor[segment] = {};
        }

        cursor = cursor[segment];
    }

    cursor[path[path.length - 1]] = value;
};

const shouldSyncThemeBackground = (currentValue, previousValue, role) => {
    const normalizedCurrent = normalizeColorToken(currentValue);
    if (!normalizedCurrent) {
        return true;
    }

    const normalizedPrevious = normalizeColorToken(previousValue);
    if (normalizedPrevious && normalizedCurrent === normalizedPrevious) {
        return true;
    }

    const legacySet = role === 'baseBackgroundColor'
        ? LEGACY_BASE_BACKGROUND_COLORS
        : LEGACY_SURFACE_BACKGROUND_COLORS;

    return legacySet.has(normalizedCurrent);
};

const syncThemeBackgroundBindings = (draft, previousTheme, nextTheme) => {
    if (!draft?.sections || typeof draft.sections !== 'object') {
        return;
    }

    THEME_BACKGROUND_BINDINGS.forEach(({ section, path, role }) => {
        const sectionContent = draft.sections[section];
        if (!sectionContent || typeof sectionContent !== 'object') {
            return;
        }

        const currentBackground = getNestedValue(sectionContent, path);
        const previousBackground = previousTheme?.[role];
        const nextBackground = nextTheme?.[role];

        if (!nextBackground) {
            return;
        }

        if (shouldSyncThemeBackground(currentBackground, previousBackground, role)) {
            setNestedValue(sectionContent, path, nextBackground);
        }
    });
};

/**
 * Site Editor composable
 * 
 * @param {Object} initialSite - Initial site data from props
 * @returns {Object} Reactive state and methods for site editing
 */
export default function useSiteEditor(initialSite) {
    // Reactive state
    const site = ref(deepClone(initialSite));
    const draftContent = ref(ensureThemeDefaults(initialSite.draft_content || {}));
    const originalContent = ref(deepClone(draftContent.value));
    
    // Status flags
    const isDirty = ref(false);
    const isSaving = ref(false);
    const isPublishing = ref(false);
    const lastSaved = ref(null);
    const error = ref(null);
    const localMutationToken = ref(0);

    /**
     * Check if content has changed from original
     */
    const checkDirty = () => {
        isDirty.value = JSON.stringify(draftContent.value) !== JSON.stringify(originalContent.value);
    };

    /**
     * Hydrate local editor state from server site payload.
     *
     * @param {Object} nextSiteData
     * @param {Object} options
     * @param {boolean} options.touchLastSaved
     */
    const applyServerSiteData = (nextSiteData, { touchLastSaved = true } = {}) => {
        if (!nextSiteData || typeof nextSiteData !== 'object') {
            return;
        }

        site.value = deepClone({
            ...site.value,
            ...nextSiteData,
        });

        const nextDraftContent = ensureThemeDefaults(nextSiteData.draft_content || {});
        draftContent.value = nextDraftContent;
        originalContent.value = deepClone(nextDraftContent);
        isDirty.value = false;

        if (touchLastSaved) {
            lastSaved.value = new Date().toISOString();
        }
    };

    const buildSiteSettingsPayload = () => {
        const settings = draftContent.value?.settings || {};
        const payload = {};

        if (Object.prototype.hasOwnProperty.call(settings, 'slug')) {
            const slugValue = settings.slug;
            if (slugValue !== null && slugValue !== undefined && slugValue !== '') {
                payload.slug = String(slugValue).trim();
            }
        }

        if (Object.prototype.hasOwnProperty.call(settings, 'custom_domain')) {
            const domainValue = settings.custom_domain;
            payload.custom_domain = domainValue === '' || domainValue === undefined
                ? null
                : domainValue;
        }

        if (Object.prototype.hasOwnProperty.call(settings, 'access_token')) {
            const passwordValue = settings.access_token;
            if (passwordValue === null) {
                payload.access_token = null;
            } else if (passwordValue !== '' && passwordValue !== undefined) {
                payload.access_token = passwordValue;
            }
        }

        return payload;
    };

    const hasPendingSiteSettingsChanges = (payload) => {
        if (Object.keys(payload).length === 0) {
            return false;
        }

        if (Object.prototype.hasOwnProperty.call(payload, 'slug')) {
            if ((site.value?.slug ?? null) !== payload.slug) {
                return true;
            }
        }

        if (Object.prototype.hasOwnProperty.call(payload, 'custom_domain')) {
            if ((site.value?.custom_domain ?? null) !== payload.custom_domain) {
                return true;
            }
        }

        if (Object.prototype.hasOwnProperty.call(payload, 'access_token')) {
            if (payload.access_token === null) {
                return Boolean(site.value?.has_password);
            }

            // We don't have the plain password in frontend state, so treat non-empty values as pending.
            return true;
        }

        return false;
    };

    const syncSiteSettings = async ({ silentValidation = false } = {}) => {
        const payload = buildSiteSettingsPayload();

        if (!hasPendingSiteSettingsChanges(payload)) {
            return true;
        }

        try {
            const response = await axios.put(`/admin/sites/${site.value.id}/settings`, payload);

            if (response.data?.data) {
                site.value = { ...site.value, ...response.data.data };
            }

            return true;
        } catch (err) {
            const status = err.response?.status;

            if (silentValidation && status === 422) {
                return false;
            }

            if (status === 422 && err.response?.data?.errors) {
                const firstErrorGroup = Object.values(err.response.data.errors)[0];
                error.value = Array.isArray(firstErrorGroup)
                    ? (firstErrorGroup[0] || 'Erro de validação nas configurações do site.')
                    : 'Erro de validação nas configurações do site.';
            } else {
                error.value = err.response?.data?.message || 'Erro ao salvar configurações do site';
            }

            console.error('Settings save error:', err);
            return false;
        }
    };

    const persistDraft = async ({ createVersion, summary = null }) => {
        const requestMutationToken = localMutationToken.value;
        const payloadContent = deepClone(draftContent.value);
        const payload = {
            content: payloadContent,
            create_version: createVersion,
        };

        if (summary) {
            payload.summary = summary;
        }

        const response = await axios.put(`/admin/sites/${site.value.id}/draft`, payload);

        // Guard against race conditions:
        // if local content changed while this request was in-flight,
        // do not overwrite the latest editor state with an older response.
        if (localMutationToken.value !== requestMutationToken) {
            site.value = deepClone({
                ...site.value,
                ...response.data.data,
            });
            return false;
        }

        applyServerSiteData(response.data.data);
        return true;
    };

    /**
     * Create a version snapshot even when there are no content changes.
     *
     * Useful for "before/after" milestones such as applying a theme preset.
     *
     * @param {string} summary - Version summary shown in history
     * @returns {Promise<boolean>} Success status
     */
    const createVersionSnapshot = async (summary = 'Snapshot manual') => {
        if (isSaving.value) {
            return false;
        }

        isSaving.value = true;
        error.value = null;

        try {
            const settingsSaved = await syncSiteSettings({ silentValidation: false });
            if (!settingsSaved) {
                return false;
            }

            await persistDraft({
                createVersion: true,
                summary,
            });

            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao criar snapshot';
            console.error('Snapshot error:', err);
            return false;
        } finally {
            isSaving.value = false;
        }
    };

    /**
     * Update a specific section in the draft content
     * 
     * @param {string} sectionKey - The section key (e.g., 'header', 'hero')
     * @param {Object} data - The new section data
     */
    const updateSection = (sectionKey, data) => {
        if (!draftContent.value.sections) {
            draftContent.value.sections = {};
        }
        draftContent.value.sections[sectionKey] = deepClone(data);
        localMutationToken.value += 1;
        checkDirty();
    };

    /**
     * Update meta information
     * 
     * @param {Object} meta - The new meta data
     */
    const updateMeta = (meta) => {
        draftContent.value.meta = { ...draftContent.value.meta, ...meta };
        localMutationToken.value += 1;
        checkDirty();
    };

    /**
     * Update theme settings
     * 
     * @param {Object} theme - The new theme data
     */
    const updateTheme = (theme) => {
        const previousTheme = {
            ...DEFAULT_THEME_SETTINGS,
            ...(draftContent.value.theme || {}),
        };

        const nextTheme = {
            ...previousTheme,
            ...(theme || {}),
        };

        draftContent.value.theme = nextTheme;
        syncThemeBackgroundBindings(draftContent.value, previousTheme, nextTheme);
        localMutationToken.value += 1;
        checkDirty();
    };

    const touchMutation = () => {
        localMutationToken.value += 1;
        checkDirty();
    };

    /**
     * Save draft content to the server
     * 
     * @Requirements: 3.1
     * @returns {Promise<boolean>} Success status
     */
    const save = async () => {
        if (!isDirty.value || isSaving.value) {
            return false;
        }

        isSaving.value = true;
        error.value = null;

        try {
            const settingsSaved = await syncSiteSettings({ silentValidation: false });
            if (!settingsSaved) {
                return false;
            }

            const applied = await persistDraft({ createVersion: true });
            if (!applied) {
                return false;
            }

            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao salvar rascunho';
            console.error('Save error:', err);
            return false;
        } finally {
            isSaving.value = false;
        }
    };

    /**
     * Publish the site
     * 
     * @Requirements: 3.2
     * @returns {Promise<boolean>} Success status
     */
    const publish = async () => {
        if (isPublishing.value) {
            return false;
        }

        // Save any pending changes first
        if (isDirty.value) {
            const saved = await save();
            if (!saved) {
                return false;
            }
        }

        isPublishing.value = true;
        error.value = null;

        try {
            const response = await axios.post(`/admin/sites/${site.value.id}/publish`);
            applyServerSiteData(response.data.data);

            // Show success message
            alert('Site publicado com sucesso!');

            return true;
        } catch (err) {
            console.error('Publish error:', err);
            
            // Handle different error types
            if (err.response?.status === 403) {
                error.value = 'Você não tem permissão para publicar este site.';
            } else if (err.response?.status === 401) {
                error.value = 'Sessão expirada. Por favor, faça login novamente.';
            } else if (err.response?.status === 422 && err.response?.data?.errors) {
                error.value = {
                    message: 'Erros de validação',
                    errors: err.response.data.errors,
                };
            } else {
                error.value = err.response?.data?.message || 'Erro ao publicar site';
            }
            
            // Show error to user
            alert(typeof error.value === 'string' ? error.value : error.value.message);
            
            return false;
        } finally {
            isPublishing.value = false;
        }
    };

    /**
     * Rollback to the last published version
     * 
     * @Requirements: 19.1
     * @returns {Promise<boolean>} Success status
     */
    const rollback = async () => {
        if (isSaving.value) {
            return false;
        }

        isSaving.value = true;
        error.value = null;

        try {
            const response = await axios.post(`/admin/sites/${site.value.id}/rollback`);
            applyServerSiteData(response.data.data);

            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao fazer rollback';
            console.error('Rollback error:', err);
            return false;
        } finally {
            isSaving.value = false;
        }
    };

    /**
     * Reset draft to original content (discard changes)
     */
    const discardChanges = () => {
        draftContent.value = deepClone(originalContent.value);
        isDirty.value = false;
    };

    /**
     * Auto-save with debounce (5 seconds)
     * Watches for changes in draftContent and triggers save
     */
    const debouncedSave = debounce(() => {
        if (isDirty.value && !isSaving.value) {
            isSaving.value = true;
            (async () => {
                try {
                    await syncSiteSettings({ silentValidation: true });
                    await persistDraft({ createVersion: false });
                } catch (err) {
                    error.value = err.response?.data?.message || 'Erro ao salvar rascunho';
                    console.error('Auto-save error:', err);
                } finally {
                    isSaving.value = false;
                }
            })();
        }
    }, 5000);

    // Watch for content changes and trigger auto-save
    watch(
        () => draftContent.value,
        () => {
            checkDirty();
            if (isDirty.value) {
                debouncedSave();
            }
        },
        { deep: true }
    );

    return {
        // State
        site,
        draftContent,
        isDirty,
        isSaving,
        isPublishing,
        lastSaved,
        error,

        // Methods
        updateSection,
        updateMeta,
        updateTheme,
        touchMutation,
        save,
        createVersionSnapshot,
        applyServerSiteData,
        publish,
        rollback,
        discardChanges,
    };
}
