<template>
  <Transition name="slide-down">
    <div v-if="selectedCount > 0" class="selection-bar">
      <div class="selection-bar-content">
        <!-- Selection count -->
        <div class="selection-info">
          <span class="selection-count">{{ selectedCount }}</span>
          <span class="selection-text">
            {{ selectedCount === 1 ? 'foto selecionada' : 'fotos selecionadas' }}
          </span>
        </div>

        <!-- Action buttons -->
        <div class="selection-actions">
          <button
            type="button"
            class="action-btn move-btn"
            @click="handleMoveClick"
            aria-label="Mover fotos selecionadas"
          >
            <svg 
              xmlns="http://www.w3.org/2000/svg" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke-width="1.5" 
              stroke="currentColor" 
              class="btn-icon"
            >
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" 
              />
            </svg>
            <span>Mover para...</span>
          </button>

          <button
            type="button"
            class="action-btn delete-btn"
            @click="handleDeleteClick"
            aria-label="Excluir fotos selecionadas"
          >
            <svg 
              xmlns="http://www.w3.org/2000/svg" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke-width="1.5" 
              stroke="currentColor" 
              class="btn-icon"
            >
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" 
              />
            </svg>
            <span>Excluir</span>
          </button>

          <button
            type="button"
            class="action-btn cancel-btn"
            @click="handleCancelClick"
            aria-label="Cancelar seleção"
          >
            <svg 
              xmlns="http://www.w3.org/2000/svg" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke-width="1.5" 
              stroke="currentColor" 
              class="btn-icon"
            >
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                d="M6 18L18 6M6 6l12 12" 
              />
            </svg>
            <span>Cancelar</span>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import type { MediaSelectionBarProps, MediaSelectionBarEvents } from '@/types/media-screen';

/**
 * MediaSelectionBar Component
 * 
 * Displays a floating action bar when media items are selected.
 * Provides buttons for bulk operations: move, delete, and cancel selection.
 * Animates in/out smoothly when selection state changes.
 * 
 * @Requirements: Fase 1 - Menu de ações em lote
 */

const props = defineProps<MediaSelectionBarProps>();

const emit = defineEmits<MediaSelectionBarEvents>();

/**
 * Handle move button click
 */
function handleMoveClick(): void {
  emit('move-selected');
}

/**
 * Handle delete button click
 */
function handleDeleteClick(): void {
  emit('delete-selected');
}

/**
 * Handle cancel button click
 */
function handleCancelClick(): void {
  emit('cancel-selection');
}
</script>

<style scoped>
.selection-bar {
  position: sticky;
  top: 0;
  left: 0;
  right: 0;
  z-index: 20;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.selection-bar-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  max-width: 100%;
}

/* Selection info */
.selection-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: white;
}

.selection-count {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
}

.selection-text {
  font-size: 0.875rem;
  font-weight: 500;
  opacity: 0.9;
}

/* Action buttons */
.selection-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  white-space: nowrap;
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
}

.action-btn:active {
  transform: translateY(0);
}

.btn-icon {
  width: 1.25rem;
  height: 1.25rem;
}

/* Move button */
.move-btn {
  background-color: white;
  color: #3b82f6;
}

.move-btn:hover {
  background-color: #f0f9ff;
}

/* Delete button */
.delete-btn {
  background-color: #ef4444;
  color: white;
}

.delete-btn:hover {
  background-color: #dc2626;
}

/* Cancel button */
.cancel-btn {
  background-color: rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(4px);
}

.cancel-btn:hover {
  background-color: rgba(255, 255, 255, 0.3);
}

/* Animations */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-out;
}

.slide-down-enter-from {
  transform: translateY(-100%);
  opacity: 0;
}

.slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .selection-bar-content {
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }

  .selection-info {
    width: 100%;
    justify-content: center;
  }

  .selection-actions {
    width: 100%;
    justify-content: center;
    flex-wrap: wrap;
  }

  .action-btn {
    flex: 1;
    min-width: fit-content;
    justify-content: center;
  }

  .action-btn span {
    display: none;
  }

  .btn-icon {
    width: 1.5rem;
    height: 1.5rem;
  }
}

@media (max-width: 640px) {
  .selection-count {
    font-size: 1.25rem;
  }

  .selection-text {
    font-size: 0.8125rem;
  }

  .action-btn {
    padding: 0.625rem;
  }
}
</style>
