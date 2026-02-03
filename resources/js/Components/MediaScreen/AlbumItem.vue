<template>
  <div 
    class="px-3 py-2.5 rounded-lg cursor-pointer transition-colors select-none"
    :class="isSelected 
      ? 'bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/40' 
      : 'hover:bg-gray-100 dark:hover:bg-gray-800 active:bg-gray-200 dark:active:bg-gray-700'"
    role="button"
    tabindex="0"
    @click="$emit('click')"
    @keydown.enter="$emit('click')"
    @keydown.space.prevent="$emit('click')"
  >
    <div class="flex justify-between items-start">
      <div class="flex-1 mr-2 min-w-0">
        <div 
          class="text-sm font-medium truncate"
          :class="isSelected ? 'text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'"
        >
          {{ album.name }}
        </div>
        <div 
          class="text-xs truncate mt-0.5"
          :class="isSelected ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400'"
        >
          {{ getAlbumTypeLabel(album.type) }}
        </div>
      </div>
      <span 
        class="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded-full min-w-[1.5rem] text-center"
        :class="isSelected 
          ? 'bg-blue-200 dark:bg-blue-800 text-blue-700 dark:text-blue-200' 
          : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
      >
        {{ album.media_count }}
      </span>
    </div>
    
    <!-- Action Buttons -->
    <div class="flex gap-2 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
      <!-- Edit Button -->
      <button
        type="button"
        @click.stop="$emit('edit', album.id)"
        class="flex-1 px-2 py-1 text-xs font-medium rounded transition-colors"
        :class="isSelected
          ? 'text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800'
          : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
        title="Editar álbum"
      >
        <span class="flex items-center justify-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
          </svg>
          Editar
        </span>
      </button>
      
      <!-- Delete Button (only if album is empty) -->
      <button
        v-if="album.media_count === 0"
        type="button"
        @click.stop="$emit('delete', album.id)"
        class="flex-1 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
        title="Excluir álbum"
      >
        <span class="flex items-center justify-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
          </svg>
          Excluir
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AlbumItemProps } from '@/types/media-screen';

/**
 * AlbumItem Component
 * 
 * Represents a single album item in the album list.
 * Displays the album name, type, and media count with visual feedback for selection state.
 * 
 * @Requirements: 3.2, 3.3
 * - Requisito 3.2: Displays album name and media count
 * - Requisito 3.3: Visual highlight when selected
 */

defineProps<AlbumItemProps>();

defineEmits<{
  click: [];
  edit: [albumId: string];
  delete: [albumId: string];
}>();

/**
 * Get the display label for an album type
 */
const getAlbumTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    'pre_casamento': 'Pré-Casamento',
    'pos_casamento': 'Pós-Casamento',
    'uso_site': 'Uso no Site'
  };
  return labels[type] || type;
};
</script>
