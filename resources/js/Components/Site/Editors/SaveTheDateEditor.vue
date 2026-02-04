<script setup>
/**
 * SaveTheDateEditor Component
 * 
 * Editor for the Save the Date section of the wedding site.
 * Supports map display, countdown, calendar button, and location settings.
 * 
 * @Requirements: 10.1, 10.2, 10.5, 10.6
 */
import { ref, watch, computed } from 'vue';
import NavigationSettings from './NavigationSettings.vue';

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
 * Update map coordinates
 */
const updateCoordinates = (field, value) => {
    if (!localContent.value.mapCoordinates) {
        localContent.value.mapCoordinates = { lat: null, lng: null };
    }
    localContent.value.mapCoordinates[field] = value === '' ? null : parseFloat(value);
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

/**
 * Update navigation settings
 */
const updateNavigation = (navigation) => {
    localContent.value.navigation = navigation;
    emitChange();
};

// Computed properties
const mapCoordinates = computed(() => localContent.value.mapCoordinates || { lat: null, lng: null });
const style = computed(() => localContent.value.style || {});
</script>

<template>
    <div class="space-y-6">
        <!-- Navigation Settings -->
        <NavigationSettings
            :navigation="localContent.navigation"
            @change="updateNavigation"
        />

        <!-- Description -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Descrição</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Texto de Descrição</label>
                <textarea
                    :value="localContent.description"
                    @input="updateField('description', $event.target.value)"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Detalhes sobre o evento, local, horário..."
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">Use placeholders como {data}, {local}, {cidade}</p>
            </div>
        </div>

        <!-- Map Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Mapa</h3>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="localContent.showMap"
                    @change="updateField('showMap', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Exibir mapa do local</label>
            </div>

            <template v-if="localContent.showMap">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Provedor do Mapa</label>
                    <select
                        :value="localContent.mapProvider"
                        @change="updateField('mapProvider', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="google">Google Maps</option>
                        <option value="mapbox">Mapbox</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">A chave da API deve estar configurada nas configurações do sistema</p>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                    <span class="text-sm font-medium text-gray-700">Coordenadas do Local</span>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Latitude</label>
                            <input
                                type="number"
                                step="any"
                                :value="mapCoordinates.lat"
                                @input="updateCoordinates('lat', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="-23.5505"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Longitude</label>
                            <input
                                type="number"
                                step="any"
                                :value="mapCoordinates.lng"
                                @input="updateCoordinates('lng', $event.target.value)"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                                placeholder="-46.6333"
                            />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">
                        Dica: Busque o local no Google Maps, clique com botão direito e copie as coordenadas
                    </p>
                </div>
            </template>
        </div>

        <!-- Countdown Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Contador Regressivo</h3>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="localContent.showCountdown"
                    @change="updateField('showCountdown', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Exibir contador regressivo</label>
            </div>

            <template v-if="localContent.showCountdown">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Formato do Contador</label>
                    <select
                        :value="localContent.countdownFormat"
                        @change="updateField('countdownFormat', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="days">Apenas dias</option>
                        <option value="hours">Dias e horas</option>
                        <option value="minutes">Dias, horas e minutos</option>
                        <option value="full">Completo (dias, horas, minutos, segundos)</option>
                    </select>
                </div>
            </template>
        </div>

        <!-- Calendar Button -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Calendário</h3>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="localContent.showCalendarButton"
                    @change="updateField('showCalendarButton', $event.target.checked)"
                    class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                />
                <label class="ml-2 text-sm text-gray-700">Exibir botão "Adicionar ao calendário"</label>
            </div>
            <p class="text-xs text-gray-500">Gera arquivo .ics automaticamente com os dados do evento</p>
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Layout</label>
                <select
                    :value="style.layout"
                    @change="updateStyle('layout', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="inline">Inline</option>
                    <option value="card">Card</option>
                    <option value="modal">Modal</option>
                </select>
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
.text-wedding-600 {
    color: #a18072;
}
</style>
