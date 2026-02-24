<script setup>
/**
 * PublishDialog Component
 * 
 * Modal dialog for publishing a site with QA checklist validation.
 * Shows errors by section and allows override with warning.
 * 
 * @Requirements: 17.1, 17.2, 17.6
 */
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import QAPanel from './QAPanel.vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    siteId: {
        type: String,
        required: true,
    },
    siteName: {
        type: String,
        default: 'Site',
    },
});

const emit = defineEmits(['close', 'published', 'error', 'navigate-to-section']);

// State
const isLoading = ref(false);
const isPublishing = ref(false);
const qaResult = ref(null);
const error = ref(null);
const showOverrideWarning = ref(false);

// Computed
const canPublish = computed(() => {
    if (!qaResult.value) return false;
    return qaResult.value.can_publish || showOverrideWarning.value;
});

const hasErrors = computed(() => {
    if (!qaResult.value) return false;
    return qaResult.value.checks?.some(check => check.status === 'fail') || false;
});

const hasWarnings = computed(() => {
    if (!qaResult.value) return false;
    return qaResult.value.checks?.some(check => check.status === 'warning') || false;
});

const failedChecks = computed(() => {
    if (!qaResult.value?.checks) return [];
    return qaResult.value.checks.filter(check => check.status === 'fail');
});

const warningChecks = computed(() => {
    if (!qaResult.value?.checks) return [];
    return qaResult.value.checks.filter(check => check.status === 'warning');
});

const passedChecks = computed(() => {
    if (!qaResult.value?.checks) return [];
    return qaResult.value.checks.filter(check => check.status === 'pass');
});

// Methods
const runQAChecklist = async () => {
    isLoading.value = true;
    error.value = null;
    qaResult.value = null;
    showOverrideWarning.value = false;

    try {
        const response = await axios.get(`/admin/sites/${props.siteId}/qa`);
        qaResult.value = response.data.data;
    } catch (err) {
        error.value = err.response?.data?.message || 'Erro ao executar checklist de qualidade';
        console.error('QA checklist error:', err);
    } finally {
        isLoading.value = false;
    }
};

const publish = async () => {
    if (!canPublish.value || isPublishing.value) return;

    isPublishing.value = true;
    error.value = null;

    try {
        const response = await axios.post(`/admin/sites/${props.siteId}/publish`, {
            override: showOverrideWarning.value,
        });
        
        emit('published', response.data.data);
        closeDialog();
    } catch (err) {
        error.value = err.response?.data?.message || 'Erro ao publicar site';
        
        // Handle validation errors
        if (err.response?.status === 422 && err.response?.data?.errors) {
            error.value = {
                message: 'Erros de validação',
                errors: err.response.data.errors,
            };
        }
        
        emit('error', error.value);
        console.error('Publish error:', err);
    } finally {
        isPublishing.value = false;
    }
};

const enableOverride = () => {
    showOverrideWarning.value = true;
};

const navigateToSection = (section) => {
    if (!section) {
        return;
    }

    emit('navigate-to-section', section);
    closeDialog();
};

const closeDialog = () => {
    qaResult.value = null;
    error.value = null;
    showOverrideWarning.value = false;
    emit('close');
};

// Run QA when dialog opens
watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        runQAChecklist();
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="publish-dialog-title"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div 
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="closeDialog"
            ></div>

            <!-- Dialog -->
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <!-- Header -->
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-wedding-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-wedding-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                </div>
                                <div class="ml-4 text-left">
                                    <h3 id="publish-dialog-title" class="text-lg font-semibold leading-6 text-gray-900">
                                        Publicar Site
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ siteName }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="closeDialog"
                                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-wedding-500 focus:ring-offset-2"
                            >
                                <span class="sr-only">Fechar</span>
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-4 sm:px-6 pb-4">
                        <!-- Loading State -->
                        <div v-if="isLoading" class="flex flex-col items-center justify-center py-8">
                            <svg class="animate-spin h-8 w-8 text-wedding-600 mb-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-sm text-gray-500">Verificando qualidade do site...</p>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="error && typeof error === 'string'" class="rounded-md bg-red-50 p-4 mb-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">{{ error }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- QA Results -->
                        <div v-else-if="qaResult">
                            <!-- Summary -->
                            <div class="mb-4">
                                <div 
                                    class="rounded-lg p-4"
                                    :class="qaResult.passed ? 'bg-green-50' : 'bg-amber-50'"
                                >
                                    <div class="flex items-center">
                                        <svg 
                                            v-if="qaResult.passed" 
                                            class="h-5 w-5 text-green-500" 
                                            fill="none" 
                                            stroke="currentColor" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg 
                                            v-else 
                                            class="h-5 w-5 text-amber-500" 
                                            fill="none" 
                                            stroke="currentColor" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span 
                                            class="ml-2 text-sm font-medium"
                                            :class="qaResult.passed ? 'text-green-800' : 'text-amber-800'"
                                        >
                                            {{ qaResult.passed 
                                                ? 'Site pronto para publicação!' 
                                                : 'Alguns itens precisam de atenção' 
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- QA Panel -->
                            <QAPanel 
                                :checks="qaResult.checks || []"
                                :show-all="true"
                                @navigate-to-section="navigateToSection"
                            />

                            <!-- Override Warning -->
                            <div v-if="hasErrors && !showOverrideWarning" class="mt-4">
                                <button
                                    @click="enableOverride"
                                    class="text-sm text-amber-600 hover:text-amber-700 underline"
                                >
                                    Publicar mesmo assim (não recomendado)
                                </button>
                            </div>

                            <div v-if="showOverrideWarning" class="mt-4 rounded-md bg-red-50 p-4">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Atenção!</h3>
                                        <p class="mt-1 text-sm text-red-700">
                                            Você está prestes a publicar um site com erros. 
                                            Isso pode afetar a experiência dos visitantes.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            @click="publish"
                            :disabled="!canPublish || isPublishing"
                            class="inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto transition-colors"
                            :class="canPublish && !isPublishing
                                ? 'bg-wedding-600 hover:bg-wedding-700'
                                : 'bg-gray-300 cursor-not-allowed'"
                        >
                            <svg 
                                v-if="isPublishing" 
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" 
                                fill="none" 
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isPublishing ? 'Publicando...' : 'Publicar' }}
                        </button>
                        <button
                            @click="closeDialog"
                            :disabled="isPublishing"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
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
.ring-wedding-500 {
    --tw-ring-color: #b8998a;
}
</style>
