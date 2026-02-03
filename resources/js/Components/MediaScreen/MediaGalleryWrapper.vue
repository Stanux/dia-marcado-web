<template>
  <div class="w-full min-h-[600px]">
    <div class="w-full min-h-[600px]">
      <div class="flex gap-6 w-full min-h-[600px] max-md:flex-col">
        <!-- Left Column: Album List -->
        <AlbumList 
          :albums="albums"
          :selected-album-id="selectedAlbum?.id"
          @album-selected="handleAlbumSelection"
          @create-album="handleCreateAlbum"
          @edit-album="handleEditAlbum"
          @delete-album="handleDeleteAlbum"
        />
        
        <!-- Right Column: Album Content or Empty State -->
        <div v-if="!selectedAlbum" class="flex flex-col w-full h-full overflow-y-auto p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
          <!-- Disabled Upload Area (same visual as real UploadArea) -->
          <div class="w-full flex-shrink-0 mb-8">
            <div class="w-full mb-8 opacity-50 pointer-events-none">
              <div class="border-2 border-dashed rounded-lg p-8 text-center border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                <div class="flex justify-center mb-4">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="w-12 h-12 text-gray-400 dark:text-gray-500"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
                    />
                  </svg>
                </div>
                
                <div class="text-gray-600 dark:text-gray-400">
                  <p class="text-base font-medium mb-1 text-gray-700 dark:text-gray-300">
                    Arraste arquivos ou clique para selecionar
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Imagens (JPEG, PNG, GIF) e vídeos (MP4, QuickTime) até 100MB
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State Message -->
          <div class="w-full flex-1 min-h-0">
            <div class="flex items-center justify-center h-full">
              <div class="empty-state text-center">
                <div class="flex justify-center mb-4">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="w-16 h-16 text-gray-400 dark:text-gray-500"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                    />
                  </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Nenhum álbum selecionado
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                  Crie ou selecione um álbum.
                </p>
              </div>
            </div>
          </div>
        </div>
        
        <AlbumContent
          v-else
          :album="selectedAlbum"
          :albums="albums"
          @media-uploaded="handleMediaUploaded"
          @media-deleted="handleMediaDeleted"
          @media-moved="handleMediaMoved"
        />
      </div>
      
      <!-- Notification Container -->
      <NotificationContainer />
      
      <!-- Create Album Modal -->
      <CreateAlbumModal 
        :is-open="isCreateModalOpen"
        @close="handleCloseModal"
        @create="handleCreateAlbumSubmit"
      />
      
      <!-- Edit Album Modal -->
      <EditAlbumModal 
        :is-open="isEditModalOpen"
        :album-id="editingAlbumId"
        :album-name="editingAlbumName"
        :album-type="editingAlbumType"
        @close="handleCloseEditModal"
        @update="handleUpdateAlbumSubmit"
      />
      
      <!-- Delete Album Modal -->
      <DeleteAlbumModal 
        :is-open="isDeleteModalOpen"
        :album-id="deletingAlbumId"
        :album-name="deletingAlbumName"
        @close="handleCloseDeleteModal"
        @confirm="handleDeleteAlbumConfirm"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Album, Media } from '@/types/media-screen';
import { useAlbums } from '@/Composables/useAlbums';
import { useMediaGallery } from '@/Composables/useMediaGallery';
import { useNotifications } from '@/Composables/useNotifications';
import AlbumList from '@/Components/MediaScreen/AlbumList.vue';
import AlbumContent from '@/Components/MediaScreen/AlbumContent.vue';
import EmptyState from '@/Components/MediaScreen/EmptyState.vue';
import NotificationContainer from '@/Components/MediaScreen/NotificationContainer.vue';
import CreateAlbumModal from '@/Components/MediaScreen/CreateAlbumModal.vue';
import EditAlbumModal from '@/Components/MediaScreen/EditAlbumModal.vue';
import DeleteAlbumModal from '@/Components/MediaScreen/DeleteAlbumModal.vue';

