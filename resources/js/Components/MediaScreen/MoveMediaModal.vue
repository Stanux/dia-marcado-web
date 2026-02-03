<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="modal-overlay" @click="handleOverlayClick">
        <div class="modal-container" @click.stop>
          <!-- Modal header -->
          <div class="modal-header">
            <h2 class="modal-title">Mover fotos</h2>
            <button
              type="button"
              class="close-btn"
              @click="handleCancel"
              aria-label="Fechar"
            >
              <svg 
                xmlns="http://www.w3.org/2000/svg" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke-width="1.5" 
                stroke="currentColor" 
                class="close-icon"
              >
                <path 
                  stroke-linecap="round" 
                  stroke-linejoin="round" 
                  d="M6 18L18 6M6 6l12 12" 
                />
              </svg>
            </button>
          </div>

          <!-- Modal body -->
          <div class="modal-body">
            <!-- Selection summary -->
            <div class="selection-summary">
              <div class="summary-text">
                <span class="summary-count">{{ selectedCount }}</span>
                <span class="summary-label">
                  {{ selectedCount === 1 ? 'foto será movida' : 'fotos serão movidas' }}
                </span>
              </div>

              <!-- Preview thumbnails -->
              <div v-if="previewMedia && previewMedia.length > 0" class="preview-thumbnails">
                <img
                  v-for="(media, index) in previewMedia.slice(0, 3)"
                  :key="media.id"
                  :src="media.thumbnail_url"
                  :alt="media.filename"
                  class="preview-thumb"
                  :style="{ zIndex: 3 - index }"
                />
                <div v-if="selectedCount > 3" class="preview-more">
                  +{{ selectedCount - 3 }}
                </div>
              </div>
            </div>

            <!-- Albums list title -->
            <div class="albums-list-title">
              Selecione o álbum de destino:
            </div>

            <!-- Albums list -->
            <div class="albums-list">
              <!-- Empty state -->
              <div v-if="!albums || albums.length === 0" class="empty-albums">
                <svg 
                  xmlns="http://www.w3.org/2000/svg" 
                  fill="none" 
                  viewBox="0 0 24 24" 
                  stroke-width="1.5" 
                  stroke="currentColor" 
                  class="empty-icon"
                >
                  <path 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" 
                  />
                </svg>
                <p class="empty-text">Nenhum álbum disponível</p>
                <p class="empty-subtext">Crie um álbum primeiro para poder mover fotos</p>
              </div>

              <!-- Albums -->
              <template v-else>
                <div
                  v-for="album in albums"
                  :key="album.id"
                  class="album-item"
                  :class="{ 
                    'disabled': album.id === currentAlbumId,
                    'selected': selectedAlbumId === album.id
                  }"
                  @click="handleAlbumSelect(album.id)"
                >
                <div class="album-icon">
                  <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke-width="1.5" 
                    stroke="currentColor"
                  >
                    <path 
                      stroke-linecap="round" 
                      stroke-linejoin="round" 
                      d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" 
                    />
                  </svg>
                </div>

                <div class="album-info">
                  <div class="album-name">{{ album.name }}</div>
                  <div class="album-count">{{ album.media_count }} fotos</div>
                </div>

                <div v-if="album.id === currentAlbumId" class="current-badge">
                  Atual
                </div>

                <div v-if="selectedAlbumId === album.id" class="check-icon">
                  <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    viewBox="0 0 24 24" 
                    fill="currentColor"
                  >
                    <path 
                      fill-rule="evenodd" 
                      d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" 
                      clip-rule="evenodd" 
                    />
                  </svg>
                </div>
              </div>
              </template>
            </div>
          </div>

          <!-- Modal footer -->
          <div class="modal-footer">
            <button
              type="button"
              class="footer-btn cancel-btn"
              @click="handleCancel"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="footer-btn confirm-btn"
              :disabled="!selectedAlbumId || selectedAlbumId === currentAlbumId"
              @click="handleConfirm"
            >
              Mover fotos
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { MoveMediaModalProps, MoveMediaModalEvents } from '@/types/media-screen';

/**
 * MoveMediaModal Component
 * 
 * Modal dialog for selecting destination album when moving media.
 * Displays list of available albums with scroll.
 * Shows preview of selected media and prevents moving to current album.
 * 
 * @Requirements: Fase 1 - Modal de seleção de álbum destino
 */

