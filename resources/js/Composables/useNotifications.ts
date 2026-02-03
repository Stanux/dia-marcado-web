/**
 * useNotifications Composable
 * 
 * Manages notification state and provides functions to show and dismiss notifications.
 * Supports auto-dismiss with configurable timeout.
 * 
 * @Requirements: 9.2, 9.3, 9.4
 */

import { ref } from 'vue';
import type { Notification, NotificationType, UseNotificationsReturn } from '@/types/media-screen';

// Global notification state (shared across all component instances)
const notifications = ref<Notification[]>([]);

// Counter for generating unique IDs
let notificationIdCounter = 0;

/**
 * Generate a unique notification ID
 */
function generateNotificationId(): string {
  return `notification-${Date.now()}-${++notificationIdCounter}`;
}

/**
 * useNotifications composable
 * 
 * Provides reactive notification management with auto-dismiss functionality
 */
export function useNotifications(): UseNotificationsReturn {
  /**
   * Show a notification
   * 
   * @param message - The message to display
   * @param type - The type of notification (default: 'info')
   * @param duration - Auto-dismiss duration in milliseconds (default: 5000, pass null for no auto-dismiss)
   * @returns The notification ID
   */
  const show = (
    message: string,
    type: NotificationType = 'info',
    duration?: number | null
  ): string => {
    const id = generateNotificationId();
    
    // Use default duration of 5000ms if not specified
    const effectiveDuration = duration === undefined ? 5000 : duration;
    
    const notification: Notification = {
      id,
      type,
      message,
      duration: effectiveDuration
    };
    
    notifications.value.push(notification);
    
    // Set up auto-dismiss if duration is specified and greater than 0
    // null or 0 means no auto-dismiss
    if (effectiveDuration !== null && effectiveDuration !== undefined && effectiveDuration > 0) {
      setTimeout(() => {
        dismiss(id);
      }, effectiveDuration);
    }
    
    return id;
  };
  
  /**
   * Dismiss a notification by ID
   * 
   * @param id - The notification ID to dismiss
   */
  const dismiss = (id: string): void => {
    notifications.value = notifications.value.filter(n => n.id !== id);
  };
  
  return {
    notifications,
    show,
    dismiss
  };
}
