<template>
  <div class="notification-container">
    <TransitionGroup name="notification-list">
      <NotificationToast
        v-for="notification in notifications"
        :key="notification.id"
        :notification="notification"
        @dismiss="handleDismiss"
      />
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { useNotifications } from '@/Composables/useNotifications';
import NotificationToast from './NotificationToast.vue';

/**
 * NotificationContainer Component
 * 
 * Container for displaying notifications in a fixed position at the top-right of the screen.
 * Uses the useNotifications composable to access the global notification state.
 * 
 * @Requirements: 9.2, 9.3, 9.4
 */

const { notifications, dismiss } = useNotifications();

/**
 * Handle notification dismissal
 */
const handleDismiss = (id: string) => {
  dismiss(id);
};
</script>

<style scoped>
.notification-container {
  @apply fixed top-4 right-4 z-50;
  @apply flex flex-col gap-3;
  @apply pointer-events-none;
}

.notification-container > * {
  @apply pointer-events-auto;
}

/* Transition animations */
.notification-list-enter-active,
.notification-list-leave-active {
  transition: all 0.3s ease;
}

.notification-list-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.notification-list-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.notification-list-move {
  transition: transform 0.3s ease;
}
</style>
