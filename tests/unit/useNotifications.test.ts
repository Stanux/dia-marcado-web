/**
 * Unit Tests for useNotifications Composable
 * 
 * Tests the notification management composable including showing notifications,
 * dismissing them, and auto-dismiss functionality.
 * 
 * @Requirements: 9.2, 9.3, 9.4
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useNotifications } from '@/Composables/useNotifications';

describe('useNotifications', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    // Clear any existing notifications before each test by directly modifying the array
    const { notifications } = useNotifications();
    notifications.value.length = 0;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  describe('Initialization', () => {
    it('should initialize with empty notifications array', () => {
      const { notifications } = useNotifications();
      
      expect(notifications.value).toEqual([]);
      expect(notifications.value).toHaveLength(0);
    });
  });

  describe('show', () => {
    it('should add a notification with default type and duration', () => {
      const { notifications, show } = useNotifications();
      
      const id = show('Test message');
      
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0]).toMatchObject({
        id,
        type: 'info',
        message: 'Test message',
        duration: 5000
      });
    });

    it('should add a notification with custom type', () => {
      const { notifications, show } = useNotifications();
      
      show('Success message', 'success');
      
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0].type).toBe('success');
      expect(notifications.value[0].message).toBe('Success message');
    });

    it('should add a notification with custom duration', () => {
      const { notifications, show } = useNotifications();
      
      show('Custom duration', 'info', 3000);
      
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0].duration).toBe(3000);
    });

    it('should add error notification', () => {
      const { notifications, show } = useNotifications();
      
      show('Error message', 'error');
      
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0].type).toBe('error');
    });

    it('should add warning notification', () => {
      const { notifications, show } = useNotifications();
      
      show('Warning message', 'warning');
      
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0].type).toBe('warning');
    });

    it('should return unique notification ID', () => {
      const { show } = useNotifications();
      
      const id1 = show('Message 1');
      const id2 = show('Message 2');
      
      expect(id1).not.toBe(id2);
      expect(id1).toMatch(/^notification-\d+-\d+$/);
      expect(id2).toMatch(/^notification-\d+-\d+$/);
    });

    it('should add multiple notifications', () => {
      const { notifications, show } = useNotifications();
      
      show('Message 1', 'info');
      show('Message 2', 'success');
      show('Message 3', 'error');
      
      expect(notifications.value).toHaveLength(3);
      expect(notifications.value[0].message).toBe('Message 1');
      expect(notifications.value[1].message).toBe('Message 2');
      expect(notifications.value[2].message).toBe('Message 3');
    });

    it('should handle notification without auto-dismiss when duration is null', () => {
      const { notifications, show } = useNotifications();
      
      show('Persistent message', 'info', null);
      
      expect(notifications.value).toHaveLength(1);
      
      // Fast-forward time
      vi.advanceTimersByTime(10000);
      
      // Notification should still be there
      expect(notifications.value).toHaveLength(1);
    });

    it('should handle notification without auto-dismiss when duration is 0', () => {
      const { notifications, show } = useNotifications();
      
      show('Persistent message', 'info', 0);
      
      expect(notifications.value).toHaveLength(1);
      
      // Fast-forward time
      vi.advanceTimersByTime(10000);
      
      // Notification should still be there
      expect(notifications.value).toHaveLength(1);
    });
  });

  describe('dismiss', () => {
    it('should remove notification by ID', () => {
      const { notifications, show, dismiss } = useNotifications();
      
      const id = show('Test message');
      expect(notifications.value).toHaveLength(1);
      
      dismiss(id);
      
      expect(notifications.value).toHaveLength(0);
    });

    it('should remove only the specified notification', () => {
      const { notifications, show, dismiss } = useNotifications();
      
      const id1 = show('Message 1');
      const id2 = show('Message 2');
      const id3 = show('Message 3');
      
      expect(notifications.value).toHaveLength(3);
      
      dismiss(id2);
      
      expect(notifications.value).toHaveLength(2);
      expect(notifications.value[0].id).toBe(id1);
      expect(notifications.value[1].id).toBe(id3);
    });

    it('should handle dismissing non-existent notification', () => {
      const { notifications, show, dismiss } = useNotifications();
      
      show('Test message');
      expect(notifications.value).toHaveLength(1);
      
      dismiss('non-existent-id');
      
      expect(notifications.value).toHaveLength(1);
    });

    it('should handle dismissing from empty list', () => {
      const { notifications, dismiss } = useNotifications();
      
      expect(notifications.value).toHaveLength(0);
      
      dismiss('some-id');
      
      expect(notifications.value).toHaveLength(0);
    });
  });

  describe('Auto-dismiss', () => {
    it('should auto-dismiss notification after default duration', () => {
      const { notifications, show } = useNotifications();
      
      show('Auto-dismiss message');
      expect(notifications.value).toHaveLength(1);
      
      // Fast-forward time by default duration (5000ms)
      vi.advanceTimersByTime(5000);
      
      expect(notifications.value).toHaveLength(0);
    });

    it('should auto-dismiss notification after custom duration', () => {
      const { notifications, show } = useNotifications();
      
      show('Custom duration', 'info', 3000);
      expect(notifications.value).toHaveLength(1);
      
      // Fast-forward time by less than duration
      vi.advanceTimersByTime(2000);
      expect(notifications.value).toHaveLength(1);
      
      // Fast-forward remaining time
      vi.advanceTimersByTime(1000);
      expect(notifications.value).toHaveLength(0);
    });

    it('should auto-dismiss multiple notifications independently', () => {
      const { notifications, show } = useNotifications();
      
      show('Message 1', 'info', 2000);
      show('Message 2', 'info', 4000);
      show('Message 3', 'info', 6000);
      
      expect(notifications.value).toHaveLength(3);
      
      // After 2 seconds, first notification should be dismissed
      vi.advanceTimersByTime(2000);
      expect(notifications.value).toHaveLength(2);
      expect(notifications.value[0].message).toBe('Message 2');
      
      // After 4 seconds total, second notification should be dismissed
      vi.advanceTimersByTime(2000);
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0].message).toBe('Message 3');
      
      // After 6 seconds total, third notification should be dismissed
      vi.advanceTimersByTime(2000);
      expect(notifications.value).toHaveLength(0);
    });

    it('should not auto-dismiss if manually dismissed first', () => {
      const { notifications, show, dismiss } = useNotifications();
      
      const id = show('Test message', 'info', 5000);
      expect(notifications.value).toHaveLength(1);
      
      // Manually dismiss before timeout
      dismiss(id);
      expect(notifications.value).toHaveLength(0);
      
      // Fast-forward time
      vi.advanceTimersByTime(5000);
      
      // Should still be empty (no error from trying to dismiss again)
      expect(notifications.value).toHaveLength(0);
    });
  });

  describe('Edge Cases', () => {
    it('should handle empty message', () => {
      const { notifications, show } = useNotifications();
      
      show('');
      
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0].message).toBe('');
    });

    it('should handle very long message', () => {
      const { notifications, show } = useNotifications();
      const longMessage = 'A'.repeat(1000);
      
      show(longMessage);
      
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0].message).toBe(longMessage);
    });

    it('should handle special characters in message', () => {
      const { notifications, show } = useNotifications();
      const specialMessage = '<script>alert("XSS")</script>';
      
      show(specialMessage);
      
      expect(notifications.value).toHaveLength(1);
      expect(notifications.value[0].message).toBe(specialMessage);
    });

    it('should handle rapid successive notifications', () => {
      const { notifications, show } = useNotifications();
      
      for (let i = 0; i < 10; i++) {
        show(`Message ${i}`);
      }
      
      expect(notifications.value).toHaveLength(10);
    });

    it('should maintain notification order', () => {
      const { notifications, show } = useNotifications();
      
      show('First');
      show('Second');
      show('Third');
      
      expect(notifications.value[0].message).toBe('First');
      expect(notifications.value[1].message).toBe('Second');
      expect(notifications.value[2].message).toBe('Third');
    });
  });

  describe('Shared State', () => {
    it('should share notifications across multiple composable instances', () => {
      const instance1 = useNotifications();
      const instance2 = useNotifications();
      
      instance1.show('Message from instance 1');
      
      expect(instance1.notifications.value).toHaveLength(1);
      expect(instance2.notifications.value).toHaveLength(1);
      expect(instance1.notifications.value[0]).toBe(instance2.notifications.value[0]);
    });

    it('should allow dismissal from any instance', () => {
      const instance1 = useNotifications();
      const instance2 = useNotifications();
      
      const id = instance1.show('Shared message');
      expect(instance1.notifications.value).toHaveLength(1);
      expect(instance2.notifications.value).toHaveLength(1);
      
      instance2.dismiss(id);
      
      expect(instance1.notifications.value).toHaveLength(0);
      expect(instance2.notifications.value).toHaveLength(0);
    });
  });
});
