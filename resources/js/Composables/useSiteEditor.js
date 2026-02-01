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
    giftRegistry: 'lista-presentes',
    rsvp: 'confirmar-presenca',
    photoGallery: 'galeria',
};

/**
 * Section labels for display in select options
 */
export const SECTION_LABELS = {
    hero: 'Hero',
    saveTheDate: 'Save the Date',
    giftRegistry: 'Lista de Presentes',
    rsvp: 'Confirmar Presença',
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

/**
 * Site Editor composable
 * 
 * @param {Object} initialSite - Initial site data from props
 * @returns {Object} Reactive state and methods for site editing
 */
export default function useSiteEditor(initialSite) {
    // Reactive state
    const site = ref(deepClone(initialSite));
    const draftContent = ref(deepClone(initialSite.draft_content || {}));
    const originalContent = ref(deepClone(initialSite.draft_content || {}));
    
    // Status flags
    const isDirty = ref(false);
    const isSaving = ref(false);
    const isPublishing = ref(false);
    const lastSaved = ref(null);
    const error = ref(null);

    /**
     * Check if content has changed from original
     */
    const checkDirty = () => {
        isDirty.value = JSON.stringify(draftContent.value) !== JSON.stringify(originalContent.value);
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
        checkDirty();
    };

    /**
     * Update meta information
     * 
     * @param {Object} meta - The new meta data
     */
    const updateMeta = (meta) => {
        draftContent.value.meta = { ...draftContent.value.meta, ...meta };
        checkDirty();
    };

    /**
     * Update theme settings
     * 
     * @param {Object} theme - The new theme data
     */
    const updateTheme = (theme) => {
        draftContent.value.theme = { ...draftContent.value.theme, ...theme };
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
            const response = await axios.put(`/api/sites/${site.value.id}/draft`, {
                content: draftContent.value,
            });

            // Update local state with server response
            site.value = response.data.data;
            
            // Sync draft content with server response to ensure consistency
            if (response.data.data.draft_content) {
                draftContent.value = deepClone(response.data.data.draft_content);
            }
            
            originalContent.value = deepClone(draftContent.value);
            isDirty.value = false;
            lastSaved.value = new Date().toISOString();

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
            const response = await axios.post(`/api/sites/${site.value.id}/publish`);

            // Update local state with server response
            site.value = response.data.data;
            draftContent.value = deepClone(response.data.data.draft_content);
            originalContent.value = deepClone(response.data.data.draft_content);
            isDirty.value = false;

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
            const response = await axios.post(`/api/sites/${site.value.id}/rollback`);

            // Update local state with server response
            site.value = response.data.data;
            draftContent.value = deepClone(response.data.data.draft_content);
            originalContent.value = deepClone(response.data.data.draft_content);
            isDirty.value = false;
            lastSaved.value = new Date().toISOString();

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
     * Auto-save with debounce (2 seconds)
     * Watches for changes in draftContent and triggers save
     */
    const debouncedSave = debounce(() => {
        if (isDirty.value) {
            save();
        }
    }, 2000);

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
        save,
        publish,
        rollback,
        discardChanges,
    };
}
