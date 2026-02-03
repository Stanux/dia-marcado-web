/**
 * useMediaGallery Composable
 * 
 * Manages media gallery state and operations for the Media Screen.
 * Provides reactive state for media items, deletion handling, move operations,
 * and refresh operations using axios for reactive updates without page reloads.
 * 
 * @Requirements: 7.3, 7.5, Fase 1
 */

import { ref } from 'vue';
import axios from 'axios';
import type { Media, UseMediaGalleryReturn } from '@/types/media-screen';

/**
 * Extended return type with move operation
 */
interface ExtendedUseMediaGalleryReturn extends UseMediaGalleryReturn {
  moveMedia: (mediaIds: string[], targetAlbumId: string) => Promise<void>;
}

/**
 * Composable for managing media gallery
 * 
 * @param initialMedia - Initial list of media from server
 * @returns Object containing media state and operations
 */
export function useMediaGallery(initialMedia: Media[] = []): ExtendedUseMediaGalleryReturn {
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
   * Move media items to another album
   * Makes a POST request to the backend to move media
   * 
   * @Requirements: Fase 1 - Mover fotos entre álbuns
   * 
   * @param mediaIds - Array of media IDs to move
   * @param targetAlbumId - ID of the destination album
   * @returns Promise that resolves when move is complete
   * @throws Error if move fails
   */
  const moveMedia = async (mediaIds: string[], targetAlbumId: string): Promise<void> => {
    try {
      const response = await axios.post('/admin/media/batch-move', {
        media_ids: mediaIds,
        target_album_id: targetAlbumId
      });
      
      if (response.data.success) {
        // Remove moved media from local state
        media.value = media.value.filter(m => !mediaIds.includes(m.id));
      } else {
        throw new Error(response.data.message || 'Failed to move media');
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || error.message || 'Failed to move media';
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
    moveMedia,
    refreshMedia
  };
}