/**
 * MediaGalleryWrapper Component
 * 
 * Wrapper component for integrating the MediaScreen functionality
 * within Filament pages. This component removes the header and layout
 * elements from MediaScreen.vue since Filament provides its own layout.
 * 
 * @Requirements: 1.1, 2.1, 2.2, 2.3, 3.3, 3.4, 3.6
 */

// Props
interface Props {
  albums: Album[];
}

const props = defineProps<Props>();

// Use composable for album management
const {
  albums,
  selectedAlbum,
  isLoading,
  selectAlbum,
  createAlbum,
  updateAlbum,
  deleteAlbum,
  refreshAlbums
} = useAlbums(props.albums);

// Use composable for media gallery operations
const { deleteMedia } = useMediaGallery();

// Use composable for notifications
const { show: showNotification } = useNotifications();

// Modal states
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);

// Edit modal data
const editingAlbumId = ref('');
const editingAlbumName = ref('');
const editingAlbumType = ref('');

// Delete modal data
const deletingAlbumId = ref('');
const deletingAlbumName = ref('');

/**
 * Handle album selection
 * Updates the selected album and displays its content
 * 
 * @Requirements: 3.3 - Highlight selected album visually
 * @Requirements: 3.4 - Load and display media from selected album
 */
const handleAlbumSelection = (albumId: string): void => {
  selectAlbum(albumId);
};

/**
 * Handle album creation button click
 * Opens the create album modal
 * 
 * @Requirements: 3.6 - Allow creation of new album with custom name
 */
const handleCreateAlbum = (): void => {
  isCreateModalOpen.value = true;
};

/**
 * Handle album creation from modal
 * Creates a new album with the provided data
 * 
 * @Requirements: 3.6 - Allow creation of new album with custom name
 */
const handleCreateAlbumSubmit = async (data: { name: string; type: string }): Promise<void> => {
  try {
    // Create album via composable
    const newAlbum = await createAlbum(data.name, data.type);
    
    // Close modal
    isCreateModalOpen.value = false;
    
    // Automatically select the newly created album
    selectAlbum(newAlbum.id);
    
    // Show success notification
    showNotification(`Álbum "${newAlbum.name}" criado com sucesso!`, 'success');
  } catch (error: any) {
    // Show error notification
    showNotification(`Erro ao criar álbum: ${error.message}`, 'error');
    console.error('Failed to create album:', error);
  }
};

/**
 * Handle modal close
 */
const handleCloseModal = (): void => {
  isCreateModalOpen.value = false;
};

/**
 * Handle edit album button click
 * Opens the edit album modal with the album data
 */
const handleEditAlbum = (albumId: string): void => {
  const album = albums.value.find(a => a.id === albumId);
  if (album) {
    editingAlbumId.value = album.id;
    editingAlbumName.value = album.name;
    editingAlbumType.value = album.type;
    isEditModalOpen.value = true;
  }
};

/**
 * Handle album update from modal
 * Updates the album with the provided data
 */
const handleUpdateAlbumSubmit = async (data: { id: string; name: string; type: string }): Promise<void> => {
  try {
    // Update album via composable
    const updatedAlbum = await updateAlbum(data.id, data.name, data.type);
    
    // Close modal
    isEditModalOpen.value = false;
    
    // Show success notification
    showNotification(`Álbum "${updatedAlbum.name}" atualizado com sucesso!`, 'success');
  } catch (error: any) {
    // Show error notification
    showNotification(`Erro ao atualizar álbum: ${error.message}`, 'error');
    console.error('Failed to update album:', error);
  }
};

/**
 * Handle edit modal close
 */
const handleCloseEditModal = (): void => {
  isEditModalOpen.value = false;
};

/**
 * Handle delete album button click
 * Opens the delete confirmation modal
 */
const handleDeleteAlbum = (albumId: string): void => {
  const album = albums.value.find(a => a.id === albumId);
  if (album) {
    deletingAlbumId.value = album.id;
    deletingAlbumName.value = album.name;
    isDeleteModalOpen.value = true;
  }
};

