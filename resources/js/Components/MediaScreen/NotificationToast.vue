<template>
  <div 
    :class="[
      'notification-toast',
      `notification-toast--${notification.type}`,
      'animate-slide-in'
    ]"
    role="alert"
    :aria-live="notification.type === 'error' ? 'assertive' : 'polite'"
  >
    <div class="notification-toast__icon">
      <!-- Success Icon -->
      <svg v-if="notification.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      
      <!-- Error Icon -->
      <svg v-else-if="notification.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
      
      <!-- Warning Icon -->
      <svg v-else-if="notification.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      
      <!-- Info Icon (default) -->
      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    
    <div class="notification-toast__content">
      <p class="notification-toast__message">{{ notification.message }}</p>
    </div>
    
    <button
      class="notification-toast__close"
      @click="handleDismiss"
      aria-label="Fechar notificação"
      type="button"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import type { NotificationToastProps, NotificationToastEvents } from '@/types/media-screen';

/**
 * NotificationToast Component
 * 
 * Displays a single notification with appropriate styling and icon based on type.
 * Supports manual dismissal via close button.
 * 
 * @Requirements: 9.2, 9.3, 9.4
 */

const props = defineProps<NotificationToastProps>();
const emit = defineEmits<NotificationToastEvents>();

/**
 * Handle dismiss button click
 */
const handleDismiss = () => {
  emit('dismiss', props.notification.id);
};
</script>

<style scoped>
.notification-toast {
  @apply flex items-start gap-3 p-4 rounded-lg shadow-lg min-w-[320px] max-w-md;
  @apply transition-all duration-300 ease-in-out;
}

.notification-toast--success {
  @apply bg-green-50 border border-green-200 text-green-800;
}

.notification-toast--success .notification-toast__icon {
  @apply text-green-600;
}

.notification-toast--error {
  @apply bg-red-50 border border-red-200 text-red-800;
}

.notification-toast--error .notification-toast__icon {
  @apply text-red-600;
}

.notification-toast--warning {
  @apply bg-yellow-50 border border-yellow-200 text-yellow-800;
}

.notification-toast--warning .notification-toast__icon {
  @apply text-yellow-600;
}

.notification-toast--info {
  @apply bg-blue-50 border border-blue-200 text-blue-800;
}

.notification-toast--info .notification-toast__icon {
  @apply text-blue-600;
}

.notification-toast__icon {
  @apply flex-shrink-0 mt-0.5;
}

.notification-toast__content {
  @apply flex-1;
}

.notification-toast__message {
  @apply text-sm font-medium;
}

.notification-toast__close {
  @apply flex-shrink-0 text-current opacity-70 hover:opacity-100;
  @apply transition-opacity duration-200;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-current rounded;
}

/* Animation */
@keyframes slide-in {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.animate-slide-in {
  animation: slide-in 0.3s ease-out;
}
</style>
