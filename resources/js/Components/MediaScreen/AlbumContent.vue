<template>
  <div class="flex flex-col w-full h-full overflow-hidden bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
    <!-- Selection bar (sticky at top) -->
    <MediaSelectionBar
      v-if="isSelectionMode"
      :selected-count="selectedCount"
      :albums="albums"
      :current-album-id="album.id"
      @move-selected="handleMoveSelected"
      @delete-selected="handleDeleteSelected"
      @cancel-selection="handleCancelSelection"
    />

    <!-- Scrollable content area -->
    <div class="flex-1 overflow-y-auto p-6">
      <!-- Upload Area Section -->
      <div class="w-full flex-shrink-0 mb-8">
        <UploadArea
          :album-id="album.id"
          @upload-started="handleUploadStarted"
          @upload-completed="handleUploadCompleted"
          @upload-failed="handleUploadFailed"
        />
      </div>

      <!-- Media Gallery Section -->
      <div class="w-full flex-1 min-h-0">
        <MediaGallery
          :media="album.media"
          :is-selection-mode="isSelectionMode"
          :is-selected="isSelected"
          @delete-media="handleDeleteMedia"
          @toggle-selection="handleToggleSelection"
          @move-media="handleMoveSingleMedia"
        />
      </div>
    </div>

    <!-- Move Media Modal -->
    <MoveMediaModal
      :is-open="showMoveModal"
      :albums="albums"
      :current-album-id="album.id"
      :selected-count="selectedMediaForMove.length"
      :preview-media="previewMedia"
      @confirm="handleConfirmMove"
      @cancel="handleCancelMove"
    />

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      :is-open="showDeleteConfirm"
      title="Confirmar exclusão"
      :message="deleteConfirmMessage"
      confirm-label="Excluir"
      cancel-label="Cancelar"
      @confirm="handleConfirmDelete"
      @cancel="handleCancelDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { AlbumContentProps, AlbumContentEvents, Media, UploadError, Album } from '@/types/media-screen';
import { useNotifications } from '@/Composables/useNotifications';
import { useMediaSelection } from '@/Composables/useMediaSelection';
import { useMediaGallery } from '@/Composables/useMediaGallery';
import UploadArea from './UploadArea.vue';
import MediaGallery from './MediaGallery.vue';
import MediaSelectionBar from './MediaSelectionBar.vue';
import MoveMediaModal from './MoveMediaModal.vue';
import ConfirmDialog from './ConfirmDialog.vue';

/**
 * AlbumContent Component
 * 
 * Orchestrates the content area for the selected album.
 * Integrates UploadArea, MediaGallery, selection mode, and bulk operations.
 * Manages events for upload, deletion, and moving operations.
 * 
 * Layout: Vertical layout with selection bar (sticky), UploadArea, and MediaGallery.
 * 
 * @Requirements: 2.3, 4.1, 6.4, Fase 1
 * - Requisito 2.3: Display album content in right column with flexible width
 * - Requisito 4.1: Display upload area in upper section
 * - Requisito 6.4: Display gallery in lower section when album contains media
 * - Fase 1: Seleção múltipla + menu de ações
 */

// Props
interface ExtendedAlbumContentProps extends AlbumContentProps {
  albums?: Album[];
}

const props = withDefaults(defineProps<ExtendedAlbumContentProps>(), {
  albums: () => []
});

// Events
const emit = defineEmits<AlbumContentEvents>();

// Composables
const { show: showNotification } = useNotifications();
const { moveMedia } = useMediaGallery();
const {
  selectedMediaIds,
  isSelectionMode,
  selectedCount,
  isSelected,
  toggleSelection,
  clearSelection,
  enterSelectionMode,
  exitSelectionMode
} = useMediaSelection();

// Local state
const showMoveModal = ref(false);
const showDeleteConfirm = ref(false);
const selectedMediaForMove = ref<string[]>([]);

/**
 * Preview media for modal (first 3 selected items)
 */
