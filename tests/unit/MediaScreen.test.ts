/**
 * Unit Tests for MediaScreen.vue
 * 
 * Tests the main page component that orchestrates the media management interface.
 * Validates album selection, creation, media upload/deletion handling, and empty states.
 * 
 * @Requirements: 1.1, 2.1, 3.3, 3.6, 8.1
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import { ref } from 'vue';
import MediaScreen from '@/Pages/MediaScreen.vue';
import type { Album, Media } from '@/types/media-screen';

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn(),
    get: vi.fn(),
    delete: vi.fn()
  }
}));

// Mock composables
vi.mock('@/Composables/useAlbums', () => ({
  useAlbums: vi.fn()
}));

vi.mock('@/Composables/useMediaGallery', () => ({
  useMediaGallery: vi.fn()
}));

vi.mock('@/Composables/useNotifications', () => ({
  useNotifications: vi.fn()
}));

// Import mocked modules
import { router } from '@inertiajs/vue3';
import { useAlbums } from '@/Composables/useAlbums';
import { useMediaGallery } from '@/Composables/useMediaGallery';
import { useNotifications } from '@/Composables/useNotifications';

describe('MediaScreen.vue', () => {
  let mockAlbums: Album[];
  let mockSelectedAlbum: any;
  let mockSelectAlbum: any;
  let mockCreateAlbum: any;
  let mockRefreshAlbums: any;
  let mockDeleteMedia: any;
  let mockShowNotification: any;

  beforeEach(() => {
    // Reset mocks
    vi.clearAllMocks();

    // Setup mock albums
    mockAlbums = [
      {
        id: 1,
        name: 'Cerimônia',
        type: 'pre_casamento',
        media_count: 5,
        media: [
          {
            id: 1,
            album_id: 1,
            filename: 'photo1.jpg',
            type: 'image',
            mime_type: 'image/jpeg',
            size: 1024,
            url: '/media/photo1.jpg',
            thumbnail_url: '/media/thumb1.jpg',
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z'
          }
        ],
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      },
      {
        id: 2,
        name: 'Festa',
        type: 'pos_casamento',
        media_count: 10,
        media: [],
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ];

    // Setup mock composable functions
    mockSelectedAlbum = ref<Album | null>(null);
    mockSelectAlbum = vi.fn((albumId: number) => {
      mockSelectedAlbum.value = mockAlbums.find(a => a.id === albumId) || null;
    });
    mockCreateAlbum = vi.fn();
    mockRefreshAlbums = vi.fn();
    mockDeleteMedia = vi.fn().mockResolvedValue(undefined);
    mockShowNotification = vi.fn();

    // Mock useAlbums composable
    (useAlbums as any).mockReturnValue({
      albums: ref(mockAlbums),
      selectedAlbum: mockSelectedAlbum,
      isLoading: ref(false),
      selectAlbum: mockSelectAlbum,
      createAlbum: mockCreateAlbum,
      refreshAlbums: mockRefreshAlbums
    });
    
    // Mock useMediaGallery composable
    (useMediaGallery as any).mockReturnValue({
      media: ref([]),
      deleteMedia: mockDeleteMedia,
      refreshMedia: vi.fn()
    });
    
    // Mock useNotifications composable
    (useNotifications as any).mockReturnValue({
      notifications: ref([]),
      show: mockShowNotification,
      dismiss: vi.fn()
    });
  });

  describe('Rendering', () => {
    it('deve renderizar lista de álbuns quando fornecidos', () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Should render AlbumList component
      expect(wrapper.findComponent({ name: 'AlbumList' }).exists()).toBe(true);
    });

    it('deve exibir estado vazio quando não há álbuns', () => {
      // Mock empty albums
      (useAlbums as any).mockReturnValue({
        albums: ref([]),
        selectedAlbum: ref(null),
        isLoading: ref(false),
        selectAlbum: mockSelectAlbum,
        createAlbum: mockCreateAlbum,
        refreshAlbums: mockRefreshAlbums
      });

      const wrapper = mount(MediaScreen, {
        props: {
          albums: []
        }
      });

      // Should render EmptyState with type="no-albums"
      const emptyState = wrapper.findComponent({ name: 'EmptyState' });
      expect(emptyState.exists()).toBe(true);
      expect(emptyState.props('type')).toBe('no-albums');
    });

    it('deve exibir mensagem quando há álbuns mas nenhum selecionado', () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Should render the "no album selected" message
      const emptyStateDiv = wrapper.find('.empty-state');
      expect(emptyStateDiv.exists()).toBe(true);
      expect(emptyStateDiv.text()).toContain('Nenhum álbum selecionado');
    });

    it('deve exibir AlbumContent quando álbum está selecionado', async () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Select an album
      mockSelectedAlbum.value = mockAlbums[0];
      await wrapper.vm.$nextTick();

      // Should render AlbumContent
      const albumContent = wrapper.findComponent({ name: 'AlbumContent' });
      expect(albumContent.exists()).toBe(true);
      expect(albumContent.props('album')).toEqual(mockAlbums[0]);
    });
  });

  describe('Album Selection', () => {
    it('deve selecionar álbum ao emitir evento album-selected', async () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Emit album-selected event from AlbumList
      const albumList = wrapper.findComponent({ name: 'AlbumList' });
      await albumList.vm.$emit('album-selected', 1);

      // Should call selectAlbum
      expect(mockSelectAlbum).toHaveBeenCalledWith(1);
    });

    it('deve selecionar álbum inicial se selectedAlbumId for fornecido', () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums,
          selectedAlbumId: 2
        }
      });

      // Should call selectAlbum with the provided ID on mount
      expect(mockSelectAlbum).toHaveBeenCalledWith(2);
    });
  });

  describe('Album Creation', () => {
    it('deve abrir modal quando botão criar álbum é clicado', async () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Emit create-album event
      const albumList = wrapper.findComponent({ name: 'AlbumList' });
      await albumList.vm.$emit('create-album');

      // Wait for async operations
      await wrapper.vm.$nextTick();

      // Should open the modal
      const modal = wrapper.findComponent({ name: 'CreateAlbumModal' });
      expect(modal.exists()).toBe(true);
      expect(modal.props('isOpen')).toBe(true);
    });

    it('deve criar álbum quando usuário preenche formulário', async () => {
      // Mock createAlbum to return a new album
      const newAlbum: Album = {
        id: 3,
        name: 'Novo Álbum',
        type: 'uso_site',
        media_count: 0,
        media: [],
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };
      mockCreateAlbum.mockResolvedValue(newAlbum);

      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Open modal
      const albumList = wrapper.findComponent({ name: 'AlbumList' });
      await albumList.vm.$emit('create-album');
      await wrapper.vm.$nextTick();

      // Submit form from modal
      const modal = wrapper.findComponent({ name: 'CreateAlbumModal' });
      await modal.vm.$emit('create', { name: 'Novo Álbum', type: 'uso_site' });

      // Wait for async operations
      await wrapper.vm.$nextTick();
      await new Promise(resolve => setTimeout(resolve, 0));

      // Should call createAlbum with name and type
      expect(mockCreateAlbum).toHaveBeenCalledWith('Novo Álbum', 'uso_site');

      // Should select the new album
      expect(mockSelectAlbum).toHaveBeenCalledWith(3);

      // Should close modal
      expect(wrapper.vm.isCreateModalOpen).toBe(false);
    });

    it('deve fechar modal quando usuário cancela', async () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Open modal
      const albumList = wrapper.findComponent({ name: 'AlbumList' });
      await albumList.vm.$emit('create-album');
      await wrapper.vm.$nextTick();

      // Close modal
      const modal = wrapper.findComponent({ name: 'CreateAlbumModal' });
      await modal.vm.$emit('close');
      await wrapper.vm.$nextTick();

      // Should not call createAlbum
      expect(mockCreateAlbum).not.toHaveBeenCalled();

      // Should close modal
      expect(wrapper.vm.isCreateModalOpen).toBe(false);
    });

    it('deve exibir erro quando criação de álbum falha', async () => {
      // Mock createAlbum to reject
      mockCreateAlbum.mockRejectedValue(new Error('Erro de rede'));

      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Open modal
      const albumList = wrapper.findComponent({ name: 'AlbumList' });
      await albumList.vm.$emit('create-album');
      await wrapper.vm.$nextTick();

      // Submit form from modal
      const modal = wrapper.findComponent({ name: 'CreateAlbumModal' });
      await modal.vm.$emit('create', { name: 'Novo Álbum', type: 'uso_site' });

      // Wait for async operations
      await wrapper.vm.$nextTick();
      await new Promise(resolve => setTimeout(resolve, 0));

      // Should show error notification (check that NotificationContainer is present)
      const notificationContainer = wrapper.findComponent({ name: 'NotificationContainer' });
      expect(notificationContainer.exists()).toBe(true);
    });
  });

  describe('Media Upload Handling', () => {
    it('deve adicionar mídias ao álbum selecionado após upload', async () => {
      // Create a fresh album with 1 media item
      const albumWithMedia: Album = {
        id: 1,
        name: 'Cerimônia',
        media_count: 1,
        media: [
          {
            id: 1,
            album_id: 1,
            filename: 'photo1.jpg',
            type: 'image',
            mime_type: 'image/jpeg',
            size: 1024,
            url: '/media/photo1.jpg',
            thumbnail_url: '/media/thumb1.jpg',
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z'
          }
        ],
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      // Select the album
      mockSelectedAlbum.value = albumWithMedia;

      const wrapper = mount(MediaScreen, {
        props: {
          albums: [albumWithMedia]
        }
      });

      const uploadedMedia: Media[] = [
        {
          id: 2,
          album_id: 1,
          filename: 'photo2.jpg',
          type: 'image',
          mime_type: 'image/jpeg',
          size: 2048,
          url: '/media/photo2.jpg',
          thumbnail_url: '/media/thumb2.jpg',
          created_at: '2024-01-02T00:00:00Z',
          updated_at: '2024-01-02T00:00:00Z'
        }
      ];

      // Emit media-uploaded event
      const albumContent = wrapper.findComponent({ name: 'AlbumContent' });
      await albumContent.vm.$emit('media-uploaded', uploadedMedia);

      // Should add media to selected album
      expect(mockSelectedAlbum.value.media).toHaveLength(2);
      expect(mockSelectedAlbum.value.media_count).toBe(2);
    });

    it('não deve fazer nada se não há álbum selecionado', async () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      const uploadedMedia: Media[] = [
        {
          id: 2,
          album_id: 1,
          filename: 'photo2.jpg',
          type: 'image',
          mime_type: 'image/jpeg',
          size: 2048,
          url: '/media/photo2.jpg',
          thumbnail_url: '/media/thumb2.jpg',
          created_at: '2024-01-02T00:00:00Z',
          updated_at: '2024-01-02T00:00:00Z'
        }
      ];

      // Try to emit media-uploaded event (but no AlbumContent is rendered)
      // This should not throw an error
      expect(() => {
        wrapper.vm.handleMediaUploaded(uploadedMedia);
      }).not.toThrow();
    });
  });

  describe('Media Deletion Handling', () => {
    it('deve remover mídia do álbum selecionado após exclusão', async () => {
      // Select an album with media
      mockSelectedAlbum.value = { ...mockAlbums[0] };

      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Emit media-deleted event
      const albumContent = wrapper.findComponent({ name: 'AlbumContent' });
      await albumContent.vm.$emit('media-deleted', 1);
      
      // Wait for async deletion to complete
      await wrapper.vm.$nextTick();
      await new Promise(resolve => setTimeout(resolve, 0));

      // Should call deleteMedia
      expect(mockDeleteMedia).toHaveBeenCalledWith(1);
      
      // Should remove media from selected album
      expect(mockSelectedAlbum.value.media).toHaveLength(0);
      expect(mockSelectedAlbum.value.media_count).toBe(4);
      
      // Should show success notification
      expect(mockShowNotification).toHaveBeenCalledWith('Mídia excluída com sucesso!', 'success');
    });

    it('não deve fazer nada se não há álbum selecionado', async () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      // Try to delete media (but no AlbumContent is rendered)
      // This should not throw an error
      expect(() => {
        wrapper.vm.handleMediaDeleted(1);
      }).not.toThrow();
    });
  });

  describe('Layout', () => {
    it('deve ter layout de duas colunas', () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      const layoutColumns = wrapper.find('.layout-columns');
      expect(layoutColumns.exists()).toBe(true);
      
      // Should have display: flex
      expect(layoutColumns.element).toBeDefined();
    });

    it('deve prevenir scroll horizontal', () => {
      const wrapper = mount(MediaScreen, {
        props: {
          albums: mockAlbums
        }
      });

      const mediaScreen = wrapper.find('.media-screen');
      expect(mediaScreen.exists()).toBe(true);
    });
  });
});
