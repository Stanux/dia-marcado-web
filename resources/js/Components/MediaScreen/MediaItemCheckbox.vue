<template>
  <div 
    v-if="isSelectionMode || isSelected"
    class="media-checkbox-container"
    @click.stop="handleToggle"
  >
    <div 
      class="checkbox"
      :class="{ 'checked': isSelected }"
      role="checkbox"
      :aria-checked="isSelected"
      tabindex="0"
      @keydown.enter.prevent="handleToggle"
      @keydown.space.prevent="handleToggle"
    >
      <svg 
        v-if="isSelected"
        xmlns="http://www.w3.org/2000/svg" 
        viewBox="0 0 24 24" 
        fill="currentColor" 
        class="check-icon"
      >
        <path 
          fill-rule="evenodd" 
          d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" 
          clip-rule="evenodd" 
        />
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { MediaItemCheckboxProps, MediaItemCheckboxEvents } from '@/types/media-screen';

/**
 * MediaItemCheckbox Component
 * 
 * Displays a checkbox overlay on media items for selection.
 * Only visible when selection mode is active or when the item is selected.
 * Provides visual feedback for selected state.
 * 
 * @Requirements: Fase 1 - Seleção múltipla
 */

const props = defineProps<MediaItemCheckboxProps>();

const emit = defineEmits<MediaItemCheckboxEvents>();

/**
 * Handle checkbox toggle
 * Emits toggle event to parent component
 */
function handleToggle(): void {
  emit('toggle');
}
</script>

<style scoped>
.media-checkbox-container {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  z-index: 10;
  cursor: pointer;
}

.checkbox {
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 0.375rem;
  border: 2px solid white;
  background-color: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease-in-out;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.checkbox:hover {
  background-color: rgba(0, 0, 0, 0.5);
  transform: scale(1.1);
}

.checkbox.checked {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

.checkbox:focus {
  outline: 2px solid white;
  outline-offset: 2px;
}

.check-icon {
  width: 1rem;
  height: 1rem;
  color: white;
  animation: checkmark-appear 0.2s ease-in-out;
}

@keyframes checkmark-appear {
  from {
    opacity: 0;
    transform: scale(0.5);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Mobile adjustments */
@media (max-width: 640px) {
  .media-checkbox-container {
    top: 0.375rem;
    left: 0.375rem;
  }

  .checkbox {
    width: 1.25rem;
    height: 1.25rem;
  }

  .check-icon {
    width: 0.875rem;
    height: 0.875rem;
  }
}
</style>
