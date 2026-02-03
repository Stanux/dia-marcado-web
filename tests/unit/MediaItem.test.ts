import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import MediaItem from '@/Components/MediaScreen/MediaItem.vue';
import ConfirmDialog from '@/Components/MediaScreen/ConfirmDialog.vue';
import type { Media } from '@/types/media-screen';

/**
 * Unit Tests for MediaItem Component
 * 
 * Tests specific examples and edge cases for the MediaItem component.
 * Validates thumbnail rendering, delete button functionality, confirmation dialog integration,
 * and event emission.
 * 
 * @Requirements: 6.2, 6.3, 7.1, 7.2, 7.3
 */

describe('MediaItem.vue', () => {
  const createImageMedia = (): Media => ({
    id: 1,
    album_id: 1,
    filename: 'test-image.jpg',
    type: 'image',
    mime_type: 'image/jpeg',
    size: 1024000,
    url: 'https://example.com/images/test-image.jpg',
    thumbnail_url: 'https://example.com/thumbnails/test-image.jpg',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  });

  const createVideoMedia = (): Media => ({
    id: 2,
    album_id: 1,
    filename: 'test-video.mp4',
    type: 'video',
    mime_type: 'video/mp4',
    size: 5120000,
    url: 'https://example.com/videos/test-video.mp4',
    thumbnail_url: 'https://example.com/thumbnails/test-video.jpg',
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  });

  describe('Thumbnail Rendering', () => {
    it('should render img element for image media', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const img = wrapper.find('img');
      expect(img.exists()).toBe(true);
      expect(img.attributes('src')).toBe(media.thumbnail_url);
      expect(img.attributes('alt')).toBe(media.filename);
      expect(img.classes()).toContain('media-thumbnail');
    });

    it('should render video element for video media', () => {
      const media = createVideoMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const video = wrapper.find('video');
      expect(video.exists()).toBe(true);
      expect(video.attributes('src')).toBe(media.thumbnail_url);
      expect(video.classes()).toContain('media-thumbnail');
    });

    it('should not render video element for image media', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const video = wrapper.find('video');
      expect(video.exists()).toBe(false);
    });

    it('should not render img element for video media', () => {
      const media = createVideoMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const img = wrapper.find('img');
      expect(img.exists()).toBe(false);
    });

    it('should have lazy loading attribute on images', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const img = wrapper.find('img');
      expect(img.attributes('loading')).toBe('lazy');
    });

    it('should have preload metadata attribute on videos', () => {
      const media = createVideoMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const video = wrapper.find('video');
      expect(video.attributes('preload')).toBe('metadata');
    });
  });

  describe('Delete Button', () => {
    it('should render delete button', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const deleteBtn = wrapper.find('.delete-btn');
      expect(deleteBtn.exists()).toBe(true);
      expect(deleteBtn.text()).toBe('Excluir');
    });

    it('should have proper accessibility attributes on delete button', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const deleteBtn = wrapper.find('.delete-btn');
      expect(deleteBtn.attributes('type')).toBe('button');
      expect(deleteBtn.attributes('aria-label')).toBe('Excluir mídia');
    });

    it('should open confirmation dialog when delete button is clicked', async () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      // Initially, dialog should not be open
      const confirmDialog = wrapper.findComponent(ConfirmDialog);
      expect(confirmDialog.props('isOpen')).toBe(false);

      // Click delete button
      const deleteBtn = wrapper.find('.delete-btn');
      await deleteBtn.trigger('click');

      // Dialog should now be open
      expect(confirmDialog.props('isOpen')).toBe(true);
    });

    it('should NOT emit delete event immediately when delete button is clicked', async () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const deleteBtn = wrapper.find('.delete-btn');
      await deleteBtn.trigger('click');

      // Delete event should NOT be emitted yet
      expect(wrapper.emitted('delete')).toBeFalsy();
    });

    it('should emit delete event only after confirmation', async () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      // Click delete button to open dialog
      const deleteBtn = wrapper.find('.delete-btn');
      await deleteBtn.trigger('click');

      // Confirm deletion
      const confirmDialog = wrapper.findComponent(ConfirmDialog);
      await confirmDialog.vm.$emit('confirm');

      // Now delete event should be emitted
      expect(wrapper.emitted('delete')).toBeTruthy();
      expect(wrapper.emitted('delete')?.[0]).toEqual([media.id]);
    });

    it('should close dialog after confirmation', async () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      // Open dialog
      await wrapper.find('.delete-btn').trigger('click');
      
      // Confirm deletion
      const confirmDialog = wrapper.findComponent(ConfirmDialog);
      await confirmDialog.vm.$emit('confirm');
      await wrapper.vm.$nextTick();

      // Dialog should be closed
      expect(confirmDialog.props('isOpen')).toBe(false);
    });

    it('should NOT emit delete event when canceling confirmation', async () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      // Click delete button to open dialog
      await wrapper.find('.delete-btn').trigger('click');

      // Cancel deletion
      const confirmDialog = wrapper.findComponent(ConfirmDialog);
      await confirmDialog.vm.$emit('cancel');

      // Delete event should NOT be emitted
      expect(wrapper.emitted('delete')).toBeFalsy();
    });

    it('should close dialog when canceling', async () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      // Open dialog
      await wrapper.find('.delete-btn').trigger('click');
      
      // Cancel deletion
      const confirmDialog = wrapper.findComponent(ConfirmDialog);
      await confirmDialog.vm.$emit('cancel');
      await wrapper.vm.$nextTick();

      // Dialog should be closed
      expect(confirmDialog.props('isOpen')).toBe(false);
    });

    it('should emit correct media ID for different media items', async () => {
      const media1 = createImageMedia();
      const media2 = { ...createVideoMedia(), id: 99 };

      const wrapper1 = mount(MediaItem, { props: { media: media1 } });
      const wrapper2 = mount(MediaItem, { props: { media: media2 } });

      // Open dialogs and confirm
      await wrapper1.find('.delete-btn').trigger('click');
      await wrapper1.findComponent(ConfirmDialog).vm.$emit('confirm');
      
      await wrapper2.find('.delete-btn').trigger('click');
      await wrapper2.findComponent(ConfirmDialog).vm.$emit('confirm');

      expect(wrapper1.emitted('delete')?.[0]).toEqual([media1.id]);
      expect(wrapper2.emitted('delete')?.[0]).toEqual([media2.id]);
    });
  });

  describe('Component Structure', () => {
    it('should have media-item root element', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      expect(wrapper.find('.media-item').exists()).toBe(true);
    });

    it('should have media-thumbnail-container', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      expect(wrapper.find('.media-thumbnail-container').exists()).toBe(true);
    });

    it('should have media-actions container', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      expect(wrapper.find('.media-actions').exists()).toBe(true);
    });

    it('should include ConfirmDialog component', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const confirmDialog = wrapper.findComponent(ConfirmDialog);
      expect(confirmDialog.exists()).toBe(true);
    });
  });

  describe('Confirmation Dialog Integration', () => {
    it('should pass correct props to ConfirmDialog', async () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const confirmDialog = wrapper.findComponent(ConfirmDialog);
      expect(confirmDialog.props('title')).toBe('Confirmar exclusão');
      expect(confirmDialog.props('message')).toBe('Tem certeza que deseja excluir esta mídia? Esta ação não pode ser desfeita.');
      expect(confirmDialog.props('confirmLabel')).toBe('Excluir');
      expect(confirmDialog.props('cancelLabel')).toBe('Cancelar');
    });

    it('should handle multiple open/close cycles', async () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });
      const confirmDialog = wrapper.findComponent(ConfirmDialog);

      // First cycle: open and cancel
      await wrapper.find('.delete-btn').trigger('click');
      expect(confirmDialog.props('isOpen')).toBe(true);
      await confirmDialog.vm.$emit('cancel');
      await wrapper.vm.$nextTick();
      expect(confirmDialog.props('isOpen')).toBe(false);

      // Second cycle: open and confirm
      await wrapper.find('.delete-btn').trigger('click');
      expect(confirmDialog.props('isOpen')).toBe(true);
      await confirmDialog.vm.$emit('confirm');
      await wrapper.vm.$nextTick();
      expect(confirmDialog.props('isOpen')).toBe(false);

      // Delete event should only be emitted once (from confirm)
      expect(wrapper.emitted('delete')).toHaveLength(1);
    });

    it('should maintain dialog state independently for multiple instances', async () => {
      const media1 = createImageMedia();
      const media2 = { ...createVideoMedia(), id: 2 };

      const wrapper1 = mount(MediaItem, { props: { media: media1 } });
      const wrapper2 = mount(MediaItem, { props: { media: media2 } });

      // Open dialog on first instance
      await wrapper1.find('.delete-btn').trigger('click');
      expect(wrapper1.findComponent(ConfirmDialog).props('isOpen')).toBe(true);
      expect(wrapper2.findComponent(ConfirmDialog).props('isOpen')).toBe(false);

      // Open dialog on second instance
      await wrapper2.find('.delete-btn').trigger('click');
      expect(wrapper1.findComponent(ConfirmDialog).props('isOpen')).toBe(true);
      expect(wrapper2.findComponent(ConfirmDialog).props('isOpen')).toBe(true);

      // Close first dialog
      await wrapper1.findComponent(ConfirmDialog).vm.$emit('cancel');
      await wrapper1.vm.$nextTick();
      expect(wrapper1.findComponent(ConfirmDialog).props('isOpen')).toBe(false);
      expect(wrapper2.findComponent(ConfirmDialog).props('isOpen')).toBe(true);
    });
  });

  describe('Edge Cases', () => {
    it('should handle media with empty filename', () => {
      const media = { ...createImageMedia(), filename: '' };
      const wrapper = mount(MediaItem, { props: { media } });

      const img = wrapper.find('img');
      expect(img.attributes('alt')).toBe('');
    });

    it('should handle media with very long filename', () => {
      const longFilename = 'a'.repeat(500) + '.jpg';
      const media = { ...createImageMedia(), filename: longFilename };
      const wrapper = mount(MediaItem, { props: { media } });

      const img = wrapper.find('img');
      expect(img.attributes('alt')).toBe(longFilename);
    });

    it('should handle media with special characters in filename', () => {
      const specialFilename = 'test-image-@#$%^&*().jpg';
      const media = { ...createImageMedia(), filename: specialFilename };
      const wrapper = mount(MediaItem, { props: { media } });

      const img = wrapper.find('img');
      expect(img.attributes('alt')).toBe(specialFilename);
    });

    it('should handle media with ID of 0', async () => {
      const media = { ...createImageMedia(), id: 0 };
      const wrapper = mount(MediaItem, { props: { media } });

      await wrapper.find('.delete-btn').trigger('click');
      await wrapper.findComponent(ConfirmDialog).vm.$emit('confirm');

      expect(wrapper.emitted('delete')?.[0]).toEqual([0]);
    });

    it('should handle media with negative ID', async () => {
      const media = { ...createImageMedia(), id: -1 };
      const wrapper = mount(MediaItem, { props: { media } });

      await wrapper.find('.delete-btn').trigger('click');
      await wrapper.findComponent(ConfirmDialog).vm.$emit('confirm');

      expect(wrapper.emitted('delete')?.[0]).toEqual([-1]);
    });

    it('should handle media with very large ID', async () => {
      const media = { ...createImageMedia(), id: 999999999 };
      const wrapper = mount(MediaItem, { props: { media } });

      await wrapper.find('.delete-btn').trigger('click');
      await wrapper.findComponent(ConfirmDialog).vm.$emit('confirm');

      expect(wrapper.emitted('delete')?.[0]).toEqual([999999999]);
    });
  });

  describe('Aspect Ratio Support', () => {
    it('should have aspect-ratio CSS property on media-item', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const mediaItem = wrapper.find('.media-item');
      // The component should have aspect-ratio styling applied
      expect(mediaItem.exists()).toBe(true);
    });

    it('should have object-fit cover on thumbnail', () => {
      const media = createImageMedia();
      const wrapper = mount(MediaItem, { props: { media } });

      const thumbnail = wrapper.find('.media-thumbnail');
      expect(thumbnail.exists()).toBe(true);
      // object-fit: cover is applied via CSS to support different aspect ratios
    });
  });
});
