<script setup>
/**
 * GiftRegistryEditor Component
 * 
 * Editor for the Gift Registry section of the wedding site (mockup).
 * Simple fields for title, description, and background color.
 * Will be integrated with the Gift Catalog module in the future.
 * 
 * @Requirements: 11.1, 11.3, 11.4
 */
import { ref, watch, computed } from 'vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

// Watch for external content changes
watch(() => props.content, (newContent) => {
    localContent.value = JSON.parse(JSON.stringify(newContent));
}, { deep: true });

/**
 * Emit changes to parent
 */
const emitChange = () => {
    emit('change', JSON.parse(JSON.stringify(localContent.value)));
};

/**
 * Update a field and emit change
 */
const updateField = (field, value) => {
    localContent.value[field] = value;
    emitChange();
};

/**
 * Update style field
 */
const updateStyle = (field, value) => {
    if (!localContent.value.style) {
        localContent.value.style = {};
    }
    localContent.value.style[field] = value;
    emitChange();
};

// Computed properties
const style = computed(() => localContent.value.style || {});
</script>

<template>
    <div class="space-y-6">
        <!-- Integration Notice -->
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-amber-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-amber-800">Módulo em desenvolvimento</h4>
                    <p class="mt-1 text-sm text-amber-700">
                        Esta seção será integrada com o Catálogo de Presentes quando o módulo estiver disponível.
                        Por enquanto, você pode personalizar o texto e estilo da prévia.
                    </p>
                </div>
            </div>
        </div>

        <!-- Content Settings -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Conteúdo</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input
                    type="text"
                    :value="localContent.title"
                    @input="updateField('title', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Lista de Presentes"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea
                    :value="localContent.description"
                    @input="updateField('description', $event.target.value)"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Em breve você poderá ver nossa lista de presentes..."
                ></textarea>
            </div>
        </div>

        <!-- Style Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Fundo</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="style.backgroundColor || '#ffffff'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <input
                        type="text"
                        :value="style.backgroundColor || '#ffffff'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Prévia</h3>
            
            <div 
                class="p-6 rounded-lg text-center"
                :style="{ backgroundColor: style.backgroundColor || '#ffffff' }"
            >
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">{{ localContent.title || 'Lista de Presentes' }}</h4>
                <p class="mt-2 text-gray-600">{{ localContent.description || 'Em breve...' }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #b8998a;
}
.focus\:border-wedding-500:focus {
    border-color: #b8998a;
}
</style>
