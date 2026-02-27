<script setup>
/**
 * TemplateCard Component
 * 
 * Displays a template card with thumbnail, name, description,
 * and action buttons for preview and selection.
 * 
 * @Requirements: 15.1
 */
import { computed, ref, watch } from 'vue';

const props = defineProps({
    template: {
        type: Object,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['preview', 'select']);
const hasImageError = ref(false);
const TEMPLATE_FALLBACK_THUMBNAIL = `data:image/svg+xml;utf8,${encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500"><defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#fff1f4"/><stop offset="100%" stop-color="#fde8ee"/></linearGradient></defs><rect width="800" height="500" fill="url(#bg)"/><rect x="88" y="72" width="624" height="356" rx="18" fill="#ffffff" stroke="#ffccd9" stroke-width="4"/><rect x="120" y="112" width="560" height="32" rx="8" fill="#fde8ee"/><rect x="120" y="164" width="360" height="20" rx="8" fill="#fff1f4"/><rect x="120" y="198" width="280" height="20" rx="8" fill="#fff1f4"/><rect x="120" y="246" width="170" height="128" rx="10" fill="#fff1f4" stroke="#ffccd9" stroke-width="2"/><rect x="314" y="246" width="170" height="128" rx="10" fill="#fff1f4" stroke="#ffccd9" stroke-width="2"/><rect x="508" y="246" width="170" height="128" rx="10" fill="#fff1f4" stroke="#ffccd9" stroke-width="2"/><circle cx="400" cy="302" r="36" fill="#ffccd9"/><path d="M382 314l14-15 11 10 13-14 16 19h-54z" fill="#b9163a"/><text x="400" y="422" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="26" fill="#4A2F39">Template sem imagem</text></svg>')}`;

// Determine badge type based on template ownership
const badgeType = computed(() => {
    if (props.template.is_locked) {
        return { label: 'Upgrade', class: 'bg-amber-100 text-amber-800' };
    }

    if (props.template.is_system) {
        return { label: 'Sistema', class: 'bg-wedding-100 text-wedding-700' };
    }
    if (props.template.is_public) {
        return { label: 'Público', class: 'bg-rose-100 text-rose-700' };
    }
    return { label: 'Privado', class: 'bg-gray-100 text-gray-800' };
});

const isLocked = computed(() => props.template.is_locked === true);

const planHint = computed(() => {
    if (!isLocked.value) {
        return '';
    }

    const plans = Array.isArray(props.template.required_plans)
        ? props.template.required_plans.join(', ')
        : '';

    if (!plans) {
        return 'Disponível em planos superiores.';
    }

    return `Disponível para: ${plans}.`;
});

// Default thumbnail placeholder
const thumbnailUrl = computed(() => {
    if (hasImageError.value) {
        return TEMPLATE_FALLBACK_THUMBNAIL;
    }

    const thumbnail = typeof props.template.thumbnail === 'string'
        ? props.template.thumbnail.trim()
        : '';

    return thumbnail !== '' ? thumbnail : TEMPLATE_FALLBACK_THUMBNAIL;
});

const handleImageError = (event) => {
    hasImageError.value = true;

    const image = event?.target;
    if (!image) {
        return;
    }

    image.onerror = null;
    image.src = TEMPLATE_FALLBACK_THUMBNAIL;
    image.alt = 'Imagem padrao de template';
};

watch(
    () => [props.template?.id, props.template?.thumbnail],
    () => {
        hasImageError.value = false;
    },
);

const handlePreview = () => {
    emit('preview', props.template);
};

const handleSelect = () => {
    if (isLocked.value) {
        return;
    }

    emit('select', props.template);
};
</script>

<template>
    <div
        class="group relative bg-white rounded-lg border-2 transition-all duration-200 overflow-hidden"
        :class="[
            selected 
                ? 'border-wedding-500 ring-2 ring-wedding-200' 
                : 'border-gray-200 hover:border-wedding-300 hover:shadow-md'
        ]"
    >
        <!-- Thumbnail -->
        <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
            <img
                :src="thumbnailUrl"
                :alt="template.name"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                @error="handleImageError"
            />
            
            <!-- Hover Overlay with Actions -->
            <div 
                class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-3"
            >
                <button
                    @click.stop="handlePreview"
                    class="px-4 py-2 bg-white text-gray-800 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Navegar
                </button>
                <button
                    @click.stop="handleSelect"
                    :disabled="isLocked"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
                    :class="[
                        isLocked
                            ? 'bg-gray-300 text-gray-700 cursor-not-allowed'
                            : 'bg-wedding-600 text-white hover:bg-wedding-700'
                    ]"
                >
                    {{ isLocked ? 'Bloqueado' : 'Usar' }}
                </button>
            </div>

            <!-- Badge -->
            <span
                :class="[
                    'absolute top-2 right-2 px-2 py-1 text-xs font-medium rounded-full',
                    badgeType.class
                ]"
            >
                {{ badgeType.label }}
            </span>

            <!-- Selected Indicator -->
            <div
                v-if="selected"
                class="absolute top-2 left-2 w-6 h-6 bg-wedding-500 rounded-full flex items-center justify-center"
            >
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4">
            <h3 class="font-medium text-gray-900 truncate">
                {{ template.name }}
            </h3>
            <p 
                v-if="template.description" 
                class="mt-1 text-sm text-gray-500 line-clamp-2"
            >
                {{ template.description }}
            </p>
            <p 
                v-else 
                class="mt-1 text-sm text-gray-400 italic"
            >
                Sem descrição
            </p>
            <p
                v-if="planHint"
                class="mt-2 text-xs font-medium text-amber-700"
            >
                {{ planHint }}
            </p>
        </div>
    </div>
</template>

<style scoped>
.bg-wedding-500 {
    background-color: #c45a6f;
}
.bg-wedding-100 {
    background-color: #fde8ee;
}
.bg-wedding-600 {
    background-color: #b9163a;
}
.bg-wedding-700 {
    background-color: #4A2F39;
}
.border-wedding-300 {
    border-color: #ffccd9;
}
.border-wedding-500 {
    border-color: #c45a6f;
}
.ring-wedding-200 {
    --tw-ring-color: rgba(216, 122, 141, 0.3);
}
.text-wedding-600 {
    color: #b9163a;
}
.text-wedding-700 {
    color: #4A2F39;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
