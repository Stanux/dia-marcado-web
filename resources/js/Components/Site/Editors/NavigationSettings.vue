<script setup>
/**
 * NavigationSettings Component
 * 
 * Componente reutilizável para configurar as opções de navegação de uma seção.
 * Permite editar o rótulo e escolher se a seção aparece no menu.
 */
import { ref, watch } from 'vue';

const props = defineProps({
    navigation: {
        type: Object,
        default: () => ({
            label: '',
            showInMenu: true,
        }),
    },
});

const emit = defineEmits(['change']);

// Local copy of navigation settings
const localNavigation = ref({
    label: props.navigation?.label || '',
    showInMenu: props.navigation?.showInMenu ?? true,
});

// Watch for external changes
watch(() => props.navigation, (newNav) => {
    if (newNav) {
        localNavigation.value = {
            label: newNav.label || '',
            showInMenu: newNav.showInMenu ?? true,
        };
    }
}, { deep: true });

/**
 * Update field and emit change
 */
const updateField = (field, value) => {
    localNavigation.value[field] = value;
    emit('change', { ...localNavigation.value });
};
</script>

<template>
    <div class="space-y-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-center gap-2 text-blue-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h4 class="text-sm font-semibold uppercase tracking-wider">Configurações de Navegação</h4>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Rótulo no Menu
            </label>
            <input
                type="text"
                :value="localNavigation.label"
                @input="updateField('label', $event.target.value)"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                placeholder="Ex: Save the Date"
            />
            <p class="mt-1 text-xs text-gray-500">
                Este texto aparecerá no menu de navegação do site
            </p>
        </div>

        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input
                    type="checkbox"
                    :checked="localNavigation.showInMenu"
                    @change="updateField('showInMenu', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
            </div>
            <div class="ml-3">
                <label class="text-sm font-medium text-gray-700">
                    Exibir no menu de navegação
                </label>
                <p class="text-xs text-gray-500">
                    Quando ativado, esta seção aparecerá no menu principal do site
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #d87a8d;
}
.focus\:border-wedding-500:focus {
    border-color: #d87a8d;
}
.text-wedding-600 {
    color: #c45a6f;
}
</style>
