<script setup>
/**
 * SettingsPanel Component
 * 
 * Site settings configuration panel including slug, custom domain,
 * password protection, and storage usage.
 * 
 * @Requirements: 5.1, 5.4, 6.1, 16.8
 */
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    site: {
        type: Object,
        required: true,
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
    baseUrl: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'updated']);

// Form state
const slug = ref('');
const customDomain = ref('');
const accessToken = ref('');
const showPassword = ref(false);

// Status
const isSaving = ref(false);
const error = ref(null);
const successMessage = ref(null);

// Storage usage
const storageUsage = ref(null);
const isLoadingStorage = ref(false);

// Computed
const publicUrl = computed(() => {
    if (!slug.value) return '';
    const base = props.baseUrl || window.location.origin;
    return `${base}/s/${slug.value}`;
});

const storagePercentage = computed(() => {
    if (!storageUsage.value) return 0;
    return Math.min(100, Math.round((storageUsage.value.used / storageUsage.value.limit) * 100));
});

const storageColor = computed(() => {
    if (storagePercentage.value >= 90) return 'bg-red-500';
    if (storagePercentage.value >= 70) return 'bg-amber-500';
    return 'bg-green-500';
});

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Methods
const initializeForm = () => {
    slug.value = props.site.slug || '';
    customDomain.value = props.site.custom_domain || '';
    accessToken.value = ''; // Don't show existing password
};

const loadStorageUsage = async () => {
    isLoadingStorage.value = true;
    try {
        const response = await axios.get(`/api/sites/${props.site.id}/media/usage`);
        storageUsage.value = response.data.data;
    } catch (err) {
        console.error('Error loading storage usage:', err);
    } finally {
        isLoadingStorage.value = false;
    }
};

const saveSettings = async () => {
    if (isSaving.value) return;

    isSaving.value = true;
    error.value = null;
    successMessage.value = null;

    try {
        const payload = {
            slug: slug.value,
            custom_domain: customDomain.value || null,
        };

        // Only include access_token if it was changed
        if (accessToken.value) {
            payload.access_token = accessToken.value;
        }

        const response = await axios.put(`/api/sites/${props.site.id}/settings`, payload);
        
        successMessage.value = 'Configurações salvas com sucesso!';
        emit('updated', response.data.data);

        // Clear success message after 3 seconds
        setTimeout(() => {
            successMessage.value = null;
        }, 3000);
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            const errors = err.response.data.errors;
            error.value = Object.values(errors).flat().join(', ');
        } else {
            error.value = err.response?.data?.message || 'Erro ao salvar configurações';
        }
        console.error('Save settings error:', err);
    } finally {
        isSaving.value = false;
    }
};

const removePassword = async () => {
    if (isSaving.value) return;

    isSaving.value = true;
    error.value = null;

    try {
        const response = await axios.put(`/api/sites/${props.site.id}/settings`, {
            slug: slug.value,
            custom_domain: customDomain.value || null,
            access_token: null,
        });
        
        successMessage.value = 'Senha removida com sucesso!';
        accessToken.value = '';
        emit('updated', response.data.data);

        setTimeout(() => {
            successMessage.value = null;
        }, 3000);
    } catch (err) {
        error.value = err.response?.data?.message || 'Erro ao remover senha';
        console.error('Remove password error:', err);
    } finally {
        isSaving.value = false;
    }
};

const copyUrl = async () => {
    try {
        await navigator.clipboard.writeText(publicUrl.value);
        successMessage.value = 'URL copiada!';
        setTimeout(() => {
            successMessage.value = null;
        }, 2000);
    } catch (err) {
        console.error('Copy error:', err);
    }
};

const closePanel = () => {
    error.value = null;
    successMessage.value = null;
    emit('close');
};

// Watch for panel open
watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        initializeForm();
        loadStorageUsage();
    }
});

// Watch for site changes
watch(() => props.site, () => {
    if (props.isOpen) {
        initializeForm();
    }
}, { deep: true });

