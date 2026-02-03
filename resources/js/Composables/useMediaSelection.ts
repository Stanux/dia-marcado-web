/**
 * useMediaSelection Composable
 * 
 * Manages media selection state for bulk operations like moving or deleting multiple media items.
 * Provides reactive state for selected media IDs, selection mode, and operations.
 * 
 * @Requirements: Fase 1 - Seleção múltipla + menu de ações
 */

import { ref, computed } from 'vue';
import type { UseMediaSelectionReturn } from '@/types/media-screen';

/**
 * Composable for managing media selection
 * 
 * @returns Object containing selection state and operations
 */
export function useMediaSelection(): UseMediaSelectionReturn {
  // Reactive state
  const selectedMediaIds = ref<Set<string>>(new Set());
  const isSelectionMode = ref(false);

  /**
   * Computed property for selected count
   */
  const selectedCount = computed(() => selectedMediaIds.value.size);

  /**
   * Check if a media item is selected
   * 
   * @param mediaId - ID of the media to check
   * @returns True if media is selected
   */
  const isSelected = (mediaId: string): boolean => {
    return selectedMediaIds.value.has(mediaId);
  };

  /**
   * Toggle selection of a media item
   * 
   * @param mediaId - ID of the media to toggle
   */
  const toggleSelection = (mediaId: string): void => {
    if (selectedMediaIds.value.has(mediaId)) {
      selectedMediaIds.value.delete(mediaId);
    } else {
      selectedMediaIds.value.add(mediaId);
    }
    
    // Trigger reactivity
    selectedMediaIds.value = new Set(selectedMediaIds.value);
    
    // Auto-exit selection mode when no items are selected
    if (selectedMediaIds.value.size === 0 && isSelectionMode.value) {
      isSelectionMode.value = false;
    }
  };

  /**
   * Select all media items
   * 
   * @param mediaIds - Array of all media IDs to select
   */
  const selectAll = (mediaIds: string[]): void => {
    selectedMediaIds.value = new Set(mediaIds);
  };

  /**
   * Clear all selections
   */
  const clearSelection = (): void => {
    selectedMediaIds.value.clear();
    selectedMediaIds.value = new Set(selectedMediaIds.value);
  };

  /**
   * Enter selection mode
   */
  const enterSelectionMode = (): void => {
    isSelectionMode.value = true;
  };

  /**
   * Exit selection mode and clear selections
   */
  const exitSelectionMode = (): void => {
    isSelectionMode.value = false;
    clearSelection();
  };

  return {
    selectedMediaIds,
    isSelectionMode,
    selectedCount,
    isSelected,
    toggleSelection,
    selectAll,
    clearSelection,
    enterSelectionMode,
    exitSelectionMode
  };
}
