<template>
  <div class="media-item">
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

    <!-- Delete button with hover state -->
    <div class="media-actions">
      <button 
        class="delete-btn"
        type="button"
        aria-label="Excluir mídia"
        @click="handleDeleteClick"
      >
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
import type { MediaItemProps, MediaItemEvents } from '@/types/media-screen';

/**
 * MediaItem Component
 * 
 * Represents a single media item (image or video) in the gallery.
 * Displays a thumbnail and provides a delete action button.
 * Supports different aspect ratios without distortion.
 * Integrates confirmation dialog before deletion.
 * 
 * @Requirements: 6.2, 6.3, 7.1, 7.2, 7.3
 * - Requisito 6.2: Displays media thumbnails
 * - Requisito 6.3: Supports different aspect ratios
 * - Requisito 7.1: Displays delete button for each media
 * - Requisito 7.2: Shows confirmation dialog before deletion
 * - Requisito 7.3: Executes deletion only after confirmation
 */

const props = defineProps<MediaItemProps>();

const emit = defineEmits<MediaItemEvents>();

/**
 * Local state to control confirmation dialog visibility
 */
const showConfirmDialog = ref(false);

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
  border-radius: 0.5rem;
  overflow: hidden;
  background-color: #f3f4f6;
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  aspect-ratio: 1 / 1;
}

/* Hover effect on entire item */
.media-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0);
  opacity: 0;
  transition: opacity 0.2s ease-in-out, background-color 0.2s ease-in-out;
  pointer-events: none;
}

.media-item:hover .media-actions {
  opacity: 1;
  background-color: rgba(0, 0, 0, 0.4);
  pointer-events: auto;
}

/* Delete button styling */
.delete-btn {
  padding: 0.5rem 1rem;
  background-color: #ef4444;
  color: white;
  border: none;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.delete-btn:hover {
  background-color: #dc2626;
  transform: scale(1.05);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.delete-btn:active {
  transform: scale(0.98);
  background-color: #b91c1c;
}

.delete-btn:focus {
  outline: 2px solid white;
  outline-offset: 2px;
}

/* Responsive adjustments for smaller screens */
@media (max-width: 640px) {
  .delete-btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
  }
}
</style>
