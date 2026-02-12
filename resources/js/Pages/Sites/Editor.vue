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
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import SectionSidebar from '@/Components/Site/SectionSidebar.vue';
import SectionEditor from '@/Components/Site/SectionEditor.vue';
import FullscreenPreview from '@/Components/Site/FullscreenPreview.vue';
import PublishDialog from '@/Components/Site/PublishDialog.vue';
import Toast from '@/Components/Site/Toast.vue';
import useSiteEditor from '@/Composables/useSiteEditor';
import useVersionHistory from '@/Composables/useVersionHistory';

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    weddingId: {
        type: String,
        default: null,
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

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
    updateSection,
    updateMeta,
    updateTheme,
    save,
    publish,
    rollback,
} = useSiteEditor(props.site);

const {
    versions,
    isLoading: isLoadingVersions,
    loadVersions,
    restore,
} = useVersionHistory(props.site.id);

// Local state
const activeSection = ref('header');
const showPreview = ref(false);
const showVersionHistory = ref(false);
const showPublishDialog = ref(false);

// Toast state
const toast = ref({
    show: false,
    type: 'success',
    message: '',
});

// Section list for sidebar
const sections = computed(() => {
    if (!draftContent.value?.sections) return [];
    
    return [
        { key: 'header', label: 'Cabeçalho', icon: 'header' },
        { key: 'hero', label: 'Destaque', icon: 'image' },
        { key: 'saveTheDate', label: 'Save the Date', icon: 'calendar' },
        { key: 'giftRegistry', label: 'Lista de Presentes', icon: 'gift' },
        { key: 'rsvp', label: 'Confirme Presença', icon: 'users' },
        { key: 'photoGallery', label: 'Galeria de Fotos', icon: 'images' },
        { key: 'footer', label: 'Rodapé', icon: 'footer' },
    ].map(section => ({
        ...section,
        enabled: draftContent.value.sections[section.key]?.enabled ?? false,
    }));
});

