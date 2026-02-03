/**
 * useAlbums Composable
 * 
 * Manages album state and operations for the Media Screen.
 * Provides reactive state for albums, selection handling, and CRUD operations
 * integrated with Laravel backend via axios for reactive updates.
 * 
 * @Requirements: 3.3, 3.4, 3.6
 */

import { ref, computed, type Ref } from 'vue';
import axios from 'axios';
import type { Album, UseAlbumsReturn } from '@/types/media-screen';

/**
 * Composable for managing albums
 * 
 * @param initialAlbums - Initial list of albums from server
 * @returns Object containing albums state and operations
 */
export function useAlbums(initialAlbums: Album[]): UseAlbumsReturn {
  // Reactive state
  const albums = ref<Album[]>(initialAlbums);
  const selectedAlbum = ref<Album | null>(null);
  const isLoading = ref(false);

  /**
   * Select an album by ID
   * Updates the selectedAlbum ref to the album with matching ID
   * 
   * @Requirements: 3.3 - Highlight selected album visually
   * @Requirements: 3.4 - Load and display media from selected album
   * 
   * @param albumId - ID of the album to select
   */
  const selectAlbum = (albumId: string): void => {
    const album = albums.value.find(a => a.id === albumId);
    if (album) {
      // Ensure media array exists
      if (!album.media) {
        album.media = [];
      }
      selectedAlbum.value = album;
    } else {
      selectedAlbum.value = null;
    }
  };

  /**
   * Create a new album
   * Makes a POST request to the backend and adds the new album to the list
   * Uses axios for reactive updates without page reload
   * 
   * @Requirements: 3.6 - Allow creation of new album with custom name
   * 
   * @param name - Name for the new album
   * @param type - Type of the album (pre_casamento, pos_casamento, uso_site)
   * @returns Promise resolving to the created album
   * @throws Error if creation fails
   */
  const createAlbum = async (name: string, type: string): Promise<Album> => {
    isLoading.value = true;
    
    try {
      const response = await axios.post('/admin/albums', {
        name,
        type
      });

      if (response.data.success && response.data.album) {
        const newAlbum = response.data.album as Album;
        
        // Add to local albums list
        albums.value.push(newAlbum);
        
        return newAlbum;
      } else {
        throw new Error(response.data.message || 'Failed to create album');
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || error.message || 'Failed to create album';
      throw new Error(errorMessage);
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Update an existing album
   * Makes a PUT request to the backend and updates the album in the list
   * Uses axios for reactive updates without page reload
   * 
   * @param id - ID of the album to update
   * @param name - New name for the album
   * @param type - New type of the album
   * @returns Promise resolving to the updated album
   * @throws Error if update fails
   */
  const updateAlbum = async (id: string, name: string, type: string): Promise<Album> => {
    isLoading.value = true;
    
    try {
      const response = await axios.put(`/admin/albums/${id}`, {
        name,
        type
      });

      if (response.data.success && response.data.album) {
        const updatedAlbum = response.data.album as Album;
        
        // Update in local albums list
        const index = albums.value.findIndex(a => a.id === id);
        if (index !== -1) {
          albums.value[index] = updatedAlbum;
        }
        
        // Update selected album if it's the one being updated
        if (selectedAlbum.value?.id === id) {
          selectedAlbum.value = updatedAlbum;
        }
        
        return updatedAlbum;
      } else {
        throw new Error(response.data.message || 'Failed to update album');
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || error.message || 'Failed to update album';
      throw new Error(errorMessage);
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Delete an album
   * Makes a DELETE request to the backend and removes the album from the list
   * Uses axios for reactive updates without page reload
   * 
   * @param id - ID of the album to delete
   * @returns Promise that resolves when album is deleted
   * @throws Error if deletion fails
   */
  const deleteAlbum = async (id: string): Promise<void> => {
    isLoading.value = true;
    
    try {
      const response = await axios.delete(`/admin/albums/${id}`);

      if (response.data.success) {
        // Remove from local albums list
        albums.value = albums.value.filter(a => a.id !== id);
        
        // Clear selected album if it's the one being deleted
        if (selectedAlbum.value?.id === id) {
          selectedAlbum.value = null;
        }
      } else {
        throw new Error(response.data.message || 'Failed to delete album');
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || error.message || 'Failed to delete album';
      throw new Error(errorMessage);
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Refresh albums list from server
   * Makes a GET request to reload all albums
   * 
   * @returns Promise that resolves when albums are refreshed
   * @throws Error if refresh fails
   */
  const refreshAlbums = async (): Promise<void> => {
    isLoading.value = true;
    
    try {
      const response = await axios.get('/admin/albums');
      
      if (response.data.albums) {
        const updatedAlbums = response.data.albums as Album[];
        albums.value = updatedAlbums;
        
        // Update selected album if it still exists
        if (selectedAlbum.value) {
          const updatedSelected = updatedAlbums.find(
            a => a.id === selectedAlbum.value!.id
          );
          selectedAlbum.value = updatedSelected || null;
        }
      } else {
        throw new Error('Failed to refresh albums: No albums returned');
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || error.message || 'Failed to refresh albums';
      throw new Error(errorMessage);
    } finally {
      isLoading.value = false;
    }
  };

  return {
    albums,
    selectedAlbum,
    isLoading,
    selectAlbum,
    createAlbum,
    updateAlbum,
    deleteAlbum,
    refreshAlbums
  };
}
