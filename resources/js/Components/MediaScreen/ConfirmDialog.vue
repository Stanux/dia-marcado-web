<script setup lang="ts">
import { watch, onMounted, onUnmounted } from 'vue';
import type { ConfirmDialogProps } from '@/types/media-screen';

/**
 * ConfirmDialog Component
 * 
 * Modal dialog for confirming destructive actions (like deleting media).
 * Displays an overlay with a confirmation message and action buttons.
 * 
 * @Requirements: 7.2
 */

const props = withDefaults(defineProps<ConfirmDialogProps>(), {
  confirmLabel: 'Confirmar',
  cancelLabel: 'Cancelar'
});

const emit = defineEmits<{
  'confirm': [];
  'cancel': [];
}>();

/**
 * Handle confirm button click
 */
const handleConfirm = () => {
  emit('confirm');
};

/**
 * Handle cancel button click
 */
const handleCancel = () => {
  emit('cancel');
};

/**
 * Handle escape key press to close dialog
 */
const handleEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.isOpen) {
    handleCancel();
  }
};

/**
 * Handle click on overlay to close dialog
 */
const handleOverlayClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    handleCancel();
  }
};

/**
 * Prevent body scroll when dialog is open
 */
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

// Add keyboard listener
onMounted(() => {
  document.addEventListener('keydown', handleEscape);
});

// Clean up
onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape);
  document.body.style.overflow = '';
});
</script>

<template>
  <Teleport to="body">
    <Transition name="dialog">
      <div
        v-if="isOpen"
        class="dialog-overlay"
        @click="handleOverlayClick"
      >
        <div class="dialog-container">
          <div class="dialog-content">
            <h3 class="dialog-title">
              {{ title }}
            </h3>
            <p class="dialog-message">
              {{ message }}
            </p>
            <div class="dialog-actions">
              <button
                class="dialog-button dialog-button-cancel"
                @click="handleCancel"
              >
                {{ cancelLabel }}
              </button>
              <button
                class="dialog-button dialog-button-confirm"
                @click="handleConfirm"
              >
                {{ confirmLabel }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Overlay */
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

/* Dialog Container */
.dialog-container {
  background-color: white;
  border-radius: 0.75rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-width: 28rem;
  width: 100%;
  overflow: hidden;
}

/* Dialog Content */
.dialog-content {
  padding: 1.5rem;
}

.dialog-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.75rem;
}

.dialog-message {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.5;
  margin-bottom: 1.5rem;
}

/* Dialog Actions */
.dialog-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.dialog-button {
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dialog-button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}

/* Cancel Button */
.dialog-button-cancel {
  background-color: #f3f4f6;
  color: #374151;
}

.dialog-button-cancel:hover {
  background-color: #e5e7eb;
}

.dialog-button-cancel:active {
  background-color: #d1d5db;
}

/* Confirm Button */
.dialog-button-confirm {
  background-color: #ef4444;
  color: white;
}

.dialog-button-confirm:hover {
  background-color: #dc2626;
}

.dialog-button-confirm:active {
  background-color: #b91c1c;
}

/* Animations */
.dialog-enter-active,
.dialog-leave-active {
  transition: opacity 0.2s ease;
}

.dialog-enter-active .dialog-container,
.dialog-leave-active .dialog-container {
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.dialog-enter-from,
.dialog-leave-to {
  opacity: 0;
}

.dialog-enter-from .dialog-container,
.dialog-leave-to .dialog-container {
  transform: scale(0.95);
  opacity: 0;
}

.dialog-enter-to .dialog-container,
.dialog-leave-from .dialog-container {
  transform: scale(1);
  opacity: 1;
}

/* Responsive */
@media (max-width: 640px) {
  .dialog-container {
    max-width: 100%;
    margin: 0 1rem;
  }

  .dialog-actions {
    flex-direction: column-reverse;
  }

  .dialog-button {
    width: 100%;
  }
}
</style>