// Current section content
const currentSectionContent = computed(() => {
    // Special sections (meta, theme, settings) are stored at root level
    if (activeSection.value === 'meta') {
        return draftContent.value?.meta || {
            title: '',
            description: '',
            ogImage: '',
            canonical: '',
        };
    }

    if (activeSection.value === 'theme') {
        return draftContent.value?.theme || {
            primaryColor: '#d4a574',
            secondaryColor: '#8b7355',
            fontFamily: 'Playfair Display',
            fontSize: '16px',
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
    activeSection.value = sectionKey;
};

// Handle section toggle (enable/disable)
const toggleSection = (sectionKey) => {
    const section = draftContent.value.sections[sectionKey];
    if (section) {
        updateSection(sectionKey, { ...section, enabled: !section.enabled });
    }
};

// Handle section content update
const handleSectionUpdate = async (data) => {
    // Special sections use dedicated update methods
    if (activeSection.value === 'meta') {
        updateMeta(data);
    } else if (activeSection.value === 'theme') {
        updateTheme(data);
    } else if (activeSection.value === 'settings') {
        // Settings include both site-level and content-level data
        // Extract site-level fields
        const siteFields = ['slug', 'custom_domain', 'access_token'];
        const siteData = {};
        const contentData = {};
        
        Object.keys(data).forEach(key => {
            if (key === '__saveSettings') {
                return;
            }
            if (siteFields.includes(key)) {
                if (key === 'access_token') {
                    // Only send access_token if explicitly set or cleared
                    if (data[key] === null) {
                        siteData[key] = null;
                    } else if (data[key] !== '' && data[key] !== undefined) {
                        siteData[key] = data[key];
                    }
                } else if (data[key] === '' || data[key] === undefined) {
                    siteData[key] = null;
                } else {
                    siteData[key] = data[key];
                }
            } else if (key !== 'is_published' && key !== 'published_at') {
                contentData[key] = data[key];
            }
        });
        
        // Update site-level fields only on explicit save (avoid 422 while typing)
        const shouldSaveSettings = data?.__saveSettings === true;
        if (shouldSaveSettings && Object.keys(siteData).length > 0) {
            try {
                const response = await axios.put(`/admin/sites/${site.value.id}/settings`, siteData);
                if (response.data?.data) {
                    // Update site object with response
                    Object.assign(site.value, {
                        slug: response.data.data.slug,
                        custom_domain: response.data.data.custom_domain,
                        has_password: response.data.data.has_password,
                    });
                    showToast('success', 'Configurações atualizadas com sucesso!');
                }
            } catch (error) {
                console.error('Error updating site settings:', error);
                const message = error.response?.data?.message || 'Erro ao atualizar configurações';
                showToast('error', message);
            }
        }
        
        // Update content-level settings
        if (Object.keys(contentData).length > 0) {
            if (!draftContent.value.settings) {
                draftContent.value.settings = {};
            }
            draftContent.value.settings = { ...draftContent.value.settings, ...contentData };
        }

        // Keep local edits for site-level fields in draft settings to avoid input reset
        if (Object.keys(siteData).length > 0) {
            if (!draftContent.value.settings) {
                draftContent.value.settings = {};
            }
            draftContent.value.settings = { ...draftContent.value.settings, ...siteData };
        }
    } else {
        // Regular sections
        updateSection(activeSection.value, data);
    }
};

// Handle publish - open dialog
const handlePublish = () => {
    showPublishDialog.value = true;
};

const openPreview = async () => {
    if (isDirty.value) {
        const saved = await save();
        if (!saved) {
            showToast('error', 'Erro ao salvar antes do preview');
            return;
        }
    }
    showPreview.value = true;
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
const handleRestore = async (versionId) => {
    if (confirm('Tem certeza que deseja restaurar esta versão? O rascunho atual será substituído.')) {
        await restore(versionId);
        showVersionHistory.value = false;
    }
};

// Toggle version history panel
const toggleVersionHistory = async () => {
    showVersionHistory.value = !showVersionHistory.value;
    if (showVersionHistory.value && versions.value.length === 0) {
        await loadVersions();
    }
};

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
        save();
    }
    // Ctrl/Cmd + P to publish
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        handlePublish();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head title="Editor de Site" />

    <div class="h-screen flex flex-col bg-gray-100">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="/admin" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-lg font-semibold text-gray-900">Editor de Site</h1>
                
                <!-- Status indicators -->
                <div class="flex items-center space-x-2 text-sm">
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

            <div class="flex items-center space-x-3">
                <!-- Save Button -->
                <button
                    @click="save"
                    :disabled="!isDirty || isSaving"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Salvar
                </button>

                <!-- Version History Button -->
                <button
                    @click="toggleVersionHistory"
                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md flex items-center"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Histórico
                </button>

                <!-- Preview Button -->
                <button
                    @click="openPreview"
                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md flex items-center"
                    :class="{ 'bg-gray-100': showPreview }"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview
                </button>

                <!-- Publish Button -->
                <button
                    @click="handlePublish"
                    :disabled="isPublishing"
                    class="px-4 py-2 text-sm font-medium text-white bg-wedding-600 rounded-md hover:bg-wedding-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                >
                    <svg v-if="isPublishing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isPublishing ? 'Publicando...' : 'Publicar' }}
                </button>

                <!-- View Site Button (only if published) -->
                <a
                    v-if="site.is_published"
                    :href="`/site/${site.slug}`"
                    target="_blank"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 flex items-center"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Ver Site
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Sidebar -->
            <SectionSidebar
                :sections="sections"
                :active-section="activeSection"
                @select="selectSection"
                @toggle="toggleSection"
            />

            <!-- Editor Area -->
            <main class="flex-1 overflow-hidden p-6 flex flex-col">
                <div class="w-full flex-1 flex flex-col min-h-0">
                    <!-- Draft Watermark -->
                    <div v-if="site.is_draft" class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center text-amber-800 flex-shrink-0">
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
                        :enabled-sections="enabledSections"
                        @change="handleSectionUpdate"
                        class="flex-1 min-h-0 overflow-hidden"
                    />
                </div>
            </main>

            <!-- Version History Panel (Slide-over) -->
            <aside
                v-if="showVersionHistory"
                class="w-80 bg-white border-l border-gray-200 flex flex-col"
            >
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="font-medium text-gray-900">Histórico de Versões</h2>
                    <button @click="showVersionHistory = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="flex-1 overflow-auto">
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
                    
                    <ul v-else class="divide-y divide-gray-200">
                        <li
                            v-for="version in versions"
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
                                </div>
                                <button
                                    @click="handleRestore(version.id)"
                                    class="ml-2 px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                                    :class="version.is_published 
                                        ? 'text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100' 
                                        : 'text-wedding-600 bg-wedding-50 border border-wedding-200 hover:bg-wedding-100'"
                                >
                                    {{ version.is_published ? 'Rollback' : 'Restaurar' }}
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>

        <!-- Publish Dialog -->
        <PublishDialog
            :is-open="showPublishDialog"
            :site-id="site.id"
            :site-name="site.name || 'Site'"
            @close="showPublishDialog = false"
            @published="handlePublishSuccess"
            @error="handlePublishError"
        />

        <!-- Fullscreen Preview -->
        <FullscreenPreview
            :show="showPreview"
            :content="draftContent"
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
    background-color: #faf8f6;
}
.bg-wedding-100 {
    background-color: #f5ebe4;
}
.bg-wedding-600 {
    background-color: #a18072;
}
.bg-wedding-700 {
    background-color: #8b6b5d;
}
.text-wedding-600 {
    color: #a18072;
}
.text-wedding-700 {
    color: #8b6b5d;
}
.text-wedding-800 {
    color: #6b5347;
}
.border-wedding-200 {
    border-color: #e5d5c9;
}
.hover\:bg-wedding-100:hover {
    background-color: #f5ebe4;
}
</style>