// Initialize on mount
onMounted(() => {
    if (props.isOpen) {
        initializeForm();
        loadStorageUsage();
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h2 class="font-semibold text-gray-900">Configurações</h2>
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
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-4 space-y-6">
            <!-- Success Message -->
            <div v-if="successMessage" class="rounded-md bg-green-50 p-3">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="ml-2 text-sm font-medium text-green-800">{{ successMessage }}</p>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="rounded-md bg-red-50 p-3">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="ml-2 text-sm font-medium text-red-800">{{ error }}</p>
                </div>
            </div>

            <!-- URL Section -->
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-3">Endereço do Site</h3>
                
                <!-- Slug Input -->
                <div class="mb-3">
                    <label for="slug" class="block text-sm text-gray-600 mb-1">
                        Slug (identificador na URL)
                    </label>
                    <div class="flex rounded-md shadow-sm">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            /s/
                        </span>
                        <input
                            id="slug"
                            v-model="slug"
                            type="text"
                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                            placeholder="seu-casamento"
                            pattern="[a-z0-9-]+"
                        />
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Apenas letras minúsculas, números e hífens
                    </p>
                </div>

                <!-- URL Preview -->
                <div v-if="publicUrl" class="bg-gray-50 rounded-md p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-500 mb-1">URL pública:</p>
                            <p class="text-sm text-gray-900 truncate">{{ publicUrl }}</p>
                        </div>
                        <button
                            @click="copyUrl"
                            class="ml-2 p-2 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-200"
                            title="Copiar URL"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Custom Domain Section -->
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-3">Domínio Personalizado</h3>
                <div>
                    <label for="customDomain" class="block text-sm text-gray-600 mb-1">
                        URL do domínio (opcional)
                    </label>
                    <input
                        id="customDomain"
                        v-model="customDomain"
                        type="url"
                        class="block w-full px-3 py-2 rounded-md border border-gray-300 focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                        placeholder="https://meucasamento.com.br"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Configure um domínio próprio para seu site
                    </p>
                </div>
            </div>

            <!-- Password Protection Section -->
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-3">Proteção por Senha</h3>
                
                <div class="mb-3">
                    <label for="accessToken" class="block text-sm text-gray-600 mb-1">
                        {{ site.access_token ? 'Nova senha (deixe vazio para manter)' : 'Senha de acesso' }}
                    </label>
                    <div class="relative">
                        <input
                            id="accessToken"
                            v-model="accessToken"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full px-3 py-2 pr-10 rounded-md border border-gray-300 focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                            placeholder="Digite uma senha"
                            minlength="4"
                            maxlength="50"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        >
                            <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Mínimo 4 caracteres. Deixe vazio para site público.
                    </p>
                </div>

                <!-- Current Password Status -->
                <div 
                    v-if="site.access_token"
                    class="flex items-center justify-between bg-amber-50 rounded-md p-3"
                >
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-amber-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span class="text-sm text-amber-800">Site protegido por senha</span>
                    </div>
                    <button
                        @click="removePassword"
                        :disabled="isSaving"
                        class="text-xs text-amber-600 hover:text-amber-700 underline"
                    >
                        Remover
                    </button>
                </div>
                <div 
                    v-else
                    class="flex items-center bg-green-50 rounded-md p-3"
                >
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-green-800">Site público (sem senha)</span>
                </div>
            </div>

            <!-- Storage Usage Section -->
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-3">Uso de Armazenamento</h3>
                
                <div v-if="isLoadingStorage" class="flex items-center justify-center py-4">
                    <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <div v-else-if="storageUsage" class="space-y-2">
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div 
                            class="h-2.5 rounded-full transition-all duration-300"
                            :class="storageColor"
                            :style="{ width: `${storagePercentage}%` }"
                        ></div>
                    </div>

                    <!-- Usage Details -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">
                            {{ formatBytes(storageUsage.used) }} usado
                        </span>
                        <span class="text-gray-500">
                            {{ formatBytes(storageUsage.limit) }} total
                        </span>
                    </div>

                    <!-- Warning if near limit -->
                    <div 
                        v-if="storagePercentage >= 90"
                        class="flex items-center text-xs text-red-600 mt-2"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Armazenamento quase cheio!
                    </div>
                </div>

                <div v-else class="text-sm text-gray-500">
                    Não foi possível carregar informações de armazenamento
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <button
                @click="saveSettings"
                :disabled="isSaving"
                class="w-full inline-flex justify-center items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-wedding-600 hover:bg-wedding-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
                <svg 
                    v-if="isSaving" 
                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" 
                    fill="none" 
                    viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isSaving ? 'Salvando...' : 'Salvar Configurações' }}
            </button>
        </div>
    </aside>
</template>

<style scoped>
.bg-wedding-600 {
    background-color: #c45a6f;
}
.bg-wedding-700 {
    background-color: #b9163a;
}
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #d87a8d;
}
.focus\:border-wedding-500:focus {
    border-color: #d87a8d;
}
</style>
