<template>
  <div class="flex flex-col w-full h-full overflow-y-auto p-6 bg-white dark:bg-gray-900">
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
        @delete-media="handleDeleteMedia"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AlbumContentProps, AlbumContentEvents, Media, UploadError } from '@/types/media-screen';
import { useNotifications } from '@/Composables/useNotifications';
import UploadArea from './UploadArea.vue';
import MediaGallery from './MediaGallery.vue';

/**
 * AlbumContent Component
 * 
 * Orchestrates the content area for the selected album.
 * Integrates UploadArea and MediaGallery components and manages events
 * for upload and deletion operations, propagating them to the parent component.
 * 
 * Layout: Vertical layout with UploadArea at the top and MediaGallery below.
 * 
 * @Requirements: 2.3, 4.1, 6.4
 * - Requisito 2.3: Display album content in right column with flexible width
 * - Requisito 4.1: Display upload area in upper section
 * - Requisito 6.4: Display gallery in lower section when album contains media
 */

// Props
const props = defineProps<AlbumContentProps>();

// Events
const emit = defineEmits<AlbumContentEvents>();

// Composable for notifications
const { show: showNotification } = useNotifications();

/**
 * Handle upload started event from UploadArea
 * Currently just logs the event, but can be extended for additional handling
 * 
 * @param files - Array of files that started uploading
 */
const handleUploadStarted = (files: File[]): void => {
  // Log upload start for debugging/monitoring
  console.log(`Upload started for ${files.length} file(s) to album ${props.album.id}`);
  
  // Could add additional handling here, such as:
  // - Showing a loading indicator
  // - Disabling certain UI elements
  // - Tracking analytics
};

/**
 * Handle upload completed event from UploadArea
 * Propagates the event to parent component (MediaScreen)
 * 
 * @param media - Array of successfully uploaded media items
 * @Requirements: 5.3 - Update gallery after successful upload
 */
const handleUploadCompleted = (media: Media[]): void => {
  console.log(`Upload completed: ${media.length} media item(s) added to album ${props.album.id}`);
  
  // Propagate event to parent component
  emit('media-uploaded', media);
};

/**
 * Handle upload failed event from UploadArea
 * Logs the error for debugging/monitoring
 * 
 * @param error - Upload error information
 * @Requirements: 5.4 - Display error message when upload fails
 */
const handleUploadFailed = (error: UploadError): void => {
  console.error(`Upload failed for album ${props.album.id}:`, error.message, error.code);
  
  // Error is already displayed by UploadArea component
  // Could add additional handling here, such as:
  // - Showing a global notification
  // - Tracking error analytics
  // - Logging to error monitoring service
};

/**
 * Handle delete media event from MediaGallery
 * Propagates the event to parent component (MediaScreen)
 * 
 * @param mediaId - ID of the media to delete
 * @Requirements: 7.3 - Remove media from album after confirmation
 */
const handleDeleteMedia = (mediaId: string): void => {
  console.log(`Delete media requested: ${mediaId} from album ${props.album.id}`);
  
  // Propagate event to parent component
  emit('media-deleted', mediaId);
};
</script>
