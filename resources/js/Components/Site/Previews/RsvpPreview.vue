<script setup>
/**
 * RsvpPreview Component
 * 
 * Renders the RSVP section preview (mockup).
 * Shows placeholder form until the Guest Confirmation module is implemented.
 * 
 * @Requirements: 12.1, 12.2, 12.3, 12.4
 */
import { computed } from 'vue';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    theme: {
        type: Object,
        default: () => ({}),
    },
    isEditMode: {
        type: Boolean,
        default: false,
    },
    viewport: {
        type: String,
        default: 'desktop',
    },
});

// Computed properties
const style = computed(() => props.content.style || {});

// Mock form fields
const mockFields = computed(() => props.content.mockFields || [
    { label: 'Nome Completo', type: 'text', placeholder: 'Seu nome' },
    { label: 'Email', type: 'email', placeholder: 'seu@email.com' },
    { label: 'Confirmação', type: 'select', options: ['Confirmo presença', 'Não poderei comparecer'] },
    { label: 'Número de Acompanhantes', type: 'number', placeholder: '0' },
]);
</script>

<template>
    <section 
        class="py-16 px-4 relative"
        :style="{ backgroundColor: style.backgroundColor || '#f5f5f5' }"
        id="rsvp"
    >
        <div class="max-w-xl mx-auto">
            <!-- Section Header -->
            <div class="text-center mb-8">
                <h2 
                    class="text-2xl md:text-3xl font-bold mb-4"
                    :style="{ color: theme.primaryColor, fontFamily: theme.fontFamily }"
                >
                    {{ content.title || 'Confirme sua Presença' }}
                </h2>
                <p class="text-gray-600">
                    {{ content.description || 'Por favor, confirme sua presença para que possamos preparar tudo com carinho.' }}
                </p>
            </div>

            <!-- Mock Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <form class="space-y-5" @submit.prevent>
                    <div 
                        v-for="(field, index) in mockFields"
                        :key="index"
                    >
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ field.label }}
                        </label>
                        
                        <!-- Text Input -->
                        <input
                            v-if="field.type === 'text'"
                            type="text"
                            disabled
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-md bg-gray-50 cursor-not-allowed"
                            :placeholder="field.placeholder || ''"
                        />
                        
                        <!-- Email Input -->
                        <input
                            v-else-if="field.type === 'email'"
                            type="email"
                            disabled
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-md bg-gray-50 cursor-not-allowed"
                            :placeholder="field.placeholder || ''"
                        />
                        
                        <!-- Select -->
                        <select
                            v-else-if="field.type === 'select'"
                            disabled
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-md bg-gray-50 cursor-not-allowed"
                        >
                            <option value="">Selecione uma opção</option>
                            <option v-for="opt in (field.options || [])" :key="opt" :value="opt">
                                {{ opt }}
                            </option>
                        </select>
                        
                        <!-- Number Input -->
                        <input
                            v-else-if="field.type === 'number'"
                            type="number"
                            disabled
                            min="0"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-md bg-gray-50 cursor-not-allowed"
                            :placeholder="field.placeholder || '0'"
                        />
                    </div>

                    <!-- Message Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mensagem (opcional)
                        </label>
                        <textarea
                            disabled
                            rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-md bg-gray-50 cursor-not-allowed resize-none"
                            placeholder="Deixe uma mensagem para os noivos..."
                        ></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="button"
                        disabled
                        class="w-full px-6 py-3 text-white font-medium rounded-md cursor-not-allowed opacity-60"
                        :style="{ backgroundColor: theme.primaryColor }"
                    >
                        Confirmar Presença
                    </button>
                </form>

                <!-- Coming Soon Notice -->
                <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-amber-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm text-amber-800 font-medium">
                                Formulário em desenvolvimento
                            </p>
                            <p class="text-sm text-amber-700 mt-1">
                                Este é apenas um preview. O formulário funcional será habilitado quando o módulo de convidados estiver disponível.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Mode Indicator -->
        <div 
            v-if="isEditMode"
            class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-0.5 rounded z-10"
        >
            RSVP
        </div>
    </section>
</template>

<style scoped>
/* Form styling */
input:disabled,
select:disabled,
textarea:disabled {
    color: #9ca3af;
}
</style>