const props = defineProps<MoveMediaModalProps>();

const emit = defineEmits<MoveMediaModalEvents>();

// Local state
const selectedAlbumId = ref<string | null>(null);

/**
 * Handle album selection
 */
function handleAlbumSelect(albumId: string): void {
  if (albumId === props.currentAlbumId) {
    return; // Cannot select current album
  }
  selectedAlbumId.value = albumId;
}

/**
 * Handle confirm button click
 */
function handleConfirm(): void {
  if (selectedAlbumId.value && selectedAlbumId.value !== props.currentAlbumId) {
    emit('confirm', selectedAlbumId.value);
    resetModal();
  }
}

/**
 * Handle cancel button click
 */
function handleCancel(): void {
  emit('cancel');
  resetModal();
}

/**
 * Handle overlay click (close modal)
 */
function handleOverlayClick(): void {
  handleCancel();
}

/**
 * Reset modal state
 */
function resetModal(): void {
  selectedAlbumId.value = null;
}
</script>

<style scoped>
/* Modal overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  padding: 1rem;
}

/* Modal container */
.modal-container {
  background-color: white;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-width: 32rem;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Modal header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.close-btn {
  padding: 0.5rem;
  border: none;
  background: none;
  color: #6b7280;
  cursor: pointer;
  border-radius: 0.375rem;
  transition: all 0.2s;
}

.close-btn:hover {
  background-color: #f3f4f6;
  color: #111827;
}

.close-icon {
  width: 1.25rem;
  height: 1.25rem;
}

/* Modal body */
.modal-body {
  flex: 1;
  overflow: hidden;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
}

/* Selection summary */
.selection-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border-radius: 0.75rem;
  margin-bottom: 1.5rem;
  flex-shrink: 0;
}

.summary-text {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.summary-count {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e40af;
}

.summary-label {
  font-size: 0.875rem;
  color: #1e40af;
}

/* Preview thumbnails */
.preview-thumbnails {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.preview-thumb {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.375rem;
  object-fit: cover;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-left: -0.5rem;
}

.preview-thumb:first-child {
  margin-left: 0;
}

.preview-more {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.375rem;
  background-color: #3b82f6;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-left: -0.5rem;
}

/* Albums list title */
.albums-list-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.75rem;
  flex-shrink: 0;
}

/* Albums list */
.albums-list {
  flex: 1;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  min-height: 0;
}

/* Custom scrollbar */
.albums-list::-webkit-scrollbar {
  width: 8px;
}

.albums-list::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 0 0.5rem 0.5rem 0;
}

.albums-list::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.albums-list::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.album-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  cursor: pointer;
  transition: all 0.2s;
}

.album-item:last-child {
  border-bottom: none;
}

.album-item:hover:not(.disabled) {
  background-color: #f9fafb;
}

.album-item.selected {
  background-color: #eff6ff;
  border-left: 3px solid #3b82f6;
}

.album-item.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.album-icon {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f3f4f6;
  border-radius: 0.5rem;
  color: #6b7280;
  flex-shrink: 0;
}

.album-icon svg {
  width: 1.5rem;
  height: 1.5rem;
}

.album-info {
  flex: 1;
  min-width: 0;
}

.album-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.album-count {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.125rem;
}

.current-badge {
  padding: 0.25rem 0.5rem;
  background-color: #fef3c7;
  color: #92400e;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 0.25rem;
}

.check-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #3b82f6;
  flex-shrink: 0;
}

/* Empty state */
.empty-albums {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  text-align: center;
}

.empty-icon {
  width: 3rem;
  height: 3rem;
  color: #d1d5db;
  margin-bottom: 0.5rem;
}

.empty-text {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  margin: 0 0 0.25rem 0;
}

.empty-subtext {
  font-size: 0.75rem;
  color: #9ca3af;
  margin: 0;
}

/* Modal footer */
.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.footer-btn {
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.cancel-btn {
  background-color: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.cancel-btn:hover {
  background-color: #f9fafb;
}

.confirm-btn {
  background-color: #3b82f6;
  color: white;
}

.confirm-btn:hover:not(:disabled) {
  background-color: #2563eb;
}

.confirm-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.95);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .modal-container {
    max-height: 95vh;
    border-radius: 0.75rem;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
  }

  .selection-summary {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .preview-thumbnails {
    align-self: flex-end;
  }
}
</style>
