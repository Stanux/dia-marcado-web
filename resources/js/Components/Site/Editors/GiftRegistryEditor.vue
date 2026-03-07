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

const DEFAULT_SECTION_TITLE_TYPOGRAPHY = {
    fontFamily: 'Playfair Display',
    fontColor: '#333333',
    fontSize: 48,
    fontWeight: 400,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_CARD_TITLE_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#1f2937',
    fontSize: 18,
    fontWeight: 600,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_CARD_DESCRIPTION_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#6b7280',
    fontSize: 14,
    fontWeight: 400,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_CARD_PRICE_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#059669',
    fontSize: 24,
    fontWeight: 700,
    fontItalic: false,
    fontUnderline: false,
};

const DEFAULT_BUTTON_TYPOGRAPHY = {
    fontFamily: 'Montserrat',
    fontColor: '#ffffff',
    fontSize: 14,
    fontWeight: 600,
    fontItalic: false,
    fontUnderline: false,
};

// Local copy of content for editing (deep clone to avoid reference issues)
const localContent = ref(JSON.parse(JSON.stringify(props.content)));

const mergeTypography = (currentValue, defaults) => ({
    ...defaults,
    ...(currentValue && typeof currentValue === 'object' ? currentValue : {}),
});

const ensureStructure = () => {
    if (!localContent.value.config) {
        localContent.value.config = {};
    }

    localContent.value.config = {
        section_title: giftRegistryConfig.value?.section_title || 'Lista de Presentes',
        registry_mode: giftRegistryConfig.value?.registry_mode || 'quantity',
        fee_modality: giftRegistryConfig.value?.fee_modality || 'couple_pays',
        ...localContent.value.config,
    };

    if (!localContent.value.style || typeof localContent.value.style !== 'object') {
        localContent.value.style = {};
    }

    localContent.value.style = {
        backgroundColor: '#ffffff',
        cardBackgroundColor: '#ffffff',
        cardBorderColor: '#e5e7eb',
        buttonBackgroundColor: '#3b82f6',
        ...localContent.value.style,
    };

    localContent.value.titleTypography = mergeTypography(
        localContent.value.titleTypography || {
            fontFamily: giftRegistryConfig.value?.title_font_family || DEFAULT_SECTION_TITLE_TYPOGRAPHY.fontFamily,
            fontColor: giftRegistryConfig.value?.title_color || DEFAULT_SECTION_TITLE_TYPOGRAPHY.fontColor,
            fontSize: giftRegistryConfig.value?.title_font_size || DEFAULT_SECTION_TITLE_TYPOGRAPHY.fontSize,
            fontWeight: giftRegistryConfig.value?.title_style === 'bold' || giftRegistryConfig.value?.title_style === 'bold_italic' ? 700 : 400,
            fontItalic: giftRegistryConfig.value?.title_style === 'italic' || giftRegistryConfig.value?.title_style === 'bold_italic',
            fontUnderline: Boolean(localContent.value?.config?.title_underline ?? false),
        },
        DEFAULT_SECTION_TITLE_TYPOGRAPHY,
    );

    localContent.value.cardTitleTypography = mergeTypography(localContent.value.cardTitleTypography, DEFAULT_CARD_TITLE_TYPOGRAPHY);
    localContent.value.cardDescriptionTypography = mergeTypography(localContent.value.cardDescriptionTypography, DEFAULT_CARD_DESCRIPTION_TYPOGRAPHY);
    localContent.value.cardPriceTypography = mergeTypography(localContent.value.cardPriceTypography, DEFAULT_CARD_PRICE_TYPOGRAPHY);
    localContent.value.buttonTypography = mergeTypography(localContent.value.buttonTypography, DEFAULT_BUTTON_TYPOGRAPHY);
};

ensureStructure();

// Watch for external content changes
watch(() => props.content, (newContent) => {
    localContent.value = JSON.parse(JSON.stringify(newContent));
    ensureStructure();
}, { deep: true });

/**
 * Emit changes to parent (includes config in the content)
 */
const emitChange = () => {
    ensureStructure();

    // Sync typography to config before emitting
    if (localContent.value.titleTypography) {
        if (!localContent.value.config) {
            localContent.value.config = {};
        }
        
        localContent.value.config.title_font_family = localContent.value.titleTypography.fontFamily;
        localContent.value.config.title_color = localContent.value.titleTypography.fontColor;
        localContent.value.config.title_font_size = localContent.value.titleTypography.fontSize;
        localContent.value.config.title_underline = Boolean(localContent.value.titleTypography.fontUnderline);
        
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
        localContent.value.titleTypography = { ...DEFAULT_SECTION_TITLE_TYPOGRAPHY };
    }

    localContent.value.titleTypography[field] = value;
    emitChange();
};

