/**
 * Unit Tests for NotificationContainer Component
 * 
 * Tests the notification container component including rendering notifications,
 * handling dismissal, and managing multiple notifications.
 * 
 * @Requirements: 9.2, 9.3, 9.4
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import NotificationContainer from '@/Components/MediaScreen/NotificationContainer.vue';
import NotificationToast from '@/Components/MediaScreen/NotificationToast.vue';
import { useNotifications } from '@/Composables/useNotifications';

describe('NotificationContainer', () => {
  beforeEach(() => {
    // Clear notifications before each test by directly modifying the array
    const { notifications } = useNotifications();
    notifications.value.length = 0;
  });

  describe('Rendering', () => {
    it('should render without notifications', () => {
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.find('.notification-container').exists()).toBe(true);
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(0);
    });

    it('should render single notification', () => {
      const { show } = useNotifications();
      show('Test notification');
      
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(1);
    });

    it('should render multiple notifications', () => {
      const { show } = useNotifications();
      show('Notification 1');
      show('Notification 2');
      show('Notification 3');
      
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(3);
    });

    it('should pass notification props to NotificationToast', () => {
      const { show } = useNotifications();
      show('Test message', 'success');
      
      const wrapper = mount(NotificationContainer);
      const toast = wrapper.findComponent(NotificationToast);
      
      expect(toast.props('notification')).toMatchObject({
        type: 'success',
        message: 'Test message'
      });
    });

    it('should render notifications in order', () => {
      const { show } = useNotifications();
      show('First');
      show('Second');
      show('Third');
      
      const wrapper = mount(NotificationContainer);
      const toasts = wrapper.findAllComponents(NotificationToast);
      
      expect(toasts[0].props('notification').message).toBe('First');
      expect(toasts[1].props('notification').message).toBe('Second');
      expect(toasts[2].props('notification').message).toBe('Third');
    });
  });

  describe('Dismissal', () => {
    it('should handle dismiss event from NotificationToast', async () => {
      const { show, notifications } = useNotifications();
      const id = show('Test notification');
      
      const wrapper = mount(NotificationContainer);
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(1);
      
      // Trigger dismiss event
      const toast = wrapper.findComponent(NotificationToast);
      await toast.vm.$emit('dismiss', id);
      
      // Wait for reactivity
      await wrapper.vm.$nextTick();
      
      expect(notifications.value).toHaveLength(0);
    });

    it('should remove only the dismissed notification', async () => {
      const { show, notifications } = useNotifications();
      const id1 = show('Notification 1');
      const id2 = show('Notification 2');
      const id3 = show('Notification 3');
      
      const wrapper = mount(NotificationContainer);
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(3);
      
      // Dismiss the second notification
      const toasts = wrapper.findAllComponents(NotificationToast);
      await toasts[1].vm.$emit('dismiss', id2);
      await wrapper.vm.$nextTick();
      
      expect(notifications.value).toHaveLength(2);
      expect(notifications.value[0].id).toBe(id1);
      expect(notifications.value[1].id).toBe(id3);
    });

    it('should update rendered toasts after dismissal', async () => {
      const { show } = useNotifications();
      show('Notification 1');
      const id2 = show('Notification 2');
      
      const wrapper = mount(NotificationContainer);
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(2);
      
      // Dismiss second notification
      const toasts = wrapper.findAllComponents(NotificationToast);
      await toasts[1].vm.$emit('dismiss', id2);
      await wrapper.vm.$nextTick();
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(1);
      expect(wrapper.findComponent(NotificationToast).props('notification').message).toBe('Notification 1');
    });
  });

  describe('Dynamic Updates', () => {
    it('should update when new notification is added', async () => {
      const { show } = useNotifications();
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(0);
      
      show('New notification');
      await wrapper.vm.$nextTick();
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(1);
    });

    it('should update when notification is removed', async () => {
      const { show, dismiss } = useNotifications();
      const id = show('Test notification');
      
      const wrapper = mount(NotificationContainer);
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(1);
      
      dismiss(id);
      await wrapper.vm.$nextTick();
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(0);
    });

    it('should handle rapid additions and removals', async () => {
      const { show, dismiss } = useNotifications();
      const wrapper = mount(NotificationContainer);
      
      // Add multiple notifications
      const id1 = show('Notification 1');
      const id2 = show('Notification 2');
      const id3 = show('Notification 3');
      await wrapper.vm.$nextTick();
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(3);
      
      // Remove some
      dismiss(id1);
      dismiss(id3);
      await wrapper.vm.$nextTick();
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(1);
      expect(wrapper.findComponent(NotificationToast).props('notification').id).toBe(id2);
    });
  });

  describe('Notification Types', () => {
    it('should render success notifications', async () => {
      const { show } = useNotifications();
      show('Success message', 'success');
      
      const wrapper = mount(NotificationContainer);
      const toast = wrapper.findComponent(NotificationToast);
      
      expect(toast.props('notification').type).toBe('success');
    });

    it('should render error notifications', async () => {
      const { show } = useNotifications();
      show('Error message', 'error');
      
      const wrapper = mount(NotificationContainer);
      const toast = wrapper.findComponent(NotificationToast);
      
      expect(toast.props('notification').type).toBe('error');
    });

    it('should render warning notifications', async () => {
      const { show } = useNotifications();
      show('Warning message', 'warning');
      
      const wrapper = mount(NotificationContainer);
      const toast = wrapper.findComponent(NotificationToast);
      
      expect(toast.props('notification').type).toBe('warning');
    });

    it('should render info notifications', async () => {
      const { show } = useNotifications();
      show('Info message', 'info');
      
      const wrapper = mount(NotificationContainer);
      const toast = wrapper.findComponent(NotificationToast);
      
      expect(toast.props('notification').type).toBe('info');
    });

    it('should render mixed notification types', async () => {
      const { show } = useNotifications();
      show('Success', 'success');
      show('Error', 'error');
      show('Warning', 'warning');
      show('Info', 'info');
      
      const wrapper = mount(NotificationContainer);
      const toasts = wrapper.findAllComponents(NotificationToast);
      
      expect(toasts).toHaveLength(4);
      expect(toasts[0].props('notification').type).toBe('success');
      expect(toasts[1].props('notification').type).toBe('error');
      expect(toasts[2].props('notification').type).toBe('warning');
      expect(toasts[3].props('notification').type).toBe('info');
    });
  });

  describe('Styling and Layout', () => {
    it('should have notification-container class', () => {
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.find('.notification-container').exists()).toBe(true);
    });

    it('should have container element', () => {
      const wrapper = mount(NotificationContainer);
      const container = wrapper.find('.notification-container');
      
      expect(container.exists()).toBe(true);
      expect(container.element.tagName).toBe('DIV');
    });
  });

  describe('Edge Cases', () => {
    it('should handle empty notifications array', () => {
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(0);
      expect(wrapper.find('.notification-container').exists()).toBe(true);
    });

    it('should handle many notifications', async () => {
      const { show } = useNotifications();
      
      for (let i = 0; i < 20; i++) {
        show(`Notification ${i}`);
      }
      
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(20);
    });

    it('should handle notifications with same message', async () => {
      const { show } = useNotifications();
      show('Same message');
      show('Same message');
      show('Same message');
      
      const wrapper = mount(NotificationContainer);
      const toasts = wrapper.findAllComponents(NotificationToast);
      
      expect(toasts).toHaveLength(3);
      // Each should have unique ID
      const ids = toasts.map(t => t.props('notification').id);
      expect(new Set(ids).size).toBe(3);
    });

    it('should handle notifications with different durations', async () => {
      const { show } = useNotifications();
      show('Short', 'info', 1000);
      show('Medium', 'info', 5000);
      show('Long', 'info', 10000);
      show('Persistent', 'info', null);
      
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(4);
    });
  });

  describe('Integration with useNotifications', () => {
    it('should reflect global notification state', async () => {
      const { show, notifications } = useNotifications();
      const wrapper = mount(NotificationContainer);
      
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(0);
      
      show('Test 1');
      await wrapper.vm.$nextTick();
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(1);
      expect(notifications.value).toHaveLength(1);
      
      show('Test 2');
      await wrapper.vm.$nextTick();
      expect(wrapper.findAllComponents(NotificationToast)).toHaveLength(2);
      expect(notifications.value).toHaveLength(2);
    });

    it('should work with multiple container instances', async () => {
      const { show } = useNotifications();
      
      const wrapper1 = mount(NotificationContainer);
      const wrapper2 = mount(NotificationContainer);
      
      show('Shared notification');
      await wrapper1.vm.$nextTick();
      await wrapper2.vm.$nextTick();
      
      // Both containers should show the same notification
      expect(wrapper1.findAllComponents(NotificationToast)).toHaveLength(1);
      expect(wrapper2.findAllComponents(NotificationToast)).toHaveLength(1);
    });
  });
});
