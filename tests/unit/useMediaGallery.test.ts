/**
 * Unit Tests for useMediaGallery Composable
 * 
 * Tests the media gallery management functionality including:
 * - Media state management
 * - Media deletion with backend integration
 * - Media refresh operations
 * 
 * @Requirements: 7.3, 7.5
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useMediaGallery } from '@/Composables/useMediaGallery';
import { router } from '@inertiajs/vue3';
import type { Media } from '@/types/media-screen';

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    delete: vi.fn(),
    get: vi.fn()
  }
}));

describe('useMediaGallery', () => {
  const mockMedia: Media[] = [
    {
      id: 1,
      album_id: 1,
      filename: 'photo1.jpg',
      type: 'image',
      mime_type: 'image/jpeg',
      size: 1024000,
      url: '/storage/media/photo1.jpg',
      thumbnail_url: '/storage/thumbnails/photo1.jpg',
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z'
    },
    {
      id: 2,
      album_id: 1,
      filename: 'video1.mp4',
      type: 'video',
      mime_type: 'video/mp4',
      size: 5120000,
      url: '/storage/media/video1.mp4',
      thumbnail_url: '/storage/thumbnails/video1.jpg',
      created_at: '2024-01-02T00:00:00Z',
      updated_at: '2024-01-02T00:00:00Z'
    }
  ];

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Initialization', () => {
    it('should initialize with provided media', () => {
      const { media } = useMediaGallery(mockMedia);
      
      expect(media.value).toEqual(mockMedia);
      expect(media.value).toHaveLength(2);
    });

    it('should initialize with empty array when no media provided', () => {
      const { media } = useMediaGallery();
      
      expect(media.value).toEqual([]);
      expect(media.value).toHaveLength(0);
    });

    it('should initialize with empty array when explicitly passed', () => {
      const { media } = useMediaGallery([]);
      
      expect(media.value).toEqual([]);
      expect(media.value).toHaveLength(0);
    });
  });

  describe('deleteMedia', () => {
    it('should delete media and remove from list on success', async () => {
      // Setup mock to simulate successful deletion
      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        // Simulate successful deletion
        options.onSuccess();
        return Promise.resolve();
      });

      const { media, deleteMedia } = useMediaGallery(mockMedia);
      
      // Verify initial state
      expect(media.value).toHaveLength(2);
      
      // Delete first media
      await deleteMedia(1);
      
      // Verify media was removed
      expect(media.value).toHaveLength(1);
      expect(media.value[0].id).toBe(2);
      expect(media.value.find(m => m.id === 1)).toBeUndefined();
      
      // Verify correct endpoint was called
      expect(router.delete).toHaveBeenCalledWith(
        '/media/1',
        expect.objectContaining({
          preserveScroll: true
        })
      );
    });

    it('should call correct endpoint for different media IDs', async () => {
      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        options.onSuccess();
        return Promise.resolve();
      });

      const { deleteMedia } = useMediaGallery(mockMedia);
      
      await deleteMedia(2);
      
      expect(router.delete).toHaveBeenCalledWith(
        '/media/2',
        expect.any(Object)
      );
    });

    it('should throw error when deletion fails', async () => {
      const errorMessage = 'Failed to delete media';
      
      // Setup mock to simulate deletion failure
      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        options.onError({ message: errorMessage });
        return Promise.resolve();
      });

      const { media, deleteMedia } = useMediaGallery(mockMedia);
      
      // Verify deletion throws error
      await expect(deleteMedia(1)).rejects.toThrow(errorMessage);
      
      // Verify media was NOT removed from list
      expect(media.value).toHaveLength(2);
    });

    it('should use default error message when none provided', async () => {
      // Setup mock to simulate deletion failure without message
      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        options.onError({});
        return Promise.resolve();
      });

      const { deleteMedia } = useMediaGallery(mockMedia);
      
      await expect(deleteMedia(1)).rejects.toThrow('Failed to delete media');
    });

    it('should handle deletion of non-existent media gracefully', async () => {
      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        options.onSuccess();
        return Promise.resolve();
      });

      const { media, deleteMedia } = useMediaGallery(mockMedia);
      
      // Try to delete media that doesn't exist
      await deleteMedia(999);
      
      // List should remain unchanged
      expect(media.value).toHaveLength(2);
      expect(media.value).toEqual(mockMedia);
    });

    it('should preserve scroll position during deletion', async () => {
      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        options.onSuccess();
        return Promise.resolve();
      });

      const { deleteMedia } = useMediaGallery(mockMedia);
      
      await deleteMedia(1);
      
      expect(router.delete).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          preserveScroll: true
        })
      );
    });
  });

  describe('refreshMedia', () => {
    it('should refresh media list for album on success', async () => {
      const updatedMedia: Media[] = [
        {
          id: 3,
          album_id: 1,
          filename: 'photo3.jpg',
          type: 'image',
          mime_type: 'image/jpeg',
          size: 2048000,
          url: '/storage/media/photo3.jpg',
          thumbnail_url: '/storage/thumbnails/photo3.jpg',
          created_at: '2024-01-03T00:00:00Z',
          updated_at: '2024-01-03T00:00:00Z'
        }
      ];

      // Setup mock to simulate successful refresh
      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onSuccess({
          props: { media: updatedMedia }
        });
        return Promise.resolve();
      });

      const { media, refreshMedia } = useMediaGallery(mockMedia);
      
      // Verify initial state
      expect(media.value).toHaveLength(2);
      
      // Refresh media
      await refreshMedia(1);
      
      // Verify media was updated
      expect(media.value).toEqual(updatedMedia);
      expect(media.value).toHaveLength(1);
      expect(media.value[0].id).toBe(3);
      
      // Verify correct endpoint was called
      expect(router.get).toHaveBeenCalledWith(
        '/albums/1/media',
        {},
        expect.objectContaining({
          preserveScroll: true,
          preserveState: true,
          only: ['media']
        })
      );
    });

    it('should call correct endpoint for different album IDs', async () => {
      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onSuccess({
          props: { media: [] }
        });
        return Promise.resolve();
      });

      const { refreshMedia } = useMediaGallery(mockMedia);
      
      await refreshMedia(5);
      
      expect(router.get).toHaveBeenCalledWith(
        '/albums/5/media',
        expect.any(Object),
        expect.any(Object)
      );
    });

    it('should handle empty media list on refresh', async () => {
      // Setup mock to return empty media list
      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onSuccess({
          props: { media: [] }
        });
        return Promise.resolve();
      });

      const { media, refreshMedia } = useMediaGallery(mockMedia);
      
      await refreshMedia(1);
      
      expect(media.value).toEqual([]);
      expect(media.value).toHaveLength(0);
    });

    it('should throw error when refresh fails', async () => {
      const errorMessage = 'Failed to load media';
      
      // Setup mock to simulate refresh failure
      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onError({ message: errorMessage });
        return Promise.resolve();
      });

      const { media, refreshMedia } = useMediaGallery(mockMedia);
      
      // Verify refresh throws error
      await expect(refreshMedia(1)).rejects.toThrow(errorMessage);
      
      // Verify media was NOT changed
      expect(media.value).toEqual(mockMedia);
    });

    it('should use default error message when none provided', async () => {
      // Setup mock to simulate refresh failure without message
      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onError({});
        return Promise.resolve();
      });

      const { refreshMedia } = useMediaGallery(mockMedia);
      
      await expect(refreshMedia(1)).rejects.toThrow('Failed to refresh media');
    });

    it('should throw error when no media returned in response', async () => {
      // Setup mock to return response without media
      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onSuccess({
          props: {}
        });
        return Promise.resolve();
      });

      const { refreshMedia } = useMediaGallery(mockMedia);
      
      await expect(refreshMedia(1)).rejects.toThrow('Failed to refresh media: No media returned');
    });

    it('should preserve scroll and state during refresh', async () => {
      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onSuccess({
          props: { media: [] }
        });
        return Promise.resolve();
      });

      const { refreshMedia } = useMediaGallery(mockMedia);
      
      await refreshMedia(1);
      
      expect(router.get).toHaveBeenCalledWith(
        expect.any(String),
        expect.any(Object),
        expect.objectContaining({
          preserveScroll: true,
          preserveState: true
        })
      );
    });

    it('should only request media data during refresh', async () => {
      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onSuccess({
          props: { media: [] }
        });
        return Promise.resolve();
      });

      const { refreshMedia } = useMediaGallery(mockMedia);
      
      await refreshMedia(1);
      
      expect(router.get).toHaveBeenCalledWith(
        expect.any(String),
        expect.any(Object),
        expect.objectContaining({
          only: ['media']
        })
      );
    });
  });

  describe('Integration scenarios', () => {
    it('should handle multiple deletions in sequence', async () => {
      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        options.onSuccess();
        return Promise.resolve();
      });

      const { media, deleteMedia } = useMediaGallery(mockMedia);
      
      // Delete first media
      await deleteMedia(1);
      expect(media.value).toHaveLength(1);
      
      // Delete second media
      await deleteMedia(2);
      expect(media.value).toHaveLength(0);
    });

    it('should maintain media state after failed deletion followed by successful deletion', async () => {
      let callCount = 0;
      
      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        callCount++;
        if (callCount === 1) {
          // First call fails
          options.onError({ message: 'Network error' });
        } else {
          // Second call succeeds
          options.onSuccess();
        }
        return Promise.resolve();
      });

      const { media, deleteMedia } = useMediaGallery(mockMedia);
      
      // First deletion fails
      await expect(deleteMedia(1)).rejects.toThrow('Network error');
      expect(media.value).toHaveLength(2);
      
      // Second deletion succeeds
      await deleteMedia(1);
      expect(media.value).toHaveLength(1);
    });

    it('should update media list after refresh following deletion', async () => {
      const updatedMedia: Media[] = [mockMedia[1]];

      vi.mocked(router.delete).mockImplementation((url, options: any) => {
        options.onSuccess();
        return Promise.resolve();
      });

      vi.mocked(router.get).mockImplementation((url, params, options: any) => {
        options.onSuccess({
          props: { media: updatedMedia }
        });
        return Promise.resolve();
      });

      const { media, deleteMedia, refreshMedia } = useMediaGallery(mockMedia);
      
      // Delete media
      await deleteMedia(1);
      expect(media.value).toHaveLength(1);
      
      // Refresh to sync with server
      await refreshMedia(1);
      expect(media.value).toEqual(updatedMedia);
    });
  });
});
