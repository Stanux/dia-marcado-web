<script setup>
/**
 * NavigationEditor Component
 * 
 * Editable list of navigation menu items with drag & drop reordering.
 * Each item has label, type (anchor/URL/action), and destination.
 * 
 * @Requirements: 8.1
 */
import { ref, watch, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    maxItems: {
        type: Number,
        default: 10,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

// Local copy of navigation items
const items = ref([...props.modelValue]);

// Drag state
const draggedIndex = ref(null);
const dragOverIndex = ref(null);

/**
 * Watch for external value changes
 */
watch(() => props.modelValue, (newValue) => {
    items.value = [...newValue];
}, { deep: true });

/**
 * Emit changes to parent
 */
const emitChange = () => {
    emit('update:modelValue', [...items.value]);
};

/**
 * Add new navigation item
 */
const addItem = () => {
    if (items.value.length >= props.maxItems) return;
    
    items.value.push({
        label: '',
        type: 'anchor',
        target: '',
    });
    emitChange();
};

/**
 * Remove navigation item
 */
const removeItem = (index) => {
    items.value.splice(index, 1);
    emitChange();
};

/**
 * Update item field
 */
const updateItem = (index, field, value) => {
    if (items.value[index]) {
        items.value[index][field] = value;
        emitChange();
    }
};

/**
 * Move item up
 */
const moveUp = (index) => {
    if (index > 0) {
        const item = items.value.splice(index, 1)[0];
        items.value.splice(index - 1, 0, item);
        emitChange();
    }
};

/**
 * Move item down
 */
const moveDown = (index) => {
    if (index < items.value.length - 1) {
        const item = items.value.splice(index, 1)[0];
        items.value.splice(index + 1, 0, item);
        emitChange();
    }
};

/**
 * Handle drag start
 */
const handleDragStart = (event, index) => {
    draggedIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', index);
    
    // Add dragging class after a short delay
    setTimeout(() => {
        event.target.classList.add('dragging');
    }, 0);
};

/**
 * Handle drag end
 */
const handleDragEnd = (event) => {
    event.target.classList.remove('dragging');
    draggedIndex.value = null;
    dragOverIndex.value = null;
};

/**
 * Handle drag over
 */
const handleDragOver = (event, index) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    dragOverIndex.value = index;
};

/**
 * Handle drag leave
 */
const handleDragLeave = () => {
    dragOverIndex.value = null;
};

/**
 * Handle drop
 */
const handleDrop = (event, dropIndex) => {
    event.preventDefault();
    
    const dragIndex = draggedIndex.value;
    if (dragIndex === null || dragIndex === dropIndex) {
        dragOverIndex.value = null;
        return;
    }
    
    // Reorder items
    const item = items.value.splice(dragIndex, 1)[0];
    items.value.splice(dropIndex, 0, item);
    
    draggedIndex.value = null;
    dragOverIndex.value = null;
    emitChange();
};

/**
 * Get placeholder text based on item type
 */
const getTargetPlaceholder = (type) => {
    switch (type) {
        case 'anchor':
            return '#hero, #rsvp, #galeria';
        case 'url':
            return 'https://exemplo.com';
        case 'action':
            return 'rsvp, contact';
        default:
            return '';
    }
};

/**
 * Get type label
 */
const getTypeLabel = (type) => {
    switch (type) {
        case 'anchor':
            return 'Âncora';
        case 'url':
            return 'URL';
        case 'action':
            return 'Ação';
        default:
            return type;
    }
};

/**
 * Check if can add more items
 */
const canAddMore = computed(() => items.value.length < props.maxItems);
</script>

<template>
    <div class="navigation-editor" :class="{ 'disabled': disabled }">
        <!-- Header -->
        <div class="editor-header">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Menu de Navegação</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Arraste para reordenar • {{ items.length }}/{{ maxItems }} itens
                </p>
            </div>
            
            <button
                type="button"
                @click="addItem"
                :disabled="!canAddMore || disabled"
                class="add-btn"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Adicionar
            </button>
        </div>
        
        <!-- Empty State -->
        <div v-if="items.length === 0" class="empty-state">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
            <p class="text-sm text-gray-500">Nenhum item de navegação</p>
            <button
                type="button"
                @click="addItem"
                :disabled="disabled"
                class="mt-3 text-sm text-wedding-600 hover:text-wedding-700 font-medium"
            >
                + Adicionar primeiro item
            </button>
        </div>
        
        <!-- Navigation Items List -->
        <div v-else class="items-list">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="nav-item"
                :class="{
                    'drag-over': dragOverIndex === index,
                    'dragging': draggedIndex === index,
                }"
                draggable="true"
                @dragstart="handleDragStart($event, index)"
                @dragend="handleDragEnd"
                @dragover="handleDragOver($event, index)"
                @dragleave="handleDragLeave"
                @drop="handleDrop($event, index)"
            >
                <!-- Drag Handle -->
                <div class="drag-handle" title="Arraste para reordenar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    </svg>
                </div>
                
                <!-- Item Content -->
                <div class="item-content">
                    <!-- Label Input -->
                    <div class="input-group">
                        <label class="input-label">Rótulo</label>
                        <input
                            type="text"
                            :value="item.label"
                            @input="updateItem(index, 'label', $event.target.value)"
                            class="text-input"
                            placeholder="Ex: Sobre nós"
                            :disabled="disabled"
                        />
                    </div>
                    
                    <!-- Type Select -->
                    <div class="input-group type-select">
                        <label class="input-label">Tipo</label>
                        <select
                            :value="item.type"
                            @change="updateItem(index, 'type', $event.target.value)"
                            class="select-input"
                            :disabled="disabled"
                        >
                            <option value="anchor">Âncora (seção)</option>
                            <option value="url">URL externa</option>
                            <option value="action">Ação (RSVP)</option>
                        </select>
                    </div>
                    
                    <!-- Target Input -->
                    <div class="input-group target-input">
                        <label class="input-label">Destino</label>
                        <input
                            type="text"
                            :value="item.target"
                            @input="updateItem(index, 'target', $event.target.value)"
                            class="text-input"
                            :placeholder="getTargetPlaceholder(item.type)"
                            :disabled="disabled"
                        />
                    </div>
                </div>
                
                <!-- Item Actions -->
                <div class="item-actions">
                    <button
                        type="button"
                        @click="moveUp(index)"
                        :disabled="index === 0 || disabled"
                        class="action-btn"
                        title="Mover para cima"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                    
                    <button
                        type="button"
                        @click="moveDown(index)"
                        :disabled="index === items.length - 1 || disabled"
                        class="action-btn"
                        title="Mover para baixo"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <button
                        type="button"
                        @click="removeItem(index)"
                        :disabled="disabled"
                        class="action-btn delete"
                        title="Remover"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Help Text -->
        <div v-if="items.length > 0" class="help-text">
            <p class="text-xs text-gray-400">
                <strong>Âncora:</strong> Link para seção da página (#hero, #rsvp) •
                <strong>URL:</strong> Link externo •
                <strong>Ação:</strong> Abre modal ou executa ação
            </p>
        </div>
    </div>
</template>

<style scoped>
.navigation-editor {
    @apply space-y-4;
}

.navigation-editor.disabled {
    @apply opacity-60;
}

.editor-header {
    @apply flex items-center justify-between;
}

.add-btn {
    @apply flex items-center px-3 py-1.5 text-sm font-medium text-wedding-600 bg-wedding-50 rounded-md hover:bg-wedding-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.empty-state {
    @apply py-8 text-center border-2 border-dashed border-gray-200 rounded-lg;
}

.items-list {
    @apply space-y-3;
}

.nav-item {
    @apply flex items-start gap-3 p-4 bg-gray-50 rounded-lg border border-transparent transition-all;
}

.nav-item.drag-over {
    @apply border-wedding-400 bg-wedding-50;
}

.nav-item.dragging {
    @apply opacity-50;
}

.drag-handle {
    @apply flex-shrink-0 p-1 text-gray-400 cursor-grab hover:text-gray-600 active:cursor-grabbing;
}

.item-content {
    @apply flex-1 grid grid-cols-1 md:grid-cols-3 gap-3;
}

.input-group {
    @apply space-y-1;
}

.input-group.type-select {
    @apply md:col-span-1;
}

.input-group.target-input {
    @apply md:col-span-1;
}

.input-label {
    @apply block text-xs font-medium text-gray-600;
}

.text-input {
    @apply w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500;
}

.select-input {
    @apply w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-wedding-500 focus:border-wedding-500 bg-white;
}

.item-actions {
    @apply flex flex-col gap-1;
}

.action-btn {
    @apply p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded transition-colors disabled:opacity-30 disabled:cursor-not-allowed;
}

.action-btn.delete {
    @apply hover:text-red-500 hover:bg-red-50;
}

.help-text {
    @apply pt-2 border-t border-gray-100;
}

/* Wedding theme colors */
.text-wedding-600 {
    color: #a18072;
}

.text-wedding-700 {
    color: #8b6b5d;
}

.bg-wedding-50 {
    background-color: #faf7f5;
}

.bg-wedding-100 {
    background-color: #f5ebe4;
}

.border-wedding-400 {
    border-color: #c4a99a;
}

.focus\:ring-wedding-500:focus {
    --tw-ring-color: #b8998a;
}

.focus\:border-wedding-500:focus {
    border-color: #b8998a;
}
</style>
