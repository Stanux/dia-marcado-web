/**
 * Unit Tests for MediaGallery Component
 * 
 * Tests the MediaGallery component's rendering, empty state, and event handling.
 * 
 * @Requirements: 6.1, 7.1, 8.2
 */

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import MediaGallery from '@/Components/MediaScreen/MediaGallery.vue';
import MediaItem from '@/Components/MediaScreen/MediaItem.vue';
import type { Media } from '@/types/media-screen';

describe('MediaGallery', () => {
  /**
   * Test: Empty state rendering
   * Validates Requisito 8.2: Display empty state when album has no media
   */
  it('deve exibir estado vazio quando não há mídias', () => {
    const wrapper = mount(MediaGallery, {
      props: {
        media: []
      }
    });

    // Should show empty state
    expect(wrapper.find('.empty-state').exists()).toBe(true);
    
    // Should show appropriate message
    expect(wrapper.text()).toContain('Nenhuma mídia neste álbum');
    expect(wrapper.text()).toContain('Faça upload de fotos e vídeos');
    
    // Should not show gallery grid
    expect(wrapper.find('.gallery-grid').exists()).toBe(false);
  });

  /**
   * Test: Rendering all media items
   * Validates Requisito 6.1: Display all media from selected album
   */
  it('deve renderizar todas as mídias fornecidas', () => {
    const media: Media[] = [
      {
        id: 1,
        album_id: 1,
        filename: 'photo1.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/storage/photo1.jpg',
        thumbnail_url: '/storage/thumbs/photo1.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      },
      {
        id: 2,
        album_id: 1,
        filename: 'photo2.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 2048,
        url: '/storage/photo2.jpg',
        thumbnail_url: '/storage/thumbs/photo2.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ];

    const wrapper = mount(MediaGallery, {
      props: { media },
      global: {
        stubs: {
          MediaItem: true
        }
      }
    });

    // Should show gallery grid
    expect(wrapper.find('.gallery-grid').exists()).toBe(true);
    
    // Should not show empty state
    expect(wrapper.find('.empty-state').exists()).toBe(false);
    
    // Should render all media items
    const mediaItems = wrapper.findAllComponents(MediaItem);
    expect(mediaItems).toHaveLength(2);
  });

  /**
   * Test: Rendering single media item
   * Edge case: gallery with only one item
   */
  it('deve renderizar corretamente com apenas uma mídia', () => {
    const media: Media[] = [
      {
        id: 1,
        album_id: 1,
        filename: 'single.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/storage/single.jpg',
        thumbnail_url: '/storage/thumbs/single.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ];

    const wrapper = mount(MediaGallery, {
      props: { media },
      global: {
        stubs: {
          MediaItem: true
        }
      }
    });

    // Should show gallery grid
    expect(wrapper.find('.gallery-grid').exists()).toBe(true);
    
    // Should render exactly one media item
    const mediaItems = wrapper.findAllComponents(MediaItem);
    expect(mediaItems).toHaveLength(1);
  });

  /**
   * Test: Delete event emission
   * Validates Requisito 7.1: Each media should have delete button that emits event
   */
  it('deve emitir evento delete-media ao clicar em excluir', async () => {
    const media: Media[] = [
      {
        id: 1,
        album_id: 1,
        filename: 'photo1.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/storage/photo1.jpg',
        thumbnail_url: '/storage/thumbs/photo1.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ];

    const wrapper = mount(MediaGallery, {
      props: { media }
    });

    // Find the delete button in MediaItem and click it
    const deleteBtn = wrapper.find('.delete-btn');
    await deleteBtn.trigger('click');
    
    // Wait for dialog to appear
    await wrapper.vm.$nextTick();
    
    // Find and click the confirm button in the dialog
    const confirmBtn = wrapper.find('.dialog-button-confirm');
    await confirmBtn.trigger('click');
    
    // Wait for event to be emitted
    await wrapper.vm.$nextTick();

    // Should emit delete-media event with correct media ID
    expect(wrapper.emitted('delete-media')).toBeTruthy();
    expect(wrapper.emitted('delete-media')?.[0]).toEqual([1]);
  });

  /**
   * Test: Multiple delete events
   * Ensures each media item can emit delete independently
   */
  it('deve emitir eventos delete-media corretos para múltiplas mídias', async () => {
    const media: Media[] = [
      {
        id: 1,
        album_id: 1,
        filename: 'photo1.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/storage/photo1.jpg',
        thumbnail_url: '/storage/thumbs/photo1.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      },
      {
        id: 2,
        album_id: 1,
        filename: 'photo2.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 2048,
        url: '/storage/photo2.jpg',
        thumbnail_url: '/storage/thumbs/photo2.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ];

    const wrapper = mount(MediaGallery, {
      props: { media }
    });

    // Click delete on first media item
    const deleteButtons = wrapper.findAll('.delete-btn');
    await deleteButtons[0].trigger('click');
    await wrapper.vm.$nextTick();
    
    // Confirm first deletion
    let confirmBtn = wrapper.find('.dialog-button-confirm');
    await confirmBtn.trigger('click');
    await wrapper.vm.$nextTick();

    // Should emit event with first media ID
    expect(wrapper.emitted('delete-media')?.[0]).toEqual([1]);

    // Click delete on second media item
    await deleteButtons[1].trigger('click');
    await wrapper.vm.$nextTick();
    
    // Confirm second deletion
    confirmBtn = wrapper.find('.dialog-button-confirm');
    await confirmBtn.trigger('click');
    await wrapper.vm.$nextTick();

    // Should emit event with second media ID
    expect(wrapper.emitted('delete-media')?.[1]).toEqual([2]);
  });

  /**
   * Test: Props passing to MediaItem
   * Ensures media data is correctly passed to child components
   */
  it('deve passar props corretas para MediaItem', () => {
    const media: Media[] = [
      {
        id: 1,
        album_id: 1,
        filename: 'test.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/storage/test.jpg',
        thumbnail_url: '/storage/thumbs/test.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ];

    const wrapper = mount(MediaGallery, {
      props: { media }
    });

    const mediaItem = wrapper.findComponent(MediaItem);
    expect(mediaItem.props('media')).toEqual(media[0]);
  });

  /**
   * Test: Video media rendering
   * Ensures both images and videos are rendered correctly
   */
  it('deve renderizar mídias de vídeo corretamente', () => {
    const media: Media[] = [
      {
        id: 1,
        album_id: 1,
        filename: 'video.mp4',
        type: 'video',
        mime_type: 'video/mp4',
        size: 5120,
        url: '/storage/video.mp4',
        thumbnail_url: '/storage/thumbs/video.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ];

    const wrapper = mount(MediaGallery, {
      props: { media }
    });

    // Should render the video media item
    const mediaItems = wrapper.findAllComponents(MediaItem);
    expect(mediaItems).toHaveLength(1);
    expect(mediaItems[0].props('media').type).toBe('video');
  });

  /**
   * Test: Mixed media types
   * Ensures gallery can handle both images and videos together
   */
  it('deve renderizar mídias mistas (imagens e vídeos)', () => {
    const media: Media[] = [
      {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/storage/photo.jpg',
        thumbnail_url: '/storage/thumbs/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      },
      {
        id: 2,
        album_id: 1,
        filename: 'video.mp4',
        type: 'video',
        mime_type: 'video/mp4',
        size: 5120,
        url: '/storage/video.mp4',
        thumbnail_url: '/storage/thumbs/video.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      }
    ];

    const wrapper = mount(MediaGallery, {
      props: { media }
    });

    // Should render both media items
    const mediaItems = wrapper.findAllComponents(MediaItem);
    expect(mediaItems).toHaveLength(2);
    expect(mediaItems[0].props('media').type).toBe('image');
    expect(mediaItems[1].props('media').type).toBe('video');
  });

  /**
   * Test: Large number of media items
   * Edge case: gallery with many items
   */
  it('deve renderizar corretamente com muitas mídias', () => {
    // Create 50 media items
    const media: Media[] = Array.from({ length: 50 }, (_, i) => ({
      id: i + 1,
      album_id: 1,
      filename: `photo${i + 1}.jpg`,
      type: 'image' as const,
      mime_type: 'image/jpeg',
      size: 1024,
      url: `/storage/photo${i + 1}.jpg`,
      thumbnail_url: `/storage/thumbs/photo${i + 1}.jpg`,
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z'
    }));

    const wrapper = mount(MediaGallery, {
      props: { media },
      global: {
        stubs: {
          MediaItem: true
        }
      }
    });

    // Should render all 50 items
    const mediaItems = wrapper.findAllComponents(MediaItem);
    expect(mediaItems).toHaveLength(50);
  });
});
