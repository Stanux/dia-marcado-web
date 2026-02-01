<script setup>
/**
 * HeroEditor Component
 * 
 * Editor for the Hero section of the wedding site.
 * Supports image/video/gallery media, titles, CTAs, and layout options.
 * 
 * @Requirements: 9.1, 9.2, 9.4, 9.5
 */
import { ref, watch, computed } from 'vue';
import { SECTION_IDS, SECTION_LABELS } from '@/Composables/useSiteEditor';

const props = defineProps({
    content: {
        type: Object,
        required: true,
    },
    enabledSections: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['change']);

/**
 * Get available sections for CTA target (only enabled sections, excluding header/footer)
 */
const availableCtaTargets = computed(() => {
    const targets = [];
    Object.keys(SECTION_IDS).forEach(key => {
        // Skip hero itself as a target
        if (key === 'hero') return;
        // Only include if section is enabled
        if (props.enabledSections[key]) {
            targets.push({
                value: `#${SECTION_IDS[key]}`,
                label: SECTION_LABELS[key],
            });
        }
    });
    return targets;
});

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
 * Update media field
 */
const updateMedia = (field, value) => {
    if (!localContent.value.media) {
        localContent.value.media = { type: 'image', url: '', fallback: '', autoplay: true, loop: true };
    }
    localContent.value.media[field] = value;
    emitChange();
};

/**
 * Update CTA field
 */
const updateCta = (ctaType, field, value) => {
    if (!localContent.value[ctaType]) {
        localContent.value[ctaType] = { label: '', target: '' };
    }
    localContent.value[ctaType][field] = value;
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
 * Update overlay field
 */
const updateOverlay = (field, value) => {
    if (!localContent.value.style) {
        localContent.value.style = {};
    }
    if (!localContent.value.style.overlay) {
        localContent.value.style.overlay = { color: '#000000', opacity: 0.3 };
    }
    localContent.value.style.overlay[field] = value;
    emitChange();
};

// Computed properties
const media = computed(() => localContent.value.media || { type: 'image', url: '', fallback: '', autoplay: true, loop: true });
const ctaPrimary = computed(() => localContent.value.ctaPrimary || { label: '', target: '' });
const ctaSecondary = computed(() => localContent.value.ctaSecondary || { label: '', target: '' });
const style = computed(() => localContent.value.style || {});
const overlay = computed(() => style.value.overlay || { color: '#000000', opacity: 0.3 });
const isVideo = computed(() => media.value.type === 'video');
</script>

<template>
    <div class="space-y-6">
        <!-- Media Section -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Mídia</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Mídia</label>
                <select
                    :value="media.type"
                    @change="updateMedia('type', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="image">Imagem</option>
                    <option value="video">Vídeo</option>
                    <option value="gallery">Galeria</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ media.type === 'video' ? 'URL do Vídeo' : 'URL da Imagem' }}
                </label>
                <input
                    type="text"
                    :value="media.url"
                    @input="updateMedia('url', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    :placeholder="media.type === 'video' ? 'https://youtube.com/... ou https://vimeo.com/...' : 'https://exemplo.com/imagem.jpg'"
                />
                <p class="mt-1 text-xs text-gray-500">
                    {{ media.type === 'video' ? 'Suporta YouTube, Vimeo ou URL direta de vídeo' : 'Cole uma URL ou use o gerenciador de mídia' }}
                </p>
            </div>

            <!-- Video-specific options -->
            <template v-if="isVideo">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Imagem de Fallback</label>
                    <input
                        type="text"
                        :value="media.fallback"
                        @input="updateMedia('fallback', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        placeholder="Imagem exibida enquanto o vídeo carrega"
                    />
                </div>

                <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            :checked="media.autoplay"
                            @change="updateMedia('autoplay', $event.target.checked)"
                            class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 text-sm text-gray-700">Autoplay (desktop)</label>
                    </div>
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            :checked="media.loop"
                            @change="updateMedia('loop', $event.target.checked)"
                            class="h-4 w-4 text-wedding-600 focus:ring-wedding-500 border-gray-300 rounded"
                        />
                        <label class="ml-2 text-sm text-gray-700">Loop</label>
                    </div>
                </div>
            </template>
        </div>

        <!-- Text Content -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Textos</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título Principal</label>
                <input
                    type="text"
                    :value="localContent.title"
                    @input="updateField('title', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Ex: Vamos nos casar!"
                />
                <p class="mt-1 text-xs text-gray-500">Suporta rich text básico e placeholders</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subtítulo</label>
                <textarea
                    :value="localContent.subtitle"
                    @input="updateField('subtitle', $event.target.value)"
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    placeholder="Ex: Junte-se a nós neste dia especial"
                ></textarea>
            </div>
        </div>

        <!-- CTAs -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Botões de Ação (CTA)</h3>
            
            <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                <span class="text-sm font-medium text-gray-700">CTA Primário</span>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Rótulo</label>
                        <input
                            type="text"
                            :value="ctaPrimary.label"
                            @input="updateCta('ctaPrimary', 'label', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="Ex: Confirmar Presença"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Destino</label>
                        <select
                            :value="ctaPrimary.target"
                            @change="updateCta('ctaPrimary', 'target', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        >
                            <option value="">Selecione uma seção</option>
                            <option 
                                v-for="target in availableCtaTargets" 
                                :key="target.value" 
                                :value="target.value"
                            >
                                {{ target.label }}
                            </option>
                        </select>
                        <p v-if="availableCtaTargets.length === 0" class="mt-1 text-xs text-amber-600">
                            Ative outras seções para vincular
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                <span class="text-sm font-medium text-gray-700">CTA Secundário</span>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Rótulo</label>
                        <input
                            type="text"
                            :value="ctaSecondary.label"
                            @input="updateCta('ctaSecondary', 'label', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                            placeholder="Ex: Ver Fotos"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Destino</label>
                        <select
                            :value="ctaSecondary.target"
                            @change="updateCta('ctaSecondary', 'target', $event.target.value)"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        >
                            <option value="">Selecione uma seção</option>
                            <option 
                                v-for="target in availableCtaTargets" 
                                :key="target.value" 
                                :value="target.value"
                            >
                                {{ target.label }}
                            </option>
                        </select>
                        <p v-if="availableCtaTargets.length === 0" class="mt-1 text-xs text-amber-600">
                            Ative outras seções para vincular
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layout -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Layout</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Layout</label>
                <select
                    :value="localContent.layout"
                    @change="updateField('layout', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="full-bleed">Full Bleed (tela inteira)</option>
                    <option value="boxed">Boxed (com margens)</option>
                    <option value="split">Split (imagem/texto lado a lado)</option>
                </select>
            </div>
        </div>

        <!-- Style Settings -->
        <div class="space-y-4 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Estilo</h3>
            
            <!-- Overlay -->
            <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                <span class="text-sm font-medium text-gray-700">Overlay</span>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cor</label>
                        <div class="flex items-center space-x-2">
                            <input
                                type="color"
                                :value="overlay.color"
                                @input="updateOverlay('color', $event.target.value)"
                                class="h-8 w-12 border border-gray-300 rounded cursor-pointer"
                            />
                            <input
                                type="text"
                                :value="overlay.color"
                                @input="updateOverlay('color', $event.target.value)"
                                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded-md"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Opacidade</label>
                        <div class="flex items-center space-x-2">
                            <input
                                type="range"
                                min="0"
                                max="1"
                                step="0.1"
                                :value="overlay.opacity"
                                @input="updateOverlay('opacity', parseFloat($event.target.value))"
                                class="flex-1"
                            />
                            <span class="text-sm text-gray-600 w-10">{{ (overlay.opacity * 100).toFixed(0) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alinhamento do Texto</label>
                <select
                    :value="style.textAlign"
                    @change="updateStyle('textAlign', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                >
                    <option value="left">Esquerda</option>
                    <option value="center">Centro</option>
                    <option value="right">Direita</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Animação</label>
                    <select
                        :value="style.animation"
                        @change="updateStyle('animation', $event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                    >
                        <option value="none">Nenhuma</option>
                        <option value="fade">Fade</option>
                        <option value="slide">Slide</option>
                        <option value="zoom">Zoom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duração (ms)</label>
                    <input
                        type="number"
                        :value="style.animationDuration"
                        @input="updateStyle('animationDuration', parseInt($event.target.value))"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500"
                        placeholder="500"
                        min="0"
                        step="100"
                    />
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
.text-wedding-600 {
    color: #a18072;
}
</style>
