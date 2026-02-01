<script setup>
/**
 * PublicRsvp Component
 * 
 * Renders the RSVP section (mockup).
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
        class="py-20 px-4"
        :style="{ backgroundColor: style.backgroundColor || '#f8f6f4' }"
        id="rsvp"
    >
        <div class="max-w-xl mx-auto">
            <!-- Section Header -->
            <div class="text-center mb-10">
                <h2 
                    class="text-3xl md:text-4xl font-bold mb-4"
                    :style="{ color: theme.primaryColor, fontFamily: theme.fontFamily }"
                >
                    {{ content.title || 'Confirme sua Presença' }}
                </h2>
                <p class="text-gray-600 text-lg">
                    {{ content.description || 'Por favor, confirme sua presença para que possamos preparar tudo com carinho.' }}
                </p>
            </div>

            <!-- Mock Form -->
            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-10">
                <form class="space-y-6" @submit.prevent>
                    <div 
                        v-for="(field, index) in mockFields"
                        :key="index"
                    >
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ field.label }}
                        </label>
                        
                        <!-- Text Input -->
                        <input
                            v-if="field.type === 'text'"
                            type="text"
                            disabled
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed text-gray-400"
                            :placeholder="field.placeholder || ''"
                        />
                        
                        <!-- Email Input -->
                        <input
                            v-else-if="field.type === 'email'"
                            type="email"
                            disabled
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed text-gray-400"
                            :placeholder="field.placeholder || ''"
                        />
                        
                        <!-- Select -->
                        <select
                            v-else-if="field.type === 'select'"
                            disabled
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed text-gray-400"
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
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed text-gray-400"
                            :placeholder="field.placeholder || '0'"
                        />
                    </div>

                    <!-- Message Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mensagem (opcional)
                        </label>
                        <textarea
                            disabled
                            rows="4"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed resize-none text-gray-400"
                            placeholder="Deixe uma mensagem para os noivos..."
                        ></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="button"
                        disabled
                        class="w-full px-6 py-4 text-white font-semibold rounded-lg cursor-not-allowed opacity-50"
                        :style="{ backgroundColor: theme.primaryColor }"
                    >
                        Confirmar Presença
                    </button>
                </form>

                <!-- Coming Soon Notice -->
                <div class="mt-8 p-5 bg-amber-50 border border-amber-200 rounded-xl">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-amber-500 mr-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm text-amber-800 font-semibold">
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
    </section>
</template>

<style scoped>
/* Form styling */
</style>
