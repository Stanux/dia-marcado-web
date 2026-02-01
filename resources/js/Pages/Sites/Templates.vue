<script setup>
/**
 * Templates Page
 * 
 * Gallery page for browsing and selecting site templates.
 * Features filtering by type (all, public, private) and template preview.
 * 
 * @Requirements: 15.1, 15.2, 15.3
 */
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import TemplateCard from '@/Components/Site/TemplateCard.vue';

const props = defineProps({
    site: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// State
const templates = ref([]);
const isLoading = ref(true);
const error = ref(null);
const filter = ref('all'); // 'all', 'public', 'private'
const selectedTemplate = ref(null);
const previewTemplate = ref(null);
const showPreview = ref(false);
const isApplying = ref(false);

// Filter options
const filterOptions = [
    { value: 'all', label: 'Todos' },
    { value: 'public', label: 'Públicos' },
    { value: 'private', label: 'Meus Templates' },
];

// Filtered templates based on selected filter
const filteredTemplates = computed(() => {
    if (filter.value === 'all') {
        return templates.value;
    }
    if (filter.value === 'public') {
        return templates.value.filter(t => t.is_public || t.is_system);
    }
    if (filter.value === 'private') {
        return templates.value.filter(t => !t.is_public && !t.is_system);
    }
    return templates.value;
});

// Fetch templates from API
const fetchTemplates = async () => {
    isLoading.value = true;
    error.value = null;
    
    try {
        const response = await fetch('/api/sites/templates', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        if (!response.ok) {
            throw new Error('Erro ao carregar templates');
        }
        
        const data = await response.json();
        templates.value = data.data || [];
    } catch (err) {
        error.value = err.message;
        console.error('Error fetching templates:', err);
    } finally {
        isLoading.value = false;
    }
};

// Handle template preview
const handlePreview = (template) => {
    previewTemplate.value = template;
    showPreview.value = true;
};

// Close preview modal
const closePreview = () => {
    showPreview.value = false;
    previewTemplate.value = null;
};

// Handle template selection
const handleSelect = (template) => {
    selectedTemplate.value = template;
};

// Apply selected template to site
const applyTemplate = async (template) => {
    if (!props.site?.id) {
        alert('Nenhum site selecionado para aplicar o template.');
        return;
    }
    
    if (!confirm(`Deseja aplicar o template "${template.name}"? O conteúdo atual do rascunho será mesclado com o template.`)) {
        return;
    }
    
    isApplying.value = true;
    
    try {
        const response = await fetch(`/api/sites/${props.site.id}/apply-template/${template.id}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
        });
        
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Erro ao aplicar template');
        }
        
        // Redirect to editor after applying template
        router.visit(`/admin/sites/${props.site.id}/edit`);
    } catch (err) {
        alert(err.message);
        console.error('Error applying template:', err);
    } finally {
        isApplying.value = false;
    }
};

// Use template from preview modal
const useTemplateFromPreview = () => {
    if (previewTemplate.value) {
        applyTemplate(previewTemplate.value);
    }
};

// Initialize
onMounted(() => {
    fetchTemplates();
});
</script>

<template>
    <Head title="Templates de Site" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a 
                            v-if="site" 
                            :href="`/admin/sites/${site.id}/edit`" 
                            class="text-gray-500 hover:text-gray-700"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Templates</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                Escolha um template para começar ou personalizar seu site
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filter Tabs -->
            <div class="mb-6">
                <nav class="flex space-x-4" aria-label="Filtros">
                    <button
                        v-for="option in filterOptions"
                        :key="option.value"
                        @click="filter = option.value"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="[
                            filter === option.value
                                ? 'bg-wedding-100 text-wedding-700'
                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
                        ]"
                    >
                        {{ option.label }}
                    </button>
                </nav>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-wedding-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-3 text-gray-500">Carregando templates...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Erro ao carregar templates</h3>
                <p class="mt-1 text-sm text-gray-500">{{ error }}</p>
                <button
                    @click="fetchTemplates"
                    class="mt-4 px-4 py-2 text-sm font-medium text-wedding-600 hover:text-wedding-700"
                >
                    Tentar novamente
                </button>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredTemplates.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum template encontrado</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ filter === 'private' ? 'Você ainda não salvou nenhum template.' : 'Não há templates disponíveis nesta categoria.' }}
                </p>
            </div>

            <!-- Templates Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <TemplateCard
                    v-for="template in filteredTemplates"
                    :key="template.id"
                    :template="template"
                    :selected="selectedTemplate?.id === template.id"
                    @preview="handlePreview"
                    @select="applyTemplate"
                />
            </div>
        </main>

        <!-- Preview Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showPreview && previewTemplate"
                    class="fixed inset-0 z-50 overflow-y-auto"
                >
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/60" @click="closePreview"></div>
                    
                    <!-- Modal -->
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl overflow-hidden">
                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ previewTemplate.name }}
                                    </h3>
                                    <p v-if="previewTemplate.description" class="mt-1 text-sm text-gray-500">
                                        {{ previewTemplate.description }}
                                    </p>
                                </div>
                                <button
                                    @click="closePreview"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Preview Content -->
                            <div class="aspect-video bg-gray-100">
                                <img
                                    v-if="previewTemplate.thumbnail"
                                    :src="previewTemplate.thumbnail"
                                    :alt="previewTemplate.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                    <div class="text-center">
                                        <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2">Preview não disponível</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <button
                                    @click="closePreview"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    Fechar
                                </button>
                                <button
                                    @click="useTemplateFromPreview"
                                    :disabled="isApplying"
                                    class="px-4 py-2 text-sm font-medium text-white bg-wedding-600 rounded-md hover:bg-wedding-700 disabled:opacity-50 flex items-center"
                                >
                                    <svg v-if="isApplying" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isApplying ? 'Aplicando...' : 'Usar este template' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
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
    background-color: #8b6b5d;
}
.bg-wedding-700 {
    background-color: #6b5347;
}
.text-wedding-600 {
    color: #8b6b5d;
}
.text-wedding-700 {
    color: #6b5347;
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
