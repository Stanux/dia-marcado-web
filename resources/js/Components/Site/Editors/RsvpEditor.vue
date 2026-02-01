<script setup>
/**
 * RsvpEditor Component
 * 
 * Editor for the RSVP section of the wedding site (mockup).
 * Simple fields for title, description, and background color.
 * Includes a preview of the mocked form fields.
 * Will be integrated with the Guest Confirmation module in the future.
 * 
 * @Requirements: 12.1, 12.3, 12.4
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
const mockFields = computed(() => localContent.value.mockFields || [
    { label: 'Nome', type: 'text' },
    { label: 'Email', type: 'email' },
    { label: 'Confirmação', type: 'select' },
    { label: 'Acompanhantes', type: 'number' },
]);
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
                        Esta seção será integrada com o módulo de Confirmação de Convidados quando estiver disponível.
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
                    placeholder="Confirme sua Presença"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea
                    :value="localContent.description"
                    @input="updateField('description', $event.target.value)"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Por favor, confirme sua presença até..."
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
                        :value="style.backgroundColor || '#f5f5f5'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <input
                        type="text"
                        :value="style.backgroundColor || '#f5f5f5'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Form Preview -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Prévia do Formulário</h3>
            
            <div 
                class="p-6 rounded-lg"
                :style="{ backgroundColor: style.backgroundColor || '#f5f5f5' }"
            >
                <div class="max-w-md mx-auto">
                    <h4 class="text-lg font-semibold text-gray-900 text-center mb-2">
                        {{ localContent.title || 'Confirme sua Presença' }}
                    </h4>
                    <p class="text-gray-600 text-center mb-6">
                        {{ localContent.description || 'Por favor, confirme sua presença.' }}
                    </p>

                    <!-- Mock Form Fields -->
                    <div class="space-y-4">
                        <div v-for="(field, index) in mockFields" :key="index">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ field.label }}</label>
                            
                            <input
                                v-if="field.type === 'text'"
                                type="text"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-not-allowed"
                                placeholder="Campo de texto"
                            />
                            
                            <input
                                v-else-if="field.type === 'email'"
                                type="email"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-not-allowed"
                                placeholder="email@exemplo.com"
                            />
                            
                            <select
                                v-else-if="field.type === 'select'"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-not-allowed"
                            >
                                <option>Selecione uma opção</option>
                            </select>
                            
                            <input
                                v-else-if="field.type === 'number'"
                                type="number"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white cursor-not-allowed"
                                placeholder="0"
                            />
                        </div>

                        <button
                            disabled
                            class="w-full px-4 py-2 bg-gray-400 text-white rounded-md cursor-not-allowed"
                        >
                            Confirmar (desabilitado)
                        </button>
                    </div>
                </div>
            </div>
            
            <p class="text-xs text-gray-500 text-center">
                Este é apenas um preview. O formulário funcional será habilitado quando o módulo de convidados estiver disponível.
            </p>
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
