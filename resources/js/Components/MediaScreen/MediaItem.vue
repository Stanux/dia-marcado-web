<template>
  <div 
    class="media-item"
    :class="{ 'selected': isSelected }"
    @click="handleItemClick"
  >
    <!-- Checkbox for selection -->
    <MediaItemCheckbox
      :is-selected="isSelected"
      :is-selection-mode="isSelectionMode"
      @toggle="handleToggleSelection"
    />

    <!-- Thumbnail rendering based on media type -->
    <div class="media-thumbnail-container">
      <img 
        v-if="media.type === 'image'"
        :src="media.thumbnail_url"
        :alt="media.filename"
        class="media-thumbnail"
        loading="lazy"
      />
      <video 
        v-else
        :src="media.thumbnail_url"
        class="media-thumbnail"
        preload="metadata"
      />
    </div>

    <!-- Action buttons with hover state -->
    <div v-if="!isSelectionMode" class="media-actions">
      <button 
        class="action-btn move-btn"
        type="button"
        aria-label="Mover mídia"
        @click.stop="handleMoveClick"
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
        Mover
      </button>

      <button 
        class="action-btn delete-btn"
        type="button"
        aria-label="Excluir mídia"
        @click.stop="handleDeleteClick"
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
        Excluir
      </button>
    </div>

    <!-- Confirmation Dialog -->
    <ConfirmDialog
      :is-open="showConfirmDialog"
      title="Confirmar exclusão"
      message="Tem certeza que deseja excluir esta mídia? Esta ação não pode ser desfeita."
      confirm-label="Excluir"
      cancel-label="Cancelar"
      @confirm="handleConfirmDelete"
      @cancel="handleCancelDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import ConfirmDialog from './ConfirmDialog.vue';
import MediaItemCheckbox from './MediaItemCheckbox.vue';
import type { MediaItemProps, MediaItemEvents } from '@/types/media-screen';

/**
 * MediaItem Component
 * 
 * Represents a single media item (image or video) in the gallery.
 * Displays a thumbnail and provides action buttons (move, delete).
 * Supports selection mode with checkbox overlay.
 * Integrates confirmation dialog before deletion.
 * 
 * @Requirements: 6.2, 6.3, 7.1, 7.2, 7.3, Fase 1
 * - Requisito 6.2: Displays media thumbnails
 * - Requisito 6.3: Supports different aspect ratios
 * - Requisito 7.1: Displays action buttons for each media
 * - Requisito 7.2: Shows confirmation dialog before deletion
 * - Requisito 7.3: Executes deletion only after confirmation
 * - Fase 1: Selection mode with checkbox
 */

interface ExtendedMediaItemProps extends MediaItemProps {
  isSelected?: boolean;
  isSelectionMode?: boolean;
}

const props = withDefaults(defineProps<ExtendedMediaItemProps>(), {
  isSelected: false,
  isSelectionMode: false
});

const emit = defineEmits<MediaItemEvents>();

/**
 * Local state to control confirmation dialog visibility
 */
const showConfirmDialog = ref(false);

/**
 * Handle item click - toggle selection in selection mode
 */
function handleItemClick(): void {
  if (props.isSelectionMode) {
    handleToggleSelection();
  }
}

/**
 * Handle toggle selection
 */
function handleToggleSelection(): void {
  emit('toggle-selection', props.media.id);
}

/**
 * Handle move button click
 */
function handleMoveClick(): void {
  emit('move', props.media.id);
}

/**
 * Handle delete button click
 * Opens the confirmation dialog instead of immediately emitting delete event
 */
function handleDeleteClick(): void {
  showConfirmDialog.value = true;
}

/**
 * Handle confirmation of deletion
 * Emits delete event with media ID and closes the dialog
 */
function handleConfirmDelete(): void {
  showConfirmDialog.value = false;
  emit('delete', props.media.id);
}

/**
 * Handle cancellation of deletion
 * Simply closes the dialog without emitting delete event
 */
function handleCancelDelete(): void {
  showConfirmDialog.value = false;
}
</script>

<style scoped>
.media-item {
  position: relative;
  border-radius: 0.75rem;
  overflow: hidden;
  background-color: #f3f4f6;
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  aspect-ratio: 1 / 1;
  cursor: pointer;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

/* Selected state */
.media-item.selected {
  outline: 3px solid #3b82f6;
  outline-offset: -3px;
  transform: scale(0.95);
}

/* Hover effect on entire item */
.media-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Mostrar checkbox no hover */
.media-item:hover .media-checkbox-container {
  opacity: 1;
}

/* Sempre mostrar checkbox quando selecionado */
.media-item.selected .media-checkbox-container {
  opacity: 1 !important;
}

.media-item.selected:hover {
  transform: scale(0.95) translateY(-2px);
}

/* Thumbnail container - maintains aspect ratio */
.media-thumbnail-container {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

/* Thumbnail styling - supports different aspect ratios */
.media-thumbnail {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
}

/* Actions overlay - hidden by default, shown on hover */
.media-actions {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background-color: rgba(0, 0, 0, 0);
  opacity: 0;
  transition: opacity 0.2s ease-in-out, background-color 0.2s ease-in-out;
  pointer-events: none;
}

.media-item:hover .media-actions {
  opacity: 1;
  background-color: rgba(0, 0, 0, 0.5);
  pointer-events: auto;
}

/* Action button styling */
.action-btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.action-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.action-btn:active {
  transform: scale(0.98);
}

.action-btn:focus {
  outline: 2px solid white;
  outline-offset: 2px;
}

.btn-icon {
  width: 1rem;
  height: 1rem;
}

/* Move button */
.move-btn {
  background-color: #3b82f6;
  color: white;
}

.move-btn:hover {
  background-color: #2563eb;
}

.move-btn:active {
  background-color: #1d4ed8;
}

/* Delete button */
.delete-btn {
  background-color: #ef4444;
  color: white;
}

.delete-btn:hover {
  background-color: #dc2626;
}

.delete-btn:active {
  background-color: #b91c1c;
}

/* Responsive adjustments for smaller screens */
@media (max-width: 640px) {
  .action-btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
  }

  .btn-icon {
    width: 0.875rem;
    height: 0.875rem;
  }
}
</style>
