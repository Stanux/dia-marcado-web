<script setup>
/**
 * PlaceholderHelper Component
 * 
 * Displays available placeholders with click-to-insert functionality.
 * Shows preview of real values from wedding data.
 * 
 * @Requirements: 22.1-22.7
 */
import { computed } from 'vue';

const props = defineProps({
    wedding: {
        type: Object,
        default: () => ({}),
    },
    compact: {
        type: Boolean,
        default: false,
    },
    showPreview: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['insert']);

/**
 * Available placeholders with descriptions
 */
const placeholders = computed(() => [
    {
        key: '{noivo}',
        label: 'Nome do Noivo',
        description: 'Primeiro nome do casal',
        preview: getNoivoPreview(),
        category: 'couple',
    },
    {
        key: '{noiva}',
        label: 'Nome da Noiva',
        description: 'Segundo nome do casal',
        preview: getNoivaPreview(),
        category: 'couple',
    },
    {
        key: '{noivos}',
        label: 'Nomes dos Noivos',
        description: 'Todos os nomes separados por "e"',
        preview: getNoivosPreview(),
        category: 'couple',
    },
    {
        key: '{data}',
        label: 'Data do Evento',
        description: 'Data formatada (ex: 15 de Março de 2025)',
        preview: getDataPreview(),
        category: 'event',
    },
    {
        key: '{data_curta}',
        label: 'Data Curta',
        description: 'Data curta (ex: 15/03/2025)',
        preview: getDataCurtaPreview(),
        category: 'event',
    },
    {
        key: '{local}',
        label: 'Local',
        description: 'Nome do local do evento',
        preview: props.wedding?.venue || 'Local do Evento',
        category: 'location',
    },
    {
        key: '{cidade}',
        label: 'Cidade',
        description: 'Cidade do evento',
        preview: props.wedding?.city || 'Cidade',
        category: 'location',
    },
    {
        key: '{estado}',
        label: 'Estado',
        description: 'Estado do evento',
        preview: props.wedding?.state || 'Estado',
        category: 'location',
    },
    {
        key: '{cidade_estado}',
        label: 'Cidade - Estado',
        description: 'Cidade e estado combinados',
        preview: getCidadeEstadoPreview(),
        category: 'location',
    },
]);

/**
 * Group placeholders by category
 */
const groupedPlaceholders = computed(() => {
    const groups = {
        couple: { label: 'Casal', items: [] },
        event: { label: 'Evento', items: [] },
        location: { label: 'Localização', items: [] },
    };
    
    placeholders.value.forEach(p => {
        if (groups[p.category]) {
            groups[p.category].items.push(p);
        }
    });
    
    return groups;
});

/**
 * Get noivo preview from wedding data
 */
function getNoivoPreview() {
    const couple = props.wedding?.couple_members || [];
    if (couple.length > 0) {
        return couple[0]?.name || 'Noivo';
    }
    return 'Noivo';
}

/**
 * Get noiva preview from wedding data
 */
function getNoivaPreview() {
    const couple = props.wedding?.couple_members || [];
    if (couple.length > 1) {
        return couple[1]?.name || 'Noiva';
    }
    return 'Noiva';
}

/**
 * Get noivos preview from wedding data
 */
function getNoivosPreview() {
    const couple = props.wedding?.couple_members || [];
    if (couple.length === 0) {
        return 'Noivo e Noiva';
    }
    const names = couple.map(c => c.name);
    if (names.length === 1) {
        return names[0];
    }
    if (names.length === 2) {
        return names.join(' e ');
    }
    return names.slice(0, -1).join(', ') + ' e ' + names[names.length - 1];
}

/**
 * Get formatted date preview
 */
function getDataPreview() {
    if (!props.wedding?.wedding_date) {
        return '15 de Março de 2025';
    }
    const date = new Date(props.wedding.wedding_date);
    const months = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    return `${date.getDate()} de ${months[date.getMonth()]} de ${date.getFullYear()}`;
}

/**
 * Get short date preview
 */
function getDataCurtaPreview() {
    if (!props.wedding?.wedding_date) {
        return '15/03/2025';
    }
    const date = new Date(props.wedding.wedding_date);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    return `${day}/${month}/${date.getFullYear()}`;
}

/**
 * Get cidade-estado preview
 */
function getCidadeEstadoPreview() {
    const city = props.wedding?.city || 'Cidade';
    const state = props.wedding?.state || 'Estado';
    return `${city} - ${state}`;
}

/**
 * Handle placeholder click
 */
const handleInsert = (placeholder) => {
    emit('insert', placeholder.key);
};

/**
 * Copy placeholder to clipboard
 */
const copyToClipboard = async (placeholder) => {
    try {
        await navigator.clipboard.writeText(placeholder.key);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};
</script>

<template>
    <div class="placeholder-helper" :class="{ 'compact': compact }">
        <!-- Header -->
        <div v-if="!compact" class="helper-header">
            <h3 class="text-sm font-semibold text-gray-900">Placeholders Disponíveis</h3>
            <p class="text-xs text-gray-500 mt-1">
                Clique para inserir no campo ativo
            </p>
        </div>
        
        <!-- Compact Mode: Simple List -->
        <div v-if="compact" class="compact-list">
            <button
                v-for="p in placeholders"
                :key="p.key"
                type="button"
                @click="handleInsert(p)"
                class="compact-item"
                :title="p.description"
            >
                <span class="placeholder-key">{{ p.key }}</span>
            </button>
        </div>
        
        <!-- Full Mode: Grouped with Previews -->
        <div v-else class="grouped-list">
            <div
                v-for="(group, groupKey) in groupedPlaceholders"
                :key="groupKey"
                class="placeholder-group"
            >
                <h4 class="group-label">{{ group.label }}</h4>
                
                <div class="group-items">
                    <div
                        v-for="p in group.items"
                        :key="p.key"
                        class="placeholder-item"
                    >
                        <div class="item-content">
                            <button
                                type="button"
                                @click="handleInsert(p)"
                                class="item-key"
                            >
                                {{ p.key }}
                            </button>
                            
                            <div class="item-info">
                                <span class="item-label">{{ p.label }}</span>
                                <span v-if="showPreview" class="item-preview">
                                    → {{ p.preview }}
                                </span>
                            </div>
                        </div>
                        
                        <button
                            type="button"
                            @click="copyToClipboard(p)"
                            class="copy-btn"
                            title="Copiar"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Help Text -->
        <div v-if="!compact" class="helper-footer">
            <p class="text-xs text-gray-400">
                Os placeholders serão substituídos pelos dados reais do casamento quando o site for visualizado.
            </p>
        </div>
    </div>
</template>

<style scoped>
.placeholder-helper {
    @apply bg-white border border-gray-200 rounded-lg;
}

.placeholder-helper.compact {
    @apply border-0 bg-transparent;
}

.helper-header {
    @apply px-4 py-3 border-b border-gray-100;
}

.compact-list {
    @apply flex flex-wrap gap-2;
}

.compact-item {
    @apply px-2 py-1 text-xs font-mono bg-wedding-50 text-wedding-700 rounded hover:bg-wedding-100 transition-colors;
}

.grouped-list {
    @apply divide-y divide-gray-100;
}

.placeholder-group {
    @apply p-4;
}

.group-label {
    @apply text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3;
}

.group-items {
    @apply space-y-2;
}

.placeholder-item {
    @apply flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 transition-colors;
}

.item-content {
    @apply flex items-center space-x-3 min-w-0 flex-1;
}

.item-key {
    @apply px-2 py-1 text-xs font-mono bg-wedding-100 text-wedding-700 rounded hover:bg-wedding-200 transition-colors cursor-pointer;
}

.item-info {
    @apply flex flex-col min-w-0;
}

.item-label {
    @apply text-sm text-gray-700;
}

.item-preview {
    @apply text-xs text-gray-400 truncate;
}

.copy-btn {
    @apply p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors;
}

.helper-footer {
    @apply px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-lg;
}

/* Wedding theme colors */
.bg-wedding-50 {
    background-color: #faf7f5;
}

.bg-wedding-100 {
    background-color: #fde8ee;
}

.bg-wedding-200 {
    background-color: #ebe0d6;
}

.text-wedding-700 {
    color: #b9163a;
}
</style>
