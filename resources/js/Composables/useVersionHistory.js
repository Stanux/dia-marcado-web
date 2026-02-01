/**
 * useVersionHistory Composable
 * 
 * Manages version history state and operations for site versioning.
 * Provides methods to load versions and restore to a specific version.
 * 
 * @Requirements: 4.1, 4.4
 */
import { ref } from 'vue';
import axios from 'axios';

/**
 * Version History composable
 * 
 * @param {string} siteId - The site ID to manage versions for
 * @returns {Object} Reactive state and methods for version management
 */
export default function useVersionHistory(siteId) {
    // Reactive state
    const versions = ref([]);
    const isLoading = ref(false);
    const isRestoring = ref(false);
    const error = ref(null);
    const currentSiteId = ref(siteId);

    /**
     * Load versions from the server
     * 
     * @Requirements: 4.1
     * @returns {Promise<Array>} List of versions
     */
    const loadVersions = async () => {
        if (isLoading.value) {
            return versions.value;
        }

        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.get(`/api/sites/${currentSiteId.value}/versions`);
            versions.value = response.data.data || [];
            return versions.value;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao carregar versões';
            console.error('Load versions error:', err);
            return [];
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Restore a specific version
     * 
     * @Requirements: 4.4
     * @param {string} versionId - The version ID to restore
     * @returns {Promise<Object|null>} Updated site data or null on error
     */
    const restore = async (versionId) => {
        if (isRestoring.value) {
            return null;
        }

        isRestoring.value = true;
        error.value = null;

        try {
            const response = await axios.post(`/api/sites/${currentSiteId.value}/restore`, {
                version_id: versionId,
            });

            // Reload versions after restore
            await loadVersions();

            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao restaurar versão';
            console.error('Restore version error:', err);
            return null;
        } finally {
            isRestoring.value = false;
        }
    };

    /**
     * Get a specific version by ID
     * 
     * @param {string} versionId - The version ID to find
     * @returns {Object|null} The version object or null if not found
     */
    const getVersion = (versionId) => {
        return versions.value.find(v => v.id === versionId) || null;
    };

    /**
     * Get the latest published version
     * 
     * @returns {Object|null} The latest published version or null
     */
    const getLatestPublished = () => {
        return versions.value.find(v => v.is_published) || null;
    };

    /**
     * Clear versions cache
     */
    const clearCache = () => {
        versions.value = [];
    };

    /**
     * Update site ID (useful when switching sites)
     * 
     * @param {string} newSiteId - The new site ID
     */
    const setSiteId = (newSiteId) => {
        if (newSiteId !== currentSiteId.value) {
            currentSiteId.value = newSiteId;
            clearCache();
        }
    };

    return {
        // State
        versions,
        isLoading,
        isRestoring,
        error,

        // Methods
        loadVersions,
        restore,
        getVersion,
        getLatestPublished,
        clearCache,
        setSiteId,
    };
}
