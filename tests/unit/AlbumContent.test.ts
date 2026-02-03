import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import AlbumContent from '@/Components/MediaScreen/AlbumContent.vue';
import UploadArea from '@/Components/MediaScreen/UploadArea.vue';
import MediaGallery from '@/Components/MediaScreen/MediaGallery.vue';
import type { Album, Media, UploadError } from '@/types/media-screen';

/**
 * Unit Tests for AlbumContent Component
 * 
 * Tests the orchestration of UploadArea and MediaGallery components,
 * event handling, and propagation to parent component.
 * 
 * @Requirements: 2.3, 4.1, 6.4
 */

describe('AlbumContent.vue', () => {
  // Mock album data
  const mockAlbum: Album = {
    id: 1,
    name: 'Test Album',
    media_count: 2,
    media: [
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
        updated_at: '2024-01-01T00:00:00Z',
      },
      {
        id: 2,
        album_id: 1,
        filename: 'photo2.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 2048000,
        url: '/storage/media/photo2.jpg',
        thumbnail_url: '/storage/thumbnails/photo2.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
      },
    ],
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  };

  const mockAlbumEmpty: Album = {
    id: 2,
    name: 'Empty Album',
    media_count: 0,
    media: [],
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  };

  beforeEach(() => {
    // Clear all mocks before each test
    vi.clearAllMocks();
  });

  describe('Component Rendering', () => {
    it('should render UploadArea component', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const uploadArea = wrapper.findComponent(UploadArea);
      expect(uploadArea.exists()).toBe(true);
    });

    it('should render MediaGallery component', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);
      expect(mediaGallery.exists()).toBe(true);
    });

    it('should pass album.id to UploadArea as albumId prop', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const uploadArea = wrapper.findComponent(UploadArea);
      expect(uploadArea.props('albumId')).toBe(mockAlbum.id);
    });

    it('should pass album.media to MediaGallery as media prop', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);
      expect(mediaGallery.props('media')).toEqual(mockAlbum.media);
    });

    it('should render with empty media array', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbumEmpty },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);
      expect(mediaGallery.props('media')).toEqual([]);
    });

    it('should have vertical layout structure', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const albumContent = wrapper.find('.album-content');
      expect(albumContent.exists()).toBe(true);

      const uploadSection = wrapper.find('.upload-section');
      const gallerySection = wrapper.find('.gallery-section');

      expect(uploadSection.exists()).toBe(true);
      expect(gallerySection.exists()).toBe(true);

      // Verify upload section comes before gallery section
      const sections = wrapper.findAll('.upload-section, .gallery-section');
      expect(sections[0].classes()).toContain('upload-section');
      expect(sections[1].classes()).toContain('gallery-section');
    });
  });

  describe('Upload Event Handling', () => {
    it('should handle upload-started event from UploadArea', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const uploadArea = wrapper.findComponent(UploadArea);
      const mockFiles = [
        new File(['content'], 'test1.jpg', { type: 'image/jpeg' }),
        new File(['content'], 'test2.jpg', { type: 'image/jpeg' }),
      ];

      // Emit upload-started event
      await uploadArea.vm.$emit('upload-started', mockFiles);

      // Component should handle the event (currently just logs)
      // No event should be emitted to parent for upload-started
      expect(wrapper.emitted('media-uploaded')).toBeUndefined();
    });

    it('should propagate upload-completed event to parent', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const uploadArea = wrapper.findComponent(UploadArea);
      const mockUploadedMedia: Media[] = [
        {
          id: 3,
          album_id: 1,
          filename: 'new-photo.jpg',
          type: 'image',
          mime_type: 'image/jpeg',
          size: 1500000,
          url: '/storage/media/new-photo.jpg',
          thumbnail_url: '/storage/thumbnails/new-photo.jpg',
          created_at: '2024-01-02T00:00:00Z',
          updated_at: '2024-01-02T00:00:00Z',
        },
      ];

      // Emit upload-completed event
      await uploadArea.vm.$emit('upload-completed', mockUploadedMedia);

      // Should emit media-uploaded event to parent
      expect(wrapper.emitted('media-uploaded')).toBeTruthy();
      expect(wrapper.emitted('media-uploaded')?.[0]).toEqual([mockUploadedMedia]);
    });

    it('should handle upload-failed event from UploadArea', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const uploadArea = wrapper.findComponent(UploadArea);
      const mockError: UploadError = {
        message: 'Upload failed',
        files: [new File(['content'], 'test.jpg', { type: 'image/jpeg' })],
        code: 'NETWORK_ERROR',
      };

      // Emit upload-failed event
      await uploadArea.vm.$emit('upload-failed', mockError);

      // Component should handle the event (currently just logs)
      // No event should be emitted to parent for upload-failed
      expect(wrapper.emitted('media-uploaded')).toBeUndefined();
    });

    it('should handle multiple upload-completed events', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const uploadArea = wrapper.findComponent(UploadArea);
      
      const firstBatch: Media[] = [
        {
          id: 3,
          album_id: 1,
          filename: 'photo3.jpg',
          type: 'image',
          mime_type: 'image/jpeg',
          size: 1000000,
          url: '/storage/media/photo3.jpg',
          thumbnail_url: '/storage/thumbnails/photo3.jpg',
          created_at: '2024-01-02T00:00:00Z',
          updated_at: '2024-01-02T00:00:00Z',
        },
      ];

      const secondBatch: Media[] = [
        {
          id: 4,
          album_id: 1,
          filename: 'photo4.jpg',
          type: 'image',
          mime_type: 'image/jpeg',
          size: 1200000,
          url: '/storage/media/photo4.jpg',
          thumbnail_url: '/storage/thumbnails/photo4.jpg',
          created_at: '2024-01-02T00:00:00Z',
          updated_at: '2024-01-02T00:00:00Z',
        },
      ];

      // Emit first batch
      await uploadArea.vm.$emit('upload-completed', firstBatch);
      
      // Emit second batch
      await uploadArea.vm.$emit('upload-completed', secondBatch);

      // Should have emitted media-uploaded twice
      expect(wrapper.emitted('media-uploaded')).toHaveLength(2);
      expect(wrapper.emitted('media-uploaded')?.[0]).toEqual([firstBatch]);
      expect(wrapper.emitted('media-uploaded')?.[1]).toEqual([secondBatch]);
    });
  });

  describe('Delete Event Handling', () => {
    it('should propagate delete-media event to parent', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);
      const mediaIdToDelete = 1;

      // Emit delete-media event
      await mediaGallery.vm.$emit('delete-media', mediaIdToDelete);

      // Should emit media-deleted event to parent
      expect(wrapper.emitted('media-deleted')).toBeTruthy();
      expect(wrapper.emitted('media-deleted')?.[0]).toEqual([mediaIdToDelete]);
    });

    it('should handle multiple delete-media events', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);

      // Delete first media
      await mediaGallery.vm.$emit('delete-media', 1);
      
      // Delete second media
      await mediaGallery.vm.$emit('delete-media', 2);

      // Should have emitted media-deleted twice
      expect(wrapper.emitted('media-deleted')).toHaveLength(2);
      expect(wrapper.emitted('media-deleted')?.[0]).toEqual([1]);
      expect(wrapper.emitted('media-deleted')?.[1]).toEqual([2]);
    });
  });

  describe('Props Reactivity', () => {
    it('should update MediaGallery when album.media changes', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);
      expect(mediaGallery.props('media')).toHaveLength(2);

      // Update album with new media
      const updatedAlbum: Album = {
        ...mockAlbum,
        media: [
          ...mockAlbum.media,
          {
            id: 3,
            album_id: 1,
            filename: 'photo3.jpg',
            type: 'image',
            mime_type: 'image/jpeg',
            size: 1500000,
            url: '/storage/media/photo3.jpg',
            thumbnail_url: '/storage/thumbnails/photo3.jpg',
            created_at: '2024-01-02T00:00:00Z',
            updated_at: '2024-01-02T00:00:00Z',
          },
        ],
        media_count: 3,
      };

      await wrapper.setProps({ album: updatedAlbum });

      // MediaGallery should receive updated media
      expect(mediaGallery.props('media')).toHaveLength(3);
    });

    it('should update UploadArea when album.id changes', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const uploadArea = wrapper.findComponent(UploadArea);
      expect(uploadArea.props('albumId')).toBe(1);

      // Update to different album
      await wrapper.setProps({ album: mockAlbumEmpty });

      // UploadArea should receive new album ID
      expect(uploadArea.props('albumId')).toBe(2);
    });
  });

  describe('Edge Cases', () => {
    it('should handle album with no media', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbumEmpty },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);
      expect(mediaGallery.props('media')).toEqual([]);
    });

    it('should handle album with large number of media items', () => {
      const largeMediaArray: Media[] = Array.from({ length: 100 }, (_, i) => ({
        id: i + 1,
        album_id: 1,
        filename: `photo${i + 1}.jpg`,
        type: 'image' as const,
        mime_type: 'image/jpeg',
        size: 1000000,
        url: `/storage/media/photo${i + 1}.jpg`,
        thumbnail_url: `/storage/thumbnails/photo${i + 1}.jpg`,
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z',
      }));

      const largeAlbum: Album = {
        ...mockAlbum,
        media: largeMediaArray,
        media_count: 100,
      };

      const wrapper = mount(AlbumContent, {
        props: { album: largeAlbum },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);
      expect(mediaGallery.props('media')).toHaveLength(100);
    });

    it('should handle rapid event emissions', async () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const mediaGallery = wrapper.findComponent(MediaGallery);

      // Emit multiple delete events rapidly
      const deletePromises = [1, 2, 3, 4, 5].map(id =>
        mediaGallery.vm.$emit('delete-media', id)
      );

      await Promise.all(deletePromises);

      // All events should be propagated
      expect(wrapper.emitted('media-deleted')).toHaveLength(5);
    });
  });

  describe('Layout and Styling', () => {
    it('should have proper CSS classes for layout', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      expect(wrapper.find('.album-content').exists()).toBe(true);
      expect(wrapper.find('.upload-section').exists()).toBe(true);
      expect(wrapper.find('.gallery-section').exists()).toBe(true);
    });

    it('should apply flexible width styling', () => {
      const wrapper = mount(AlbumContent, {
        props: { album: mockAlbum },
      });

      const albumContent = wrapper.find('.album-content');
      
      // Check that the component has the album-content class which applies width: 100%
      expect(albumContent.exists()).toBe(true);
      expect(albumContent.classes()).toContain('album-content');
    });
  });
});
