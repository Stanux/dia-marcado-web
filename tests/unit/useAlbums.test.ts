/**
 * Unit Tests for useAlbums Composable
 * 
 * Tests the album management composable including state management,
 * selection, creation, and refresh operations.
 * 
 * @Requirements: 3.3, 3.4, 3.6
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useAlbums } from '@/Composables/useAlbums';
import axios from 'axios';
import type { Album } from '@/types/media-screen';

// Mock axios
vi.mock('axios', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  }
}));

describe('useAlbums', () => {
  // Helper function to create fresh mock albums for each test
  const createMockAlbums = (): Album[] => [
    {
      id: 1,
      name: 'Cerimônia',
      type: 'pre_casamento',
      media_count: 5,
      media: [],
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z'
    },
    {
      id: 2,
      name: 'Festa',
      type: 'pos_casamento',
      media_count: 10,
      media: [],
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
    it('should initialize with provided albums', () => {
      const mockAlbums = createMockAlbums();
      const { albums } = useAlbums(mockAlbums);
      
      expect(albums.value).toEqual(mockAlbums);
      expect(albums.value).toHaveLength(2);
    });

    it('should initialize with empty albums array', () => {
      const { albums } = useAlbums([]);
      
      expect(albums.value).toEqual([]);
      expect(albums.value).toHaveLength(0);
    });

    it('should initialize with no selected album', () => {
      const mockAlbums = createMockAlbums();
      const { selectedAlbum } = useAlbums(mockAlbums);
      
      expect(selectedAlbum.value).toBeNull();
    });

    it('should initialize with isLoading as false', () => {
      const mockAlbums = createMockAlbums();
      const { isLoading } = useAlbums(mockAlbums);
      
      expect(isLoading.value).toBe(false);
    });
  });

  describe('selectAlbum', () => {
    it('should select an album by ID', () => {
      const mockAlbums = createMockAlbums();
      const { selectedAlbum, selectAlbum } = useAlbums(mockAlbums);
      
      selectAlbum(1);
      
      expect(selectedAlbum.value).not.toBeNull();
      expect(selectedAlbum.value?.id).toBe(1);
      expect(selectedAlbum.value?.name).toBe('Cerimônia');
    });

    it('should update selection when selecting different album', () => {
      const mockAlbums = createMockAlbums();
      const { selectedAlbum, selectAlbum } = useAlbums(mockAlbums);
      
      selectAlbum(1);
      expect(selectedAlbum.value?.id).toBe(1);
      
      selectAlbum(2);
      expect(selectedAlbum.value?.id).toBe(2);
      expect(selectedAlbum.value?.name).toBe('Festa');
    });

    it('should set selectedAlbum to null when ID does not exist', () => {
      const mockAlbums = createMockAlbums();
      const { selectedAlbum, selectAlbum } = useAlbums(mockAlbums);
      
      selectAlbum(999);
      
      expect(selectedAlbum.value).toBeNull();
    });

    it('should handle selecting album with media', () => {
      const albumsWithMedia: Album[] = [
        {
          ...createMockAlbums()[0],
          media: [
            {
              id: 1,
              album_id: 1,
              filename: 'photo.jpg',
              type: 'image',
              mime_type: 'image/jpeg',
              size: 1024,
              url: '/media/photo.jpg',
              thumbnail_url: '/media/thumb/photo.jpg',
              created_at: '2024-01-01T00:00:00Z',
              updated_at: '2024-01-01T00:00:00Z'
            }
          ]
        }
      ];
      
      const { selectedAlbum, selectAlbum } = useAlbums(albumsWithMedia);
      
      selectAlbum(1);
      
      expect(selectedAlbum.value?.media).toHaveLength(1);
      expect(selectedAlbum.value?.media[0].filename).toBe('photo.jpg');
    });
  });

  describe('createAlbum', () => {
    it('should create a new album successfully', async () => {
      const mockAlbums = createMockAlbums();
      const newAlbum: Album = {
        id: 3,
        name: 'Ensaio',
        type: 'uso_site',
        media_count: 0,
        media: [],
        created_at: '2024-01-03T00:00:00Z',
        updated_at: '2024-01-03T00:00:00Z'
      };

      // Mock successful response
      vi.mocked(axios.post).mockResolvedValue({
        data: {
          success: true,
          album: newAlbum
        }
      });

      const { albums, createAlbum, isLoading } = useAlbums(mockAlbums);
      
      const result = await createAlbum('Ensaio', 'uso_site');
      
      expect(axios.post).toHaveBeenCalledWith('/admin/albums', {
        name: 'Ensaio',
        type: 'uso_site'
      });
      expect(result).toEqual(newAlbum);
      expect(albums.value).toHaveLength(3);
      expect(albums.value[2]).toEqual(newAlbum);
      expect(isLoading.value).toBe(false);
    });

    it('should set isLoading to true during creation', async () => {
      const mockAlbums = createMockAlbums();
      const newAlbum: Album = {
        id: 3,
        name: 'Test',
        type: 'uso_site',
        media_count: 0,
        media: [],
        created_at: '2024-01-03T00:00:00Z',
        updated_at: '2024-01-03T00:00:00Z'
      };

      let loadingDuringCall = false;

      vi.mocked(axios.post).mockImplementation(() => {
        return new Promise((resolve) => {
          setTimeout(() => {
            resolve({
              data: {
                success: true,
                album: newAlbum
              }
            });
          }, 0);
        });
      });

      const { createAlbum, isLoading } = useAlbums(mockAlbums);
      
      const promise = createAlbum('Test', 'uso_site');
      loadingDuringCall = isLoading.value;
      
      await promise;
      
      expect(loadingDuringCall).toBe(true);
      expect(isLoading.value).toBe(false);
    });

    it('should handle creation error', async () => {
      const mockAlbums = createMockAlbums();
      const errorMessage = 'Album name is required';

      vi.mocked(axios.post).mockRejectedValue({
        response: {
          data: {
            message: errorMessage
          }
        }
      });

      const { createAlbum, albums } = useAlbums(mockAlbums);
      
      await expect(createAlbum('', 'uso_site')).rejects.toThrow(errorMessage);
      expect(albums.value).toHaveLength(2); // Should not add album on error
    });

    it('should handle missing album in response', async () => {
      const mockAlbums = createMockAlbums();
      
      vi.mocked(axios.post).mockResolvedValue({
        data: {
          success: false
        }
      });

      const { createAlbum } = useAlbums(mockAlbums);
      
      await expect(createAlbum('Test', 'uso_site')).rejects.toThrow('Failed to create album');
    });
  });

  describe('refreshAlbums', () => {
    it('should refresh albums list successfully', async () => {
      const mockAlbums = createMockAlbums();
      const updatedAlbums: Album[] = [
        ...mockAlbums,
        {
          id: 3,
          name: 'Novo Álbum',
          type: 'uso_site',
          media_count: 0,
          media: [],
          created_at: '2024-01-03T00:00:00Z',
          updated_at: '2024-01-03T00:00:00Z'
        }
      ];

      vi.mocked(axios.get).mockResolvedValue({
        data: {
          albums: updatedAlbums
        }
      });

      const { albums, refreshAlbums } = useAlbums(mockAlbums);
      
      await refreshAlbums();
      
      expect(axios.get).toHaveBeenCalledWith('/admin/albums');
      expect(albums.value).toHaveLength(3);
      expect(albums.value[2].name).toBe('Novo Álbum');
    });

    it('should update selected album after refresh if it still exists', async () => {
      const mockAlbums = createMockAlbums();
      const updatedAlbums: Album[] = [
        {
          ...mockAlbums[0],
          media_count: 15 // Updated count
        },
        mockAlbums[1]
      ];

      vi.mocked(axios.get).mockResolvedValue({
        data: {
          albums: updatedAlbums
        }
      });

      const { selectedAlbum, selectAlbum, refreshAlbums } = useAlbums(mockAlbums);
      
      selectAlbum(1);
      expect(selectedAlbum.value?.media_count).toBe(5);
      
      await refreshAlbums();
      
      expect(selectedAlbum.value).not.toBeNull();
      expect(selectedAlbum.value?.id).toBe(1);
      expect(selectedAlbum.value?.media_count).toBe(15);
    });

    it('should clear selected album if it no longer exists after refresh', async () => {
      const mockAlbums = createMockAlbums();
      const updatedAlbums: Album[] = [mockAlbums[1]]; // Only album 2

      vi.mocked(axios.get).mockResolvedValue({
        data: {
          albums: updatedAlbums
        }
      });

      const { selectedAlbum, selectAlbum, refreshAlbums } = useAlbums(mockAlbums);
      
      selectAlbum(1);
      expect(selectedAlbum.value?.id).toBe(1);
      
      await refreshAlbums();
      
      expect(selectedAlbum.value).toBeNull();
    });

    it('should handle refresh error', async () => {
      const mockAlbums = createMockAlbums();
      const errorMessage = 'Failed to load albums';

      vi.mocked(axios.get).mockRejectedValue({
        response: {
          data: {
            message: errorMessage
          }
        }
      });

      const { refreshAlbums, albums } = useAlbums(mockAlbums);
      const originalAlbums = [...albums.value];
      
      await expect(refreshAlbums()).rejects.toThrow(errorMessage);
      expect(albums.value).toEqual(originalAlbums); // Should not change on error
    });

    it('should set isLoading during refresh', async () => {
      const mockAlbums = createMockAlbums();
      const updatedAlbums: Album[] = mockAlbums;
      let loadingDuringCall = false;

      vi.mocked(axios.get).mockImplementation(() => {
        return new Promise((resolve) => {
          setTimeout(() => {
            resolve({
              data: {
                albums: updatedAlbums
              }
            });
          }, 0);
        });
      });

      const { refreshAlbums, isLoading } = useAlbums(mockAlbums);
      
      const promise = refreshAlbums();
      loadingDuringCall = isLoading.value;
      
      await promise;
      
      expect(loadingDuringCall).toBe(true);
      expect(isLoading.value).toBe(false);
    });
  });

  describe('Edge Cases', () => {
    it('should handle empty album name', async () => {
      const mockAlbums = createMockAlbums();
      
      vi.mocked(axios.post).mockRejectedValue({
        response: {
          data: {
            message: 'The name field is required.'
          }
        }
      });

      const { createAlbum } = useAlbums(mockAlbums);
      
      await expect(createAlbum('', 'uso_site')).rejects.toThrow();
    });

    it('should handle very long album names', async () => {
      const mockAlbums = createMockAlbums();
      const longName = 'A'.repeat(255);
      const newAlbum: Album = {
        id: 3,
        name: longName,
        type: 'uso_site',
        media_count: 0,
        media: [],
        created_at: '2024-01-03T00:00:00Z',
        updated_at: '2024-01-03T00:00:00Z'
      };

      vi.mocked(axios.post).mockResolvedValue({
        data: {
          success: true,
          album: newAlbum
        }
      });

      const { createAlbum } = useAlbums(mockAlbums);
      
      const result = await createAlbum(longName, 'uso_site');
      expect(result.name).toBe(longName);
    });

    it('should handle selecting same album twice', () => {
      const mockAlbums = createMockAlbums();
      const { selectedAlbum, selectAlbum } = useAlbums(mockAlbums);
      
      selectAlbum(1);
      const firstSelection = selectedAlbum.value;
      
      selectAlbum(1);
      const secondSelection = selectedAlbum.value;
      
      expect(firstSelection?.id).toBe(secondSelection?.id);
    });
  });
});
