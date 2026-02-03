/**
 * Unit Tests for NotificationToast Component
 * 
 * Tests the notification toast component including rendering different types,
 * displaying messages, and handling dismissal.
 * 
 * @Requirements: 9.2, 9.3, 9.4
 */

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import NotificationToast from '@/Components/MediaScreen/NotificationToast.vue';
import type { Notification } from '@/types/media-screen';

describe('NotificationToast', () => {
  const createNotification = (overrides?: Partial<Notification>): Notification => ({
    id: 'test-notification-1',
    type: 'info',
    message: 'Test message',
    duration: 5000,
    ...overrides
  });

  describe('Rendering', () => {
    it('should render notification message', () => {
      const notification = createNotification({ message: 'Test notification message' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.text()).toContain('Test notification message');
    });

    it('should render with info type styling', () => {
      const notification = createNotification({ type: 'info' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').classes()).toContain('notification-toast--info');
    });

    it('should render with success type styling', () => {
      const notification = createNotification({ type: 'success' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').classes()).toContain('notification-toast--success');
    });

    it('should render with error type styling', () => {
      const notification = createNotification({ type: 'error' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').classes()).toContain('notification-toast--error');
    });

    it('should render with warning type styling', () => {
      const notification = createNotification({ type: 'warning' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').classes()).toContain('notification-toast--warning');
    });

    it('should render close button', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      const closeButton = wrapper.find('.notification-toast__close');
      expect(closeButton.exists()).toBe(true);
      expect(closeButton.attributes('aria-label')).toBe('Fechar notificação');
    });

    it('should render icon', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast__icon').exists()).toBe(true);
    });

    it('should have animation class', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').classes()).toContain('animate-slide-in');
    });
  });

  describe('Accessibility', () => {
    it('should have role="alert"', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').attributes('role')).toBe('alert');
    });

    it('should have aria-live="assertive" for error notifications', () => {
      const notification = createNotification({ type: 'error' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').attributes('aria-live')).toBe('assertive');
    });

    it('should have aria-live="polite" for non-error notifications', () => {
      const types: Array<'info' | 'success' | 'warning'> = ['info', 'success', 'warning'];
      
      types.forEach(type => {
        const notification = createNotification({ type });
        const wrapper = mount(NotificationToast, {
          props: { notification }
        });
        
        expect(wrapper.find('.notification-toast').attributes('aria-live')).toBe('polite');
      });
    });

    it('should have accessible close button label', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      const closeButton = wrapper.find('.notification-toast__close');
      expect(closeButton.attributes('aria-label')).toBe('Fechar notificação');
      expect(closeButton.attributes('type')).toBe('button');
    });
  });

  describe('Dismiss Functionality', () => {
    it('should emit dismiss event when close button is clicked', async () => {
      const notification = createNotification({ id: 'test-id-123' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      await wrapper.find('.notification-toast__close').trigger('click');
      
      expect(wrapper.emitted('dismiss')).toBeTruthy();
      expect(wrapper.emitted('dismiss')?.[0]).toEqual(['test-id-123']);
    });

    it('should emit correct notification ID', async () => {
      const notification = createNotification({ id: 'unique-notification-id' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      await wrapper.find('.notification-toast__close').trigger('click');
      
      expect(wrapper.emitted('dismiss')?.[0]).toEqual(['unique-notification-id']);
    });

    it('should only emit dismiss once per click', async () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      await wrapper.find('.notification-toast__close').trigger('click');
      
      expect(wrapper.emitted('dismiss')).toHaveLength(1);
    });
  });

  describe('Icon Display', () => {
    it('should display success icon for success type', () => {
      const notification = createNotification({ type: 'success' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      const icon = wrapper.find('.notification-toast__icon svg');
      expect(icon.exists()).toBe(true);
    });

    it('should display error icon for error type', () => {
      const notification = createNotification({ type: 'error' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      const icon = wrapper.find('.notification-toast__icon svg');
      expect(icon.exists()).toBe(true);
    });

    it('should display warning icon for warning type', () => {
      const notification = createNotification({ type: 'warning' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      const icon = wrapper.find('.notification-toast__icon svg');
      expect(icon.exists()).toBe(true);
    });

    it('should display info icon for info type', () => {
      const notification = createNotification({ type: 'info' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      const icon = wrapper.find('.notification-toast__icon svg');
      expect(icon.exists()).toBe(true);
    });
  });

  describe('Edge Cases', () => {
    it('should handle empty message', () => {
      const notification = createNotification({ message: '' });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast__message').exists()).toBe(true);
      expect(wrapper.find('.notification-toast__message').text()).toBe('');
    });

    it('should handle very long message', () => {
      const longMessage = 'A'.repeat(500);
      const notification = createNotification({ message: longMessage });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.text()).toContain(longMessage);
    });

    it('should handle special characters in message', () => {
      const specialMessage = '<script>alert("XSS")</script>';
      const notification = createNotification({ message: specialMessage });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      // Vue should escape the HTML
      expect(wrapper.text()).toContain(specialMessage);
      expect(wrapper.html()).not.toContain('<script>');
    });

    it('should handle message with line breaks', () => {
      const messageWithBreaks = 'Line 1\nLine 2\nLine 3';
      const notification = createNotification({ message: messageWithBreaks });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.text()).toContain(messageWithBreaks);
    });

    it('should handle notification without duration', () => {
      const notification = createNotification({ duration: null });
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').exists()).toBe(true);
    });
  });

  describe('Styling', () => {
    it('should have base notification-toast class', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast').exists()).toBe(true);
    });

    it('should have content section', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast__content').exists()).toBe(true);
    });

    it('should have message element', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.find('.notification-toast__message').exists()).toBe(true);
    });
  });

  describe('Props Validation', () => {
    it('should accept valid notification prop', () => {
      const notification = createNotification();
      const wrapper = mount(NotificationToast, {
        props: { notification }
      });
      
      expect(wrapper.props('notification')).toEqual(notification);
    });

    it('should render different notifications correctly', () => {
      const notifications = [
        createNotification({ id: '1', message: 'Message 1', type: 'info' }),
        createNotification({ id: '2', message: 'Message 2', type: 'success' }),
        createNotification({ id: '3', message: 'Message 3', type: 'error' }),
        createNotification({ id: '4', message: 'Message 4', type: 'warning' })
      ];
      
      notifications.forEach(notification => {
        const wrapper = mount(NotificationToast, {
          props: { notification }
        });
        
        expect(wrapper.text()).toContain(notification.message);
        expect(wrapper.find('.notification-toast').classes()).toContain(`notification-toast--${notification.type}`);
      });
    });
  });
});
