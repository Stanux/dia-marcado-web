<script setup>
/**
 * TypographyControl Component
 * 
 * Componente reutilizável para configuração de tipografia.
 * Permite definir tipo de fonte, cor, tamanho e estilo (negrito, itálico, sublinhado).
 * Usado em campos de texto, títulos, subtítulos e outros elementos textuais do editor de sites.
 */
import { ref, computed, watch } from 'vue';
import ColorPicker from './ColorPicker.vue';

const props = defineProps({
    // Tipo de fonte
    fontFamily: {
        type: String,
        default: 'Inter',
    },
    // Cor da fonte
    fontColor: {
        type: String,
        default: '#000000',
    },
    // Tamanho da fonte (em px)
    fontSize: {
        type: [Number, String],
        default: 16,
    },
    // Peso da fonte (bold)
    fontWeight: {
        type: [Number, String],
        default: 400,
    },
    // Estilo itálico
    fontItalic: {
        type: Boolean,
        default: false,
    },
    // Sublinhado
    fontUnderline: {
        type: Boolean,
        default: false,
    },
    // Label do controle
    label: {
        type: String,
        default: 'Tipografia',
    },
    // Mostrar controle de cor
    showColor: {
        type: Boolean,
        default: true,
    },
    // Mostrar controle de tamanho
    showSize: {
        type: Boolean,
        default: true,
    },
    // Mostrar controle de fonte
    showFontFamily: {
        type: Boolean,
        default: true,
    },
    // Mostrar controles de estilo
    showStyles: {
        type: Boolean,
        default: true,
    },
    // Desabilitar controle
    disabled: {
        type: Boolean,
        default: false,
    },
    // Tamanhos predefinidos
    fontSizes: {
        type: Array,
        default: () => [
            { value: 12, label: 'Muito Pequeno' },
            { value: 14, label: 'Pequeno' },
            { value: 16, label: 'Normal' },
            { value: 18, label: 'Médio' },
            { value: 20, label: 'Grande' },
            { value: 24, label: 'Muito Grande' },
            { value: 32, label: 'Extra Grande' },
            { value: 48, label: 'Título' },
        ],
    },
    // Fontes disponíveis
    fontFamilies: {
        type: Array,
        default: () => [
            // Elegantes / Manuscritas
            { value: 'Dancing Script', label: 'Dancing Script (Manuscrita)', category: 'script' },
            { value: 'Tangerine', label: 'Tangerine (Caligráfica)', category: 'script' },
            { value: 'Allura', label: 'Allura (Delicada)', category: 'script' },
            { value: 'Sacramento', label: 'Sacramento (Suave)', category: 'script' },
            
            // Serifas Elegantes
            { value: 'Playfair Display', label: 'Playfair Display (Clássica)', category: 'serif' },
            { value: 'Cormorant Garamond', label: 'Cormorant Garamond (Sofisticada)', category: 'serif' },
            { value: 'Merriweather', label: 'Merriweather (Tradicional)', category: 'serif' },
            
            // Sans-Serif Limpas
            { value: 'Montserrat', label: 'Montserrat (Moderna)', category: 'sans-serif' },
            { value: 'Lato', label: 'Lato (Legível)', category: 'sans-serif' },
            { value: 'Raleway', label: 'Raleway (Refinada)', category: 'sans-serif' },
            { value: 'Roboto', label: 'Roboto (Versátil)', category: 'sans-serif' },
            { value: 'Open Sans', label: 'Open Sans (Universal)', category: 'sans-serif' },
        ],
    },
});

const emit = defineEmits([
    'update:fontFamily',
    'update:fontColor',
    'update:fontSize',
    'update:fontWeight',
    'update:fontItalic',
    'update:fontUnderline',
]);

// Estados locais
const localFontFamily = ref(props.fontFamily);
const localFontColor = ref(props.fontColor);
const localFontSize = ref(props.fontSize);
const localFontWeight = ref(props.fontWeight);
const localFontItalic = ref(props.fontItalic);
const localFontUnderline = ref(props.fontUnderline);

// Controle de expansão
const isExpanded = ref(false);

/**
 * Watch para mudanças externas
 */
watch(() => props.fontFamily, (val) => localFontFamily.value = val);
watch(() => props.fontColor, (val) => localFontColor.value = val);
watch(() => props.fontSize, (val) => localFontSize.value = val);
watch(() => props.fontWeight, (val) => localFontWeight.value = val);
watch(() => props.fontItalic, (val) => localFontItalic.value = val);
watch(() => props.fontUnderline, (val) => localFontUnderline.value = val);

/**
 * Handlers de mudança
 */
