<script setup>
/**
 * SaveTemplateModal Component
 * 
 * Modal dialog for saving the current site layout as a private template.
 * Allows user to specify name and description for the template.
 * 
 * @Requirements: 15.4
 */
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    saving: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'save']);

// Form state
const name = ref('');
const description = ref('');
const errors = ref({});

// Reset form when modal opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        name.value = '';
        description.value = '';
        errors.value = {};
    }
});

// Validate form
const validate = () => {
    errors.value = {};
    
    if (!name.value.trim()) {
        errors.value.name = 'O nome do template é obrigatório.';
    } else if (name.value.length > 100) {
        errors.value.name = 'O nome deve ter no máximo 100 caracteres.';
    }
    
    if (description.value.length > 500) {
        errors.value.description = 'A descrição deve ter no máximo 500 caracteres.';
    }
    
    return Object.keys(errors.value).length === 0;
};

// Handle form submission
const handleSubmit = () => {
    if (!validate()) return;
    
    emit('save', {
        name: name.value.trim(),
        description: description.value.trim() || null,
    });
};

// Handle close
const handleClose = () => {
    if (!props.saving) {
        emit('close');
    }
};

// Handle backdrop click
const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        handleClose();
    }
};

// Handle escape key
const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        handleClose();
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto"
                @keydown="handleKeydown"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50 transition-opacity"
                    @click="handleBackdropClick"
                >
                    <!-- Modal Panel -->
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div
                            class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white shadow-xl transition-all"
                            @click.stop
                        >
                            <!-- Header -->
                            <div class="border-b border-gray-200 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        Salvar como Template
                                    </h3>
                                    <button
                                        @click="handleClose"
                                        :disabled="saving"
                                        class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    Salve o layout atual como um template privado para reutilização.
                                </p>
                            </div>

                            <!-- Form -->
                            <form @submit.prevent="handleSubmit" class="px-6 py-4 space-y-4">
                                <!-- Name Field -->
                                <div>
                                    <label for="template-name" class="block text-sm font-medium text-gray-700">
                                        Nome do Template <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="template-name"
                                        v-model="name"
                                        type="text"
                                        maxlength="100"
                                        :disabled="saving"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-wedding-500 focus:ring-wedding-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        :class="{ 'border-red-300': errors.name }"
                                        placeholder="Ex: Meu Template Elegante"
                                    />
                                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">
                                        {{ errors.name }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ name.length }}/100 caracteres
                                    </p>
                                </div>

                                <!-- Description Field -->
                                <div>
                                    <label for="template-description" class="block text-sm font-medium text-gray-700">
                                        Descrição
                                    </label>
                                    <textarea
                                        id="template-description"
                                        v-model="description"
                                        rows="3"
                                        maxlength="500"
                                        :disabled="saving"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-wedding-500 focus:ring-wedding-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        :class="{ 'border-red-300': errors.description }"
                                        placeholder="Descreva brevemente este template..."
                                    ></textarea>
                                    <p v-if="errors.description" class="mt-1 text-sm text-red-600">
                                        {{ errors.description }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ description.length }}/500 caracteres
                                    </p>
                                </div>

                                <!-- Info Box -->
                                <div class="rounded-md bg-blue-50 p-3">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="ml-2 text-sm text-blue-700">
                                            Templates privados ficam disponíveis apenas para este casamento.
                                        </p>
                                    </div>
                                </div>
                            </form>

                            <!-- Footer -->
                            <div class="border-t border-gray-200 px-6 py-4 flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="handleClose"
                                    :disabled="saving"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    @click="handleSubmit"
                                    :disabled="saving || !name.trim()"
                                    class="px-4 py-2 text-sm font-medium text-white bg-wedding-600 rounded-md hover:bg-wedding-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                >
                                    <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ saving ? 'Salvando...' : 'Salvar Template' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.bg-wedding-600 {
    background-color: #b9163a;
}
.bg-wedding-700 {
    background-color: #4A2F39;
}
.focus\:border-wedding-500:focus {
    border-color: #c45a6f;
}
.focus\:ring-wedding-500:focus {
    --tw-ring-color: rgba(216, 122, 141, 0.5);
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

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
}
</style>