const updateTypographyField = (fieldKey, field, value) => {
    if (!localContent.value[fieldKey] || typeof localContent.value[fieldKey] !== 'object') {
        const defaultsByKey = {
            cardTitleTypography: DEFAULT_CARD_TITLE_TYPOGRAPHY,
            cardDescriptionTypography: DEFAULT_CARD_DESCRIPTION_TYPOGRAPHY,
            cardPriceTypography: DEFAULT_CARD_PRICE_TYPOGRAPHY,
            buttonTypography: DEFAULT_BUTTON_TYPOGRAPHY,
        };

        localContent.value[fieldKey] = { ...(defaultsByKey[fieldKey] || DEFAULT_CARD_TITLE_TYPOGRAPHY) };
    }

    localContent.value[fieldKey][field] = value;
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
const giftCardBackgroundColorHex = computed(() => normalizeHexColor(style.value.cardBackgroundColor, '#ffffff'));
const giftCardBorderColorHex = computed(() => normalizeHexColor(style.value.cardBorderColor, '#e5e7eb'));
const giftButtonBackgroundColorHex = computed(() => normalizeHexColor(style.value.buttonBackgroundColor, '#3b82f6'));
const titleTypography = computed(() => localContent.value.titleTypography || {
    ...DEFAULT_SECTION_TITLE_TYPOGRAPHY,
});
const cardTitleTypography = computed(() => localContent.value.cardTitleTypography || DEFAULT_CARD_TITLE_TYPOGRAPHY);
const cardDescriptionTypography = computed(() => localContent.value.cardDescriptionTypography || DEFAULT_CARD_DESCRIPTION_TYPOGRAPHY);
const cardPriceTypography = computed(() => localContent.value.cardPriceTypography || DEFAULT_CARD_PRICE_TYPOGRAPHY);
const buttonTypography = computed(() => localContent.value.buttonTypography || DEFAULT_BUTTON_TYPOGRAPHY);

const pickGiftRegistryBackgroundColor = () => {
    pickColorFromScreen((hex) => updateStyle('backgroundColor', hex));
};

const pickGiftCardBackgroundColor = () => {
    pickColorFromScreen((hex) => updateStyle('cardBackgroundColor', hex));
};

const pickGiftCardBorderColor = () => {
    pickColorFromScreen((hex) => updateStyle('cardBorderColor', hex));
};

const pickGiftButtonBackgroundColor = () => {
    pickColorFromScreen((hex) => updateStyle('buttonBackgroundColor', hex));
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor de Fundo do Card</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="giftCardBackgroundColorHex"
                        @input="updateStyle('cardBackgroundColor', $event.target.value)"
                        @change="updateStyle('cardBackgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        @click="pickGiftCardBackgroundColor"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="style.cardBackgroundColor || '#ffffff'"
                        @input="updateStyle('cardBackgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor da Borda do Card</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="giftCardBorderColorHex"
                        @input="updateStyle('cardBorderColor', $event.target.value)"
                        @change="updateStyle('cardBorderColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        @click="pickGiftCardBorderColor"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="style.cardBorderColor || '#e5e7eb'"
                        @input="updateStyle('cardBorderColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor do Botão</label>
                <div class="flex items-center space-x-2">
                    <input
                        type="color"
                        :value="giftButtonBackgroundColorHex"
                        @input="updateStyle('buttonBackgroundColor', $event.target.value)"
                        @change="updateStyle('buttonBackgroundColor', $event.target.value)"
                        class="h-10 w-14 border border-gray-300 rounded cursor-pointer"
                    />
                    <button
                        v-if="isEyeDropperSupported"
                        type="button"
                        @click="pickGiftButtonBackgroundColor"
                        class="h-10 w-10 inline-flex items-center justify-center border border-gray-300 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-50"
                        title="Capturar cor da tela"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                        </svg>
                    </button>
                    <input
                        type="text"
                        :value="style.buttonBackgroundColor || '#3b82f6'"
                        @input="updateStyle('buttonBackgroundColor', $event.target.value)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 text-sm"
                    />
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Tipografia dos Cards</h3>

            <TypographyControl
                :font-family="cardTitleTypography.fontFamily"
                :font-color="cardTitleTypography.fontColor"
                :font-size="cardTitleTypography.fontSize"
                :font-weight="cardTitleTypography.fontWeight"
                :font-italic="cardTitleTypography.fontItalic"
                :font-underline="cardTitleTypography.fontUnderline"
                :preview-background-color="giftCardBackgroundColorHex"
                label="Tipografia do Título do Presente"
                @update:font-family="updateTypographyField('cardTitleTypography', 'fontFamily', $event)"
                @update:font-color="updateTypographyField('cardTitleTypography', 'fontColor', $event)"
                @update:font-size="updateTypographyField('cardTitleTypography', 'fontSize', $event)"
                @update:font-weight="updateTypographyField('cardTitleTypography', 'fontWeight', $event)"
                @update:font-italic="updateTypographyField('cardTitleTypography', 'fontItalic', $event)"
                @update:font-underline="updateTypographyField('cardTitleTypography', 'fontUnderline', $event)"
            />

            <TypographyControl
                :font-family="cardDescriptionTypography.fontFamily"
                :font-color="cardDescriptionTypography.fontColor"
                :font-size="cardDescriptionTypography.fontSize"
                :font-weight="cardDescriptionTypography.fontWeight"
                :font-italic="cardDescriptionTypography.fontItalic"
                :font-underline="cardDescriptionTypography.fontUnderline"
                :preview-background-color="giftCardBackgroundColorHex"
                label="Tipografia da Descrição"
                @update:font-family="updateTypographyField('cardDescriptionTypography', 'fontFamily', $event)"
                @update:font-color="updateTypographyField('cardDescriptionTypography', 'fontColor', $event)"
                @update:font-size="updateTypographyField('cardDescriptionTypography', 'fontSize', $event)"
                @update:font-weight="updateTypographyField('cardDescriptionTypography', 'fontWeight', $event)"
                @update:font-italic="updateTypographyField('cardDescriptionTypography', 'fontItalic', $event)"
                @update:font-underline="updateTypographyField('cardDescriptionTypography', 'fontUnderline', $event)"
            />

            <TypographyControl
                :font-family="cardPriceTypography.fontFamily"
                :font-color="cardPriceTypography.fontColor"
                :font-size="cardPriceTypography.fontSize"
                :font-weight="cardPriceTypography.fontWeight"
                :font-italic="cardPriceTypography.fontItalic"
                :font-underline="cardPriceTypography.fontUnderline"
                :preview-background-color="giftCardBackgroundColorHex"
                label="Tipografia do Preço"
                @update:font-family="updateTypographyField('cardPriceTypography', 'fontFamily', $event)"
                @update:font-color="updateTypographyField('cardPriceTypography', 'fontColor', $event)"
                @update:font-size="updateTypographyField('cardPriceTypography', 'fontSize', $event)"
                @update:font-weight="updateTypographyField('cardPriceTypography', 'fontWeight', $event)"
                @update:font-italic="updateTypographyField('cardPriceTypography', 'fontItalic', $event)"
                @update:font-underline="updateTypographyField('cardPriceTypography', 'fontUnderline', $event)"
            />

            <TypographyControl
                :font-family="buttonTypography.fontFamily"
                :font-color="buttonTypography.fontColor"
                :font-size="buttonTypography.fontSize"
                :font-weight="buttonTypography.fontWeight"
                :font-italic="buttonTypography.fontItalic"
                :font-underline="buttonTypography.fontUnderline"
                :preview-background-color="giftButtonBackgroundColorHex"
                label="Tipografia do Botão"
                @update:font-family="updateTypographyField('buttonTypography', 'fontFamily', $event)"
                @update:font-color="updateTypographyField('buttonTypography', 'fontColor', $event)"
                @update:font-size="updateTypographyField('buttonTypography', 'fontSize', $event)"
                @update:font-weight="updateTypographyField('buttonTypography', 'fontWeight', $event)"
                @update:font-italic="updateTypographyField('buttonTypography', 'fontItalic', $event)"
                @update:font-underline="updateTypographyField('buttonTypography', 'fontUnderline', $event)"
            />
        </div>

        <!-- Fee Configuration -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Configuração de Taxas</h3>
            <p class="text-sm text-gray-600">Defina o modo da lista e quem paga a taxa da plataforma</p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Modo da Lista</label>
                <select
                    :value="config.registry_mode || 'quantity'"
                    @change="updateConfig('registry_mode', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="quantity">Quantidade</option>
                    <option value="quota">Quota</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Quantidade: preço por unidade. Quota: preço por cota com barra de progresso no site.
                </p>
            </div>
            
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
    --tw-ring-color: #d87a8d;
}
.focus\:border-wedding-500:focus {
    border-color: #d87a8d;
}
.bg-wedding-600 {
    background-color: #c45a6f;
}
.bg-wedding-700 {
    background-color: #b9163a;
}
.text-wedding-600 {
    color: #c45a6f;
}
</style>