const previewMedia = computed(() => {
  const selectedIds = Array.from(selectedMediaIds.value);
  return props.album.media
    .filter(m => selectedIds.includes(m.id))
    .slice(0, 3);
});

/**
 * Delete confirmation message
 */
const deleteConfirmMessage = computed(() => {
  const count = selectedMediaIds.value.size;
  if (count === 1) {
    return 'Tem certeza que deseja excluir esta foto? Esta ação não pode ser desfeita.';
  }
  return `Tem certeza que deseja excluir ${count} fotos? Esta ação não pode ser desfeita.`;
});

/**
 * Handle upload started event from UploadArea
 */
const handleUploadStarted = (files: File[]): void => {
  // Upload started - no action needed
};

/**
 * Handle upload completed event from UploadArea
 */
const handleUploadCompleted = (media: Media[]): void => {
  emit('media-uploaded', media);
};

/**
 * Handle upload failed event from UploadArea
 */
const handleUploadFailed = (error: UploadError): void => {
  console.error(`Upload failed for album ${props.album.id}:`, error.message, error.code);
};

/**
 * Handle delete media event from MediaGallery
 */
const handleDeleteMedia = (mediaId: string): void => {
  emit('media-deleted', mediaId);
};

/**
 * Handle toggle selection event from MediaGallery
 */
const handleToggleSelection = (mediaId: string): void => {
  // Toggle specific media selection
  if (!isSelectionMode.value && mediaId !== '') {
    enterSelectionMode();
  }
  if (mediaId !== '') {
    toggleSelection(mediaId);
  }
};

/**
 * Handle move single media (from individual item button)
 */
const handleMoveSingleMedia = (mediaId: string): void => {
  selectedMediaForMove.value = [mediaId];
  showMoveModal.value = true;
};

/**
 * Handle move selected (from selection bar)
 */
const handleMoveSelected = (): void => {
  selectedMediaForMove.value = Array.from(selectedMediaIds.value);
  showMoveModal.value = true;
};

/**
 * Handle delete selected (from selection bar)
 */
const handleDeleteSelected = (): void => {
  showDeleteConfirm.value = true;
};

/**
 * Handle cancel selection (from selection bar)
 */
const handleCancelSelection = (): void => {
  exitSelectionMode();
};

/**
 * Handle confirm move from modal
 */
const handleConfirmMove = async (targetAlbumId: string): Promise<void> => {
  try {
    const movedIds = [...selectedMediaForMove.value];
    
    // Call API to move media
    await moveMedia(movedIds, targetAlbumId);
    
    // Emit event to remove moved media from current album and update target album count
    emit('media-moved', movedIds, targetAlbumId);
    
    showNotification(
      `${movedIds.length} foto(s) movida(s) com sucesso`,
      'success'
    );
    
    showMoveModal.value = false;
    selectedMediaForMove.value = [];
    
    if (isSelectionMode.value) {
      exitSelectionMode();
    }
  } catch (error: any) {
    showNotification(
      error.message || 'Erro ao mover fotos',
      'error'
    );
  }
};

/**
 * Handle cancel move from modal
 */
const handleCancelMove = (): void => {
  showMoveModal.value = false;
  selectedMediaForMove.value = [];
};

/**
 * Handle confirm delete from dialog
 */
const handleConfirmDelete = async (): Promise<void> => {
  try {
    const mediaIds = Array.from(selectedMediaIds.value);
    
    // TODO: Implement API call to delete multiple media
    
    showNotification(
      `${mediaIds.length} foto(s) excluída(s) com sucesso`,
      'success'
    );
    
    showDeleteConfirm.value = false;
    exitSelectionMode();
    
    // Emit delete events for each media
    mediaIds.forEach(id => emit('media-deleted', id));
  } catch (error: any) {
    showNotification(
      error.message || 'Erro ao excluir fotos',
      'error'
    );
  }
};

/**
 * Handle cancel delete from dialog
 */
const handleCancelDelete = (): void => {
  showDeleteConfirm.value = false;
};
</script>