const handleFontFamilyChange = (event) => {
    localFontFamily.value = event.target.value;
    emit('update:fontFamily', localFontFamily.value);
};

const handleFontColorChange = (color) => {
    localFontColor.value = color;
    emit('update:fontColor', color);
};

const handleFontSizeChange = (event) => {
    const size = parseInt(event.target.value);
    localFontSize.value = size;
    emit('update:fontSize', size);
};

const handleCustomSizeChange = (event) => {
    const size = parseInt(event.target.value) || 16;
    localFontSize.value = size;
    emit('update:fontSize', size);
};

const toggleBold = () => {
    localFontWeight.value = localFontWeight.value >= 600 ? 400 : 700;
    emit('update:fontWeight', localFontWeight.value);
};

const toggleItalic = () => {
    localFontItalic.value = !localFontItalic.value;
    emit('update:fontItalic', localFontItalic.value);
};

const toggleUnderline = () => {
    localFontUnderline.value = !localFontUnderline.value;
    emit('update:fontUnderline', localFontUnderline.value);
};

/**
 * Estilo de preview
 */
const previewStyle = computed(() => ({
    fontFamily: localFontFamily.value,
    color: localFontColor.value,
    fontSize: `${localFontSize.value}px`,
    fontWeight: localFontWeight.value,
    fontStyle: localFontItalic.value ? 'italic' : 'normal',
    textDecoration: localFontUnderline.value ? 'underline' : 'none',
}));

/**
 * Verifica se o tamanho é customizado
 */
const isCustomSize = computed(() => {
    return !props.fontSizes.some(s => s.value === parseInt(localFontSize.value));
});

/**
 * Resumo da configuração
 */
const configSummary = computed(() => {
    const parts = [];
    
    if (props.showFontFamily) {
        const font = props.fontFamilies.find(f => f.value === localFontFamily.value);
        parts.push(font?.label || localFontFamily.value);
    }
    
    if (props.showSize) {
        parts.push(`${localFontSize.value}px`);
    }
    
    const styles = [];
    if (localFontWeight.value >= 600) styles.push('Negrito');
    if (localFontItalic.value) styles.push('Itálico');
    if (localFontUnderline.value) styles.push('Sublinhado');
    
    if (styles.length > 0) {
        parts.push(styles.join(', '));
    }
    
    return parts.join(' • ');
});
</script>

<template>
    <div class="typography-control" :class="{ 'disabled': disabled }">
        <!-- Header com toggle -->
        <div class="control-header" @click="isExpanded = !isExpanded">
            <div class="flex items-center justify-between flex-1">
                <label class="control-label">{{ label }}</label>
                <button
                    type="button"
                    class="expand-toggle"
                    :class="{ 'expanded': isExpanded }"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Preview compacto -->
        <div v-if="!isExpanded" class="preview-compact">
            <div class="preview-text" :style="previewStyle">Aa</div>
            <div class="preview-summary">{{ configSummary }}</div>
        </div>
        
        <!-- Controles expandidos -->
        <div v-if="isExpanded" class="controls-expanded">
            <div class="controls-row controls-row-top">
                <!-- Tipo de Fonte -->
                <div v-if="showFontFamily" class="control-group">
                    <label class="control-label-small">Tipo de Fonte</label>
                    <select
                        :value="localFontFamily"
                        @change="handleFontFamilyChange"
                        class="control-select"
                        :disabled="disabled"
                    >
                        <optgroup label="✒️ Manuscritas / Elegantes">
                            <option
                                v-for="font in fontFamilies.filter(f => f.category === 'script')"
                                :key="font.value"
                                :value="font.value"
                            >
                                {{ font.label }}
                            </option>
                        </optgroup>
                        <optgroup label="🖋️ Serifas Clássicas">
                            <option
                                v-for="font in fontFamilies.filter(f => f.category === 'serif')"
                                :key="font.value"
                                :value="font.value"
                            >
                                {{ font.label }}
                            </option>
                        </optgroup>
                        <optgroup label="🔡 Sans-Serif Modernas">
                            <option
                                v-for="font in fontFamilies.filter(f => f.category === 'sans-serif')"
                                :key="font.value"
                                :value="font.value"
                            >
                                {{ font.label }}
                            </option>
                        </optgroup>
                    </select>
                </div>
                
                <!-- Tamanho de Fonte -->
                <div v-if="showSize" class="control-group">
                    <label class="control-label-small">Tamanho de Fonte</label>
                    <div class="size-controls">
                        <select
                            :value="isCustomSize ? 'custom' : localFontSize"
                            @change="handleFontSizeChange"
                            class="control-select flex-1"
                            :disabled="disabled"
                        >
                            <option
                                v-for="size in fontSizes"
                                :key="size.value"
                                :value="size.value"
                            >
                                {{ size.label }} ({{ size.value }}px)
                            </option>
                            <option value="custom">Personalizado</option>
                        </select>
                        
                        <input
                            v-if="isCustomSize"
                            type="number"
                            :value="localFontSize"
                            @input="handleCustomSizeChange"
                            class="size-input"
                            min="8"
                            max="200"
                            :disabled="disabled"
                        />
                    </div>
                </div>
            </div>

            <div class="controls-row controls-row-bottom">
                <!-- Cor da Fonte -->
                <div v-if="showColor" class="control-group">
                    <label class="control-label-small">Cor da Fonte</label>
                    <ColorPicker
                        :model-value="localFontColor"
                        @update:model-value="handleFontColorChange"
                        label=""
                        :disabled="disabled"
                    />
                </div>
                
                <!-- Estilos -->
                <div v-if="showStyles" class="control-group">
                    <label class="control-label-small">Estilo</label>
                    <div class="style-buttons">
                        <button
                            type="button"
                            @click="toggleBold"
                            class="style-btn"
                            :class="{ 'active': localFontWeight >= 600 }"
                            title="Negrito"
                            :disabled="disabled"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" />
                            </svg>
                        </button>
                        
                        <button
                            type="button"
                            @click="toggleItalic"
                            class="style-btn"
                            :class="{ 'active': localFontItalic }"
                            title="Itálico"
                            :disabled="disabled"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0v16m-4 0h8" transform="skewX(-10)" />
                            </svg>
                        </button>
                        
                        <button
                            type="button"
                            @click="toggleUnderline"
                            class="style-btn"
                            :class="{ 'active': localFontUnderline }"
                            title="Sublinhado"
                            :disabled="disabled"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 20h14M7 4v8a5 5 0 0010 0V4" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="controls-row">
                <div class="control-group">
                    <label class="control-label-small">Preview</label>
                    <div class="preview-box">
                        <p :style="previewStyle">
                            O rato roeu a roupa do rei de Roma
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.typography-control {
    @apply border border-gray-300 rounded-lg bg-white overflow-hidden;
}