/**
 * Handle album deletion confirmation
 * Deletes the album
 */
const handleDeleteAlbumConfirm = async (albumId: string): Promise<void> => {
  try {
    const albumName = deletingAlbumName.value;
    
    // Delete album via composable
    await deleteAlbum(albumId);
    
    // Close modal
    isDeleteModalOpen.value = false;
    
    // Show success notification
    showNotification(`Álbum "${albumName}" excluído com sucesso!`, 'success');
  } catch (error: any) {
    // Show error notification
    showNotification(`Erro ao excluir álbum: ${error.message}`, 'error');
    console.error('Failed to delete album:', error);
  }
};

/**
 * Handle delete modal close
 */
const handleCloseDeleteModal = (): void => {
  isDeleteModalOpen.value = false;
};

/**
 * Handle media uploaded event
 * Updates the selected album's media list and count
 * 
 * @Requirements: 5.3 - Add media to gallery after successful upload
 * @Requirements: 7.5 - Update album media count
 */
const handleMediaUploaded = (uploadedMedia: Media[]): void => {
  if (!selectedAlbum.value) {
    return;
  }
  
  // Add uploaded media to the selected album's media array
  // Note: selectedAlbum is a reference to the object in albums array,
  // so we only need to update it once
  selectedAlbum.value.media.push(...uploadedMedia);
  
  // Update media count
  selectedAlbum.value.media_count += uploadedMedia.length;
};

/**
 * Handle media deleted event
 * Removes the media from the selected album and updates count
 * 
 * @Requirements: 7.3 - Remove media from album after confirmation
 * @Requirements: 7.5 - Update album media count after deletion
 * @Requirements: 9.3 - Show success notification after deletion
 * @Requirements: 9.4 - Show error notification when deletion fails
 */
const handleMediaDeleted = async (mediaId: string): Promise<void> => {
  if (!selectedAlbum.value) {
    return;
  }
  
  try {
    // Call backend to delete media
    await deleteMedia(mediaId);
    
    // Remove media from selected album's media array
    // Note: selectedAlbum is a reference to the object in albums array,
    // so we only need to update it once
    const mediaIndex = selectedAlbum.value.media.findIndex(m => m.id === mediaId);
    if (mediaIndex !== -1) {
      selectedAlbum.value.media.splice(mediaIndex, 1);
      selectedAlbum.value.media_count -= 1;
    }
    
    // Show success notification
    showNotification('Mídia excluída com sucesso!', 'success');
  } catch (error: any) {
    // Show error notification
    const errorMessage = error.message || 'Erro ao excluir mídia';
    showNotification(errorMessage, 'error');
    console.error('Failed to delete media:', error);
  }
};

/**
 * Handle media moved event
 * Removes the media from the current album and adds to the target album
 * 
 * @Requirements: Fase 1 - Atualizar UI após mover fotos
 */
const handleMediaMoved = (mediaIds: string[], targetAlbumId: string): void => {
  if (!selectedAlbum.value) {
    return;
  }
  
  // Get the media items that are being moved
  const movedMedia = selectedAlbum.value.media.filter(m => mediaIds.includes(m.id));
  
  // Remove moved media from selected album's media array
  selectedAlbum.value.media = selectedAlbum.value.media.filter(m => !mediaIds.includes(m.id));
  selectedAlbum.value.media_count -= mediaIds.length;
  
  // Update target album
  const targetAlbum = albums.value.find(a => a.id === targetAlbumId);
  if (targetAlbum) {
    // Ensure media array exists
    if (!targetAlbum.media) {
      targetAlbum.media = [];
    }
    
    // Update album_id for each moved media
    const updatedMedia = movedMedia.map(media => ({
      ...media,
      album_id: targetAlbumId
    }));
    
    // Add moved media to target album (use spread for better reactivity)
    targetAlbum.media = [...targetAlbum.media, ...updatedMedia];
    targetAlbum.media_count += mediaIds.length;
  }
};
</script>
