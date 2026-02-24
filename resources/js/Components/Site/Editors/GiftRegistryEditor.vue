<script setup>
/**
 * GiftRegistryEditor Component
 * 
 * Editor for the Gift Registry section of the wedding site.
 * Includes configuration for typography, fees, and section settings.
 * Configurations are saved as part of the site draft content.
 * 
 * @Requirements: 3.1, 3.2, 6.1, 6.2, 6.7, 6.8, 11.1, 11.3, 11.4
 */
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import TypographyControl from '@/Components/Site/TypographyControl.vue';
import { useColorField } from '@/Composables/useColorField';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);
const { isEyeDropperSupported, normalizeHexColor, pickColorFromScreen } = useColorField();

const page = usePage();
const wedding = computed(() => page.props.wedding);
const giftRegistryConfig = computed(() => wedding.value?.gift_registry_config);

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

// Initialize config from content or from database
if (!localContent.value.config) {
    localContent.value.config = {
        section_title: giftRegistryConfig.value?.section_title || 'Lista de Presentes',
        fee_modality: giftRegistryConfig.value?.fee_modality || 'couple_pays',
    };
}

// Initialize typography from config or defaults
if (!localContent.value.titleTypography) {
    localContent.value.titleTypography = {
        fontFamily: giftRegistryConfig.value?.title_font_family || 'Playfair Display',
        fontColor: giftRegistryConfig.value?.title_color || '#333333',
        fontSize: giftRegistryConfig.value?.title_font_size || 48,
        fontWeight: giftRegistryConfig.value?.title_style === 'bold' || giftRegistryConfig.value?.title_style === 'bold_italic' ? 700 : 400,
        fontItalic: giftRegistryConfig.value?.title_style === 'italic' || giftRegistryConfig.value?.title_style === 'bold_italic',
        fontUnderline: false,
    };
}

// Watch for external content changes
watch(() => props.content, (newContent) => {
    localContent.value = JSON.parse(JSON.stringify(newContent));
    
    // Ensure config exists
    if (!localContent.value.config && giftRegistryConfig.value) {
        localContent.value.config = {
            section_title: giftRegistryConfig.value.section_title || 'Lista de Presentes',
            fee_modality: giftRegistryConfig.value.fee_modality || 'couple_pays',
        };
    }
    
    // Ensure typography exists
    if (!localContent.value.titleTypography && giftRegistryConfig.value) {
        localContent.value.titleTypography = {
            fontFamily: giftRegistryConfig.value.title_font_family || 'Playfair Display',
            fontColor: giftRegistryConfig.value.title_color || '#333333',
            fontSize: giftRegistryConfig.value.title_font_size || 48,
            fontWeight: giftRegistryConfig.value.title_style === 'bold' || giftRegistryConfig.value.title_style === 'bold_italic' ? 700 : 400,
            fontItalic: giftRegistryConfig.value.title_style === 'italic' || giftRegistryConfig.value.title_style === 'bold_italic',
            fontUnderline: false,
        };
    }
}, { deep: true });

/**
 * Emit changes to parent (includes config in the content)
 */