.typography-control.disabled {
    @apply opacity-60 pointer-events-none;
}

.control-header {
    @apply px-4 py-3 bg-gray-50 border-b border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors;
}

.control-label {
    @apply text-sm font-medium text-gray-700;
}

.control-label-small {
    @apply block text-xs font-medium text-gray-700 mb-2;
}

.expand-toggle {
    @apply text-gray-500 transition-transform;
}

.expand-toggle.expanded {
    @apply rotate-180;
}

.preview-compact {
    @apply flex items-center gap-4 px-4 py-3;
}

.preview-text {
    @apply text-2xl font-medium;
}

.preview-summary {
    @apply text-sm text-gray-600 flex-1;
}

.controls-expanded {
    @apply p-4 space-y-4;
}

.controls-row {
    @apply w-full;
}

.controls-row-top,
.controls-row-bottom {
    @apply grid gap-4 items-start;
}

.controls-row-top {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.controls-row-bottom {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.controls-row-top .control-group,
.controls-row-bottom .control-group {
    @apply flex flex-col justify-start;
}

.controls-row-top .control-label-small,
.controls-row-bottom .control-label-small {
    @apply mb-2;
    min-height: 1rem;
    line-height: 1rem;
}

.controls-row-top .control-group :deep(.color-picker .picker-container),
.controls-row-bottom .control-group :deep(.color-picker .picker-container) {
    margin-top: 0;
}

@media (max-width: 1279px) {
    .controls-row-top,
    .controls-row-bottom {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767px) {
    .controls-row-top,
    .controls-row-bottom {
        grid-template-columns: 1fr;
    }
}

.control-group {
    @apply space-y-2;
}

.control-select {
    @apply w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 bg-white;
}

.size-controls {
    @apply flex gap-2;
}

.size-input {
    @apply w-20 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500;
}

.style-buttons {
    @apply flex gap-2;
}

.style-btn {
    @apply p-2 border border-gray-300 rounded-md text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors;
}

.style-btn.active {
    @apply bg-wedding-100 border-wedding-500 text-wedding-700;
}

.style-btn:disabled {
    @apply opacity-50 cursor-not-allowed;
}

.preview-box {
    @apply p-4 bg-gray-50 border border-gray-200 rounded-md;
}

/* Wedding theme colors */
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #b8998a;
}

.focus\:border-wedding-500:focus {
    border-color: #b8998a;
}

.bg-wedding-100 {
    background-color: #f5ebe4;
}

.border-wedding-500 {
    border-color: #b8998a;
}

.text-wedding-700 {
    color: #8b6b5d;
}
</style>
