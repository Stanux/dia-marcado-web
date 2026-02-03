/**
 * useMediaGallery Composable
 * 
 * Manages media gallery state and operations for the Media Screen.
 * Provides reactive state for media items, deletion handling, and refresh operations
 * using axios for reactive updates without page reloads.
 * 
 * @Requirements: 7.3, 7.5
 */

import { ref } from 'vue';
import axios from 'axios';
import type { Media, UseMediaGalleryReturn } from '@/types/media-screen';

/**
 * Composable for managing media gallery
 * 
 * @param initialMedia - Initial list of media from server
 * @returns Object containing media state and operations
 */
export function useMediaGallery(initialMedia: Media[] = []): UseMediaGalleryReturn {
  // Reactive state
  const media = ref<Media[]>(initialMedia);

  /**
   * Delete a media item
   * Makes a DELETE request to the backend and removes the media from the list
   * 
   * @Requirements: 7.3 - Remove media from album when user confirms deletion
   * @Requirements: 7.5 - Update album media count after deletion
   * 
   * @param mediaId - ID of the media to delete
   * @returns Promise that resolves when deletion is complete
   * @throws Error if deletion fails
   */
  const deleteMedia = async (mediaId: string): Promise<void> => {
    try {
      const response = await axios.delete(`/admin/media/${mediaId}`);
      
      if (response.data.success) {
        // Remove media from local state
        media.value = media.value.filter(m => m.id !== mediaId);
      } else {
        throw new Error(response.data.message || 'Failed to delete media');
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || error.message || 'Failed to delete media';
      throw new Error(errorMessage);
    }
  };

  /**
   * Refresh media list for a specific album
   * Makes a GET request to reload all media for the given album
   * 
   * @param albumId - ID of the album to refresh media for
   * @returns Promise that resolves when media is refreshed
   * @throws Error if refresh fails
   */
  const refreshMedia = async (albumId: string): Promise<void> => {
    try {
      const response = await axios.get(`/admin/albums/${albumId}/media`);
      
      if (response.data.media) {
        media.value = response.data.media;
      } else {
        throw new Error('Failed to refresh media: No media returned');
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || error.message || 'Failed to refresh media';
      throw new Error(errorMessage);
    }
  };

  return {
    media,
    deleteMedia,
    refreshMedia
  };
}