const emitChange = () => {
    // Sync typography to config before emitting
    if (localContent.value.titleTypography) {
        if (!localContent.value.config) {
            localContent.value.config = {};
        }
        
        localContent.value.config.title_font_family = localContent.value.titleTypography.fontFamily;
        localContent.value.config.title_color = localContent.value.titleTypography.fontColor;
        localContent.value.config.title_font_size = localContent.value.titleTypography.fontSize;
        
        // Convert typography to style
        const isBold = localContent.value.titleTypography.fontWeight >= 700;
        const isItalic = localContent.value.titleTypography.fontItalic;
        
        if (isBold && isItalic) {
            localContent.value.config.title_style = 'bold_italic';
        } else if (isBold) {
            localContent.value.config.title_style = 'bold';
        } else if (isItalic) {
            localContent.value.config.title_style = 'italic';
        } else {
            localContent.value.config.title_style = 'normal';
        }
    }
    
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

/**
 * Update config field
 */
const updateConfig = (field, value) => {
    if (!localContent.value.config) {
        localContent.value.config = {};
    }
    localContent.value.config[field] = value;
    emitChange();
};

/**
 * Update title typography
 */
const updateTitleTypography = (field, value) => {
    if (!localContent.value.titleTypography) {
        localContent.value.titleTypography = {
            fontFamily: 'Playfair Display',
            fontColor: '#333333',
            fontSize: 48,
            fontWeight: 400,
            fontItalic: false,
            fontUnderline: false,
        };
    }
    
    localContent.value.titleTypography[field] = value;
    emitChange();
};

/**
 * Calculate fee example
 */
const feeExample = computed(() => {
    const feePercentage = 0.05; // 5%
    const examplePrice = 10000; // R$ 100,00 in cents
    
    const feeModality = localContent.value.config?.fee_modality || 'couple_pays';
    let displayPrice, feeAmount, netAmountCouple;
    
    if (feeModality === 'couple_pays') {
        displayPrice = examplePrice;
        feeAmount = Math.round(examplePrice * feePercentage);
        netAmountCouple = examplePrice - feeAmount;
    } else {
        feeAmount = Math.round(examplePrice * feePercentage);
        displayPrice = examplePrice + feeAmount;
        netAmountCouple = examplePrice;
    }
    
    return {
        displayPrice: (displayPrice / 100).toFixed(2).replace('.', ','),
        feeAmount: (feeAmount / 100).toFixed(2).replace('.', ','),
        netAmountCouple: (netAmountCouple / 100).toFixed(2).replace('.', ','),
    };
});

// Computed properties
const style = computed(() => localContent.value.style || {});
const config = computed(() => localContent.value.config || {});
const giftRegistryBackgroundColorHex = computed(() => normalizeHexColor(style.value.backgroundColor, '#ffffff'));
const titleTypography = computed(() => localContent.value.titleTypography || {
    fontFamily: 'Playfair Display',
    fontColor: '#333333',
    fontSize: 48,
    fontWeight: 400,
    fontItalic: false,
    fontUnderline: false,
});

const pickGiftRegistryBackgroundColor = () => {
    pickColorFromScreen((hex) => updateStyle('backgroundColor', hex));
};
</script>

<template>
    <div class="space-y-6 h-full overflow-y-auto">
        <!-- Typography Settings -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Personalização do Título</h3>
            <p class="text-sm text-gray-600">Configure a aparência do título da seção de lista de presentes</p>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título da Seção</label>
                <input
                    type="text"
                    :value="config.section_title"
                    @input="updateConfig('section_title', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Lista de Presentes"
                />
                <p class="mt-1 text-xs text-gray-500">Título exibido na seção de presentes</p>
            </div>

            <!-- Tipografia do Título -->
            <TypographyControl
                :font-family="titleTypography.fontFamily"
                :font-color="titleTypography.fontColor"
                :font-size="titleTypography.fontSize"
                :font-weight="titleTypography.fontWeight"
                :font-italic="titleTypography.fontItalic"
                :font-underline="titleTypography.fontUnderline"
                :preview-background-color="giftRegistryBackgroundColorHex"
                @update:font-family="updateTitleTypography('fontFamily', $event)"
                @update:font-color="updateTitleTypography('fontColor', $event)"
                @update:font-size="updateTitleTypography('fontSize', $event)"
                @update:font-weight="updateTitleTypography('fontWeight', $event)"
                @update:font-italic="updateTitleTypography('fontItalic', $event)"
                @update:font-underline="updateTitleTypography('fontUnderline', $event)"
                label="Tipografia do Título"
            />
        </div>

        <!-- Style Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Fundo</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="giftRegistryBackgroundColorHex"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        @change="updateStyle('backgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        @click="pickGiftRegistryBackgroundColor"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="style.backgroundColor || '#ffffff'"
                        @input="updateStyle('backgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Fee Configuration -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Configuração de Taxas</h3>
            <p class="text-sm text-gray-600">Defina quem paga a taxa da plataforma</p>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Modalidade de Taxa</label>
                <select
                    :value="config.fee_modality"
                    @change="updateConfig('fee_modality', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="couple_pays">Noivos Pagam a Taxa</option>
                    <option value="guest_pays">Convidados Pagam a Taxa</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Escolha quem absorve o custo da taxa da plataforma</p>
            </div>

            <!-- Fee Example -->
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <p class="text-sm font-medium text-gray-900 mb-2">Exemplo de Cálculo</p>
                <div class="text-sm space-y-1 text-gray-700">
                    <p><strong>Exemplo:</strong> Presente de R$ 100,00</p>
                    <p>• Convidado paga: <strong>R$ {{ feeExample.displayPrice }}</strong></p>
                    <p>• Taxa da plataforma (5%): <strong>R$ {{ feeExample.feeAmount }}</strong></p>
                    <p>• Vocês recebem: <strong>R$ {{ feeExample.netAmountCouple }}</strong></p>
                </div>
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
.bg-wedding-600 {
    background-color: #a18072;
}
.bg-wedding-700 {
    background-color: #8b6b5d;
}
.text-wedding-600 {
    color: #a18072;
}
</style>
