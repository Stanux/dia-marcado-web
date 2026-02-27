<script setup>
/**
 * ColorPicker Component
 * 
 * Color input with preview, suggested palette, and transparency support.
 * Used for styling sections like overlays, backgrounds, and text colors.
 * 
 * @Requirements: 8.5, 9.5
 */
import { ref, computed, watch } from 'vue';
import { useColorField } from '@/Composables/useColorField';

const props = defineProps({
    modelValue: {
        type: String,
        default: '#ffffff',
    },
    label: {
        type: String,
        default: '',
    },
    showAlpha: {
        type: Boolean,
        default: false,
    },
    alpha: {
        type: Number,
        default: 1,
    },
    presets: {
        type: Array,
        default: () => [
            // Wedding palette
            { color: '#f97373', name: 'Dourado' },
            { color: '#b85c5c', name: 'Bronze' },
            { color: '#fde8ee', name: 'Creme' },
            { color: '#c45a6f', name: 'Rose' },
            // Neutrals
            { color: '#ffffff', name: 'Branco' },
            { color: '#f5f5f5', name: 'Cinza Claro' },
            { color: '#333333', name: 'Cinza Escuro' },
            { color: '#000000', name: 'Preto' },
            // Accent colors
            { color: '#e8d5c4', name: 'Bege' },
            { color: '#c9b8a8', name: 'Taupe' },
            { color: '#9caf88', name: 'Verde Sage' },
            { color: '#b8c5d6', name: 'Azul Dusty' },
        ],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'update:alpha']);
const { isEyeDropperSupported, normalizeHexColor, pickColorFromScreen } = useColorField();

const showPalette = ref(false);
const localColor = ref(normalizeHexColor(props.modelValue, '#ffffff'));
const localAlpha = ref(props.alpha);

/**
 * Watch for external value changes
 */
watch(() => props.modelValue, (newValue) => {
    localColor.value = normalizeHexColor(newValue, '#ffffff');
});

watch(() => props.alpha, (newValue) => {
    localAlpha.value = newValue;
});

/**
 * Handle color input change
 */
const handleColorChange = (event) => {
    localColor.value = normalizeHexColor(event.target.value, '#ffffff');
    emit('update:modelValue', localColor.value);
};

/**
 * Handle text input change
 */
const handleTextChange = (event) => {
    let value = event.target.value;
    
    // Add # if missing
    if (value && !value.startsWith('#')) {
        value = '#' + value;
    }
    
    // Validate hex color
    if (/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(value)) {
        localColor.value = value;
        emit('update:modelValue', value);
    }
};

/**
 * Handle alpha slider change
 */
const handleAlphaChange = (event) => {
    localAlpha.value = parseFloat(event.target.value);
    emit('update:alpha', localAlpha.value);
};

/**
 * Select preset color
 */
const selectPreset = (preset) => {
    localColor.value = preset.color;
    emit('update:modelValue', preset.color);
    showPalette.value = false;
};

const pickColorFromEyedropper = async () => {
    await pickColorFromScreen((hex) => {
        localColor.value = hex;
        emit('update:modelValue', hex);
    });
};

/**
 * Get preview style with alpha
 */
const previewStyle = computed(() => {
    if (props.showAlpha) {
        return {
            backgroundColor: hexToRgba(localColor.value, localAlpha.value),
        };
    }
    return {
        backgroundColor: localColor.value,
    };
});

/**
 * Convert hex to rgba
 */
const hexToRgba = (hex, alpha) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (result) {
        const r = parseInt(result[1], 16);
        const g = parseInt(result[2], 16);
        const b = parseInt(result[3], 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    return hex;
};

/**
 * Format alpha as percentage
 */
const alphaPercentage = computed(() => {
    return Math.round(localAlpha.value * 100) + '%';
});

/**
 * Close palette when clicking outside
 */
const closePalette = () => {
    showPalette.value = false;
};
</script>

<template>
    <div class="color-picker" :class="{ 'disabled': disabled }">
        <!-- Label -->
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
        </label>
        
        <div class="picker-container">
            <!-- Color Input Row -->
            <div class="color-input-row">
                <!-- Color Preview & Native Picker -->
                <div class="color-preview-wrapper">
                    <div class="color-preview" :style="previewStyle">
                        <input
                            type="color"
                            :value="localColor"
                            @input="handleColorChange"
                            @change="handleColorChange"
                            class="color-input-native"
                            :disabled="disabled"
                        />
                    </div>
                </div>
                
                <!-- Hex Input -->
                <input
                    type="text"
                    :value="localColor"
                    @input="handleTextChange"
                    @blur="handleTextChange"
                    class="hex-input"
                    placeholder="#000000"
                    maxlength="7"
                    :disabled="disabled"
                />

                <button
                    v-if="isEyeDropperSupported"
                    type="button"
                    @click="pickColorFromEyedropper"
                    class="palette-toggle"
                    :disabled="disabled"
                    title="Capturar cor da tela"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l4 4M7 13l6-6a2.828 2.828 0 114 4l-6 6m-4 0H3v-4l9-9" />
                    </svg>
                </button>
                
                <!-- Palette Toggle -->
                <button
                    type="button"
                    @click="showPalette = !showPalette"
                    class="palette-toggle"
                    :disabled="disabled"
                    title="Paleta de cores"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </button>
            </div>
            
            <!-- Alpha Slider (for overlays) -->
            <div v-if="showAlpha" class="alpha-slider-container">
                <label class="text-xs text-gray-600">Opacidade: {{ alphaPercentage }}</label>
                <div class="alpha-slider-row">
                    <input
                        type="range"
                        :value="localAlpha"
                        @input="handleAlphaChange"
                        min="0"
                        max="1"
                        step="0.01"
                        class="alpha-slider"
                        :disabled="disabled"
                    />
                    <span class="alpha-value">{{ alphaPercentage }}</span>
                </div>
            </div>
            
            <!-- Color Palette Dropdown -->
            <div v-if="showPalette" class="palette-dropdown" v-click-outside="closePalette">
                <p class="text-xs font-medium text-gray-700 mb-2">Cores sugeridas</p>
                <div class="preset-grid">
                    <button
                        v-for="preset in presets"
                        :key="preset.color"
                        type="button"
                        @click="selectPreset(preset)"
                        class="preset-swatch"
                        :style="{ backgroundColor: preset.color }"
                        :title="preset.name"
                        :class="{ 'selected': localColor === preset.color }"
                    >
                        <span class="sr-only">{{ preset.name }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Click outside directive
export default {
    directives: {
        'click-outside': {
            mounted(el, binding) {
                el._clickOutside = (event) => {
                    if (!(el === event.target || el.contains(event.target))) {
                        binding.value();
                    }
                };
                document.addEventListener('click', el._clickOutside);
            },
            unmounted(el) {
                document.removeEventListener('click', el._clickOutside);
            },
        },
    },
};
</script>

<style scoped>
.color-picker {
    @apply w-full;
}

.color-picker.disabled {
    @apply opacity-60 pointer-events-none;
}

.picker-container {
    @apply relative;
}

.color-input-row {
    @apply flex items-center space-x-2;
}

.color-preview-wrapper {
    @apply relative;
}

.color-preview {
    @apply w-10 h-10 rounded border border-gray-300 cursor-pointer overflow-hidden;
    /* Checkerboard pattern for transparency */
    background-image: linear-gradient(45deg, #ccc 25%, transparent 25%),
                      linear-gradient(-45deg, #ccc 25%, transparent 25%),
                      linear-gradient(45deg, transparent 75%, #ccc 75%),
                      linear-gradient(-45deg, transparent 75%, #ccc 75%);
    background-size: 8px 8px;
    background-position: 0 0, 0 4px, 4px -4px, -4px 0px;
}

.color-preview::before {
    content: '';
    @apply absolute inset-0;
    pointer-events: none;
}

.color-input-native {
    @apply absolute inset-0 w-full h-full opacity-0 cursor-pointer;
}

.hex-input {
    @apply flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 font-mono;
}

.palette-toggle {
    @apply p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors;
}

.alpha-slider-container {
    @apply mt-3;
}

.alpha-slider-row {
    @apply flex items-center space-x-3 mt-1;
}

.alpha-slider {
    @apply flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer;
}

.alpha-slider::-webkit-slider-thumb {
    @apply appearance-none w-4 h-4 bg-wedding-600 rounded-full cursor-pointer;
}

.alpha-slider::-moz-range-thumb {
    @apply w-4 h-4 bg-wedding-600 rounded-full cursor-pointer border-0;
}

.alpha-value {
    @apply text-xs text-gray-600 w-10 text-right;
}

.palette-dropdown {
    @apply absolute top-full left-0 mt-2 p-3 bg-white border border-gray-200 rounded-lg shadow-lg z-50 w-64;
}

.preset-grid {
    @apply grid grid-cols-6 gap-2;
}

.preset-swatch {
    @apply w-8 h-8 rounded border border-gray-200 cursor-pointer transition-transform hover:scale-110;
}

.preset-swatch.selected {
    @apply ring-2 ring-wedding-500 ring-offset-1;
}

/* Wedding theme colors */
.focus\:ring-wedding-500:focus {
    --tw-ring-color: #d87a8d;
}

.focus\:border-wedding-500:focus {
    border-color: #d87a8d;
}

.bg-wedding-600 {
    background-color: #c45a6f;
}

.ring-wedding-500 {
    --tw-ring-color: #d87a8d;
}
</style>
