<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <h1 class="text-2xl font-semibold text-gray-900">Galeria de Mídias</h1>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="media-screen">
        <div class="layout-columns">
          <!-- Left Column: Album List -->
          <AlbumList 
            :albums="albums"
            :selected-album-id="selectedAlbum?.id"
            @album-selected="handleAlbumSelection"
            @create-album="handleCreateAlbum"
          />
          
          <!-- Right Column: Album Content or Empty State -->
          <!-- Debug: {{ selectedAlbum ? 'Album selected: ' + selectedAlbum.name : 'No album selected' }} -->
          <div v-if="!selectedAlbum" class="flex flex-col w-full h-full overflow-y-auto p-6 bg-white dark:bg-gray-900">
            <!-- Disabled Upload Area -->
            <div class="w-full flex-shrink-0 mb-8 opacity-50 pointer-events-none">
              <div class="upload-area-disabled border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-gray-50">
                <div class="upload-icon text-gray-400 text-5xl mb-4">
                  📤
                </div>
                <p class="text-gray-500 text-sm">
                  Arraste arquivos ou clique para selecionar
                </p>
                <p class="text-gray-400 text-xs mt-2">
                  Imagens (JPG, PNG, GIF) e vídeos (MP4, QuickTime) até 100MB
                </p>
              </div>
            </div>

            <!-- Empty State Message -->
            <div class="w-full flex-1 min-h-0 flex items-center justify-center">
              <div class="empty-state text-center">
                <div class="empty-icon text-6xl mb-4">
                  🖼️
                </div>
                <h3 class="empty-title text-xl font-semibold text-gray-700 mb-2">
                  Nenhum álbum selecionado
                </h3>
                <p class="empty-message text-gray-500">
                  Crie ou selecione um álbum.
                </p>
              </div>
            </div>
          </div>
          
          <AlbumContent
            v-else
            :album="selectedAlbum"
            @media-uploaded="handleMediaUploaded"
            @media-deleted="handleMediaDeleted"
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
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { MediaScreenProps, Media } from '@/types/media-screen';
import { useAlbums } from '@/Composables/useAlbums';
import { useMediaGallery } from '@/Composables/useMediaGallery';
import { useNotifications } from '@/Composables/useNotifications';
import AlbumList from '@/Components/MediaScreen/AlbumList.vue';
import AlbumContent from '@/Components/MediaScreen/AlbumContent.vue';
import EmptyState from '@/Components/MediaScreen/EmptyState.vue';
import NotificationContainer from '@/Components/MediaScreen/NotificationContainer.vue';
import CreateAlbumModal from '@/Components/MediaScreen/CreateAlbumModal.vue';

/**
 * MediaScreen Page Component
 * 
 * Main page that orchestrates the entire media management interface.
 * Integrates album list, album content, and empty states with composables
 * for state management and backend integration.
 * 
 * Layout: Two-column layout with AlbumList (left, fixed width) and 
 * AlbumContent/EmptyState (right, flexible width).
 * 
 * @Requirements: 1.1, 2.1, 2.2, 2.3, 3.3, 3.4, 3.6
 * - Requisito 1.1: Display media screen when user clicks "Mídias" in sidebar
 * - Requisito 2.1: Divide content area into two main columns
 * - Requisito 2.2: Display album list in left column with fixed width
 * - Requisito 2.3: Display album content in right column with flexible width
 * - Requisito 3.3: Highlight selected album visually when clicked
 * - Requisito 3.4: Load and display media from selected album in right column
 * - Requisito 3.6: Allow creation of new album with custom name
 */

// Props from Inertia.js
const props = defineProps<MediaScreenProps>();

// Use composable for album management
const {
  albums,
  selectedAlbum,
  isLoading,
  selectAlbum,
  createAlbum,
  refreshAlbums
} = useAlbums(props.albums);

// Use composable for media gallery operations
const { deleteMedia } = useMediaGallery();

