<script setup>
/**
 * VersionHistoryPanel Component
 * 
 * Displays version history with restore functionality.
 * Shows version date, user, summary, and published badge.
 * 
 * @Requirements: 4.1, 4.4
 */
import { ref, computed, watch, onMounted } from 'vue';
import useVersionHistory from '../../Composables/useVersionHistory.js';

const props = defineProps({
    siteId: {
        type: String,
        required: true,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'restored']);

// Use version history composable
const { 
    versions, 
    isLoading, 
    isRestoring, 
    error, 
    loadVersions, 
    restore,
    setSiteId,
} = useVersionHistory(props.siteId);

// Local state
const confirmingRestore = ref(null);

// Methods
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const formatRelativeDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Agora mesmo';
    if (diffMins < 60) return `${diffMins} min atrás`;
    if (diffHours < 24) return `${diffHours}h atrás`;
    if (diffDays < 7) return `${diffDays} dias atrás`;
    return formatDate(dateString);
};

const confirmRestore = (version) => {
    confirmingRestore.value = version.id;
};

const cancelRestore = () => {
    confirmingRestore.value = null;
};

const executeRestore = async (versionId) => {
    const result = await restore(versionId);
    if (result) {
        confirmingRestore.value = null;
        emit('restored', result);
    }
};

const closePanel = () => {
    confirmingRestore.value = null;
    emit('close');
};

// Watch for panel open
watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        loadVersions();
    }
});

// Watch for site ID changes
watch(() => props.siteId, (newSiteId) => {
    setSiteId(newSiteId);
    if (props.isOpen) {
        loadVersions();
    }
});

// Load versions on mount if panel is open
onMounted(() => {
    if (props.isOpen) {
        loadVersions();
    }
});
</script>

<template>
    <aside
        v-if="isOpen"
        class="w-full lg:w-96 bg-white border-l border-gray-200 flex flex-col h-full"
    >
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="font-semibold text-gray-900">Histórico de Versões</h2>
                </div>
                <button 
                    @click="closePanel" 
                    class="text-gray-400 hover:text-gray-600 p-1 rounded-md hover:bg-gray-100"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Restaure versões anteriores do seu site
            </p>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Loading State -->
            <div v-if="isLoading" class="flex flex-col items-center justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-wedding-600 mb-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm text-gray-500">Carregando versões...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="p-4">
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ error }}</p>
                            <button 
                                @click="loadVersions"
                                class="mt-2 text-sm text-red-600 hover:text-red-700 underline"
                            >
                                Tentar novamente
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="versions.length === 0" class="flex flex-col items-center justify-center py-12 px-4">
                <svg class="h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-gray-500 text-center">
                    Nenhuma versão salva ainda.<br>
                    As versões são criadas automaticamente ao salvar.
                </p>
            </div>

            <!-- Versions List -->
            <div v-else class="divide-y divide-gray-100">
                <div
                    v-for="version in versions"
                    :key="version.id"
                    class="p-4 hover:bg-gray-50 transition-colors"
                >
                    <!-- Version Header -->
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-900">
                                {{ formatRelativeDate(version.created_at) }}
                            </span>
                            <span 
                                v-if="version.is_published"
                                class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                            >
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Publicada
                            </span>
                        </div>
                    </div>

                    <!-- Version Details -->
                    <div class="mb-3">
                        <p class="text-sm text-gray-600">
                            {{ version.summary || 'Alteração salva' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ formatDate(version.created_at) }}
                            <span v-if="version.user">
                                • {{ version.user.name }}
                            </span>
                        </p>
                    </div>

                    <!-- Restore Actions -->
                    <div v-if="confirmingRestore === version.id" class="mt-3">
                        <div class="bg-amber-50 border border-amber-200 rounded-md p-3 mb-2">
                            <p class="text-sm text-amber-800">
                                Tem certeza que deseja restaurar esta versão? 
                                O conteúdo atual será substituído.
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="executeRestore(version.id)"
                                :disabled="isRestoring"
                                class="flex-1 inline-flex justify-center items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-wedding-600 hover:bg-wedding-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                            >
                                <svg 
                                    v-if="isRestoring" 
                                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" 
                                    fill="none" 
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isRestoring ? 'Restaurando...' : 'Confirmar' }}
                            </button>
                            <button
                                @click="cancelRestore"
                                :disabled="isRestoring"
                                class="flex-1 inline-flex justify-center items-center px-3 py-2 text-sm font-medium rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed transition-colors"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                    <div v-else>
                        <button
                            @click="confirmRestore(version)"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-wedding-600 bg-wedding-50 hover:bg-wedding-100 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Restaurar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <p class="text-xs text-gray-500 text-center">
                Até 30 versões são mantidas automaticamente
            </p>
        </div>
    </aside>
</template>

<style scoped>
.bg-wedding-50 {
    background-color: #faf7f5;
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
</style>