// Use composable for notifications
const { show: showNotification } = useNotifications();

// Modal state
const isCreateModalOpen = ref(false);

/**
 * Handle album selection
 * Updates the selected album and displays its content
 * 
 * @Requirements: 3.3 - Highlight selected album visually
 * @Requirements: 3.4 - Load and display media from selected album
 * 
 * @param albumId - ID of the album to select
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
    
    console.log(`Album "${newAlbum.name}" created successfully`);
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
 * Handle media uploaded event
 * Updates the selected album's media list and count
 * 
 * @Requirements: 5.3 - Add media to gallery after successful upload
 * @Requirements: 7.5 - Update album media count
 * 
 * @param uploadedMedia - Array of newly uploaded media items
 */
const handleMediaUploaded = (uploadedMedia: Media[]): void => {
  if (!selectedAlbum.value) {
    return;
  }
  
  // Add uploaded media to the selected album's media array
  selectedAlbum.value.media.push(...uploadedMedia);
  
  // Update media count
  selectedAlbum.value.media_count += uploadedMedia.length;
  
  // Also update in the albums array to keep it in sync
  const albumIndex = albums.value.findIndex(a => a.id === selectedAlbum.value!.id);
  if (albumIndex !== -1) {
    albums.value[albumIndex].media.push(...uploadedMedia);
    albums.value[albumIndex].media_count += uploadedMedia.length;
  }
  
  console.log(`${uploadedMedia.length} media item(s) added to album ${selectedAlbum.value.id}`);
};

/**
 * Handle media deleted event
 * Removes the media from the selected album and updates count
 * 
 * @Requirements: 7.3 - Remove media from album after confirmation
 * @Requirements: 7.5 - Update album media count after deletion
 * @Requirements: 9.3 - Show success notification after deletion
 * @Requirements: 9.4 - Show error notification when deletion fails
 * 
 * @param mediaId - ID of the deleted media
 */
const handleMediaDeleted = async (mediaId: string): Promise<void> => {
  if (!selectedAlbum.value) {
    return;
  }
  
  try {
    // Call backend to delete media
    await deleteMedia(mediaId);
    
    // Remove media from selected album's media array
    const mediaIndex = selectedAlbum.value.media.findIndex(m => m.id === mediaId);
    if (mediaIndex !== -1) {
      selectedAlbum.value.media.splice(mediaIndex, 1);
      selectedAlbum.value.media_count -= 1;
    }
    
    // Also update in the albums array to keep it in sync
    const albumIndex = albums.value.findIndex(a => a.id === selectedAlbum.value!.id);
    if (albumIndex !== -1) {
      const albumMediaIndex = albums.value[albumIndex].media.findIndex(m => m.id === mediaId);
      if (albumMediaIndex !== -1) {
        albums.value[albumIndex].media.splice(albumMediaIndex, 1);
        albums.value[albumIndex].media_count -= 1;
      }
    }
    
    // Show success notification
    showNotification('Mídia excluída com sucesso!', 'success');
    
    console.log(`Media ${mediaId} deleted from album ${selectedAlbum.value.id}`);
  } catch (error: any) {
    // Show error notification
    const errorMessage = error.message || 'Erro ao excluir mídia';
    showNotification(errorMessage, 'error');
    console.error('Failed to delete media:', error);
  }
};

/**
 * Initialize component
 * Select the album specified in props if provided
 */
onMounted(() => {
  if (props.selectedAlbumId) {
    selectAlbum(props.selectedAlbumId);
  }
});
</script>

<style scoped>
.media-screen {
  width: 100%;
  min-height: 600px;
}

.layout-columns {
  display: flex;
  gap: 1.5rem;
  width: 100%;
  min-height: 600px;
}

/* Responsive: Stack columns on mobile */
@media (max-width: 768px) {
  .layout-columns {
    flex-direction: column;
  }
}
</style>
