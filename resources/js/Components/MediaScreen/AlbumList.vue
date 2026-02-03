<template>
  <aside class="flex flex-col w-64 h-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
    <div class="flex-1 overflow-y-auto p-2 space-y-1">
      <AlbumItem
        v-for="album in albums"
        :key="album.id"
        :album="album"
        :is-selected="album.id === selectedAlbumId"
        @click="$emit('album-selected', album.id)"
        @edit="$emit('edit-album', $event)"
        @delete="$emit('delete-album', $event)"
      />
    </div>
    <button 
      class="flex items-center justify-center gap-2 p-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-gray-900 active:bg-blue-100 dark:active:bg-gray-800 transition-colors cursor-pointer"
      @click="$emit('create-album')"
      type="button"
    >
      <span class="text-xl font-semibold leading-none">+</span>
      <span class="leading-none">Novo álbum</span>
    </button>
  </aside>
</template>

<script setup lang="ts">
import type { AlbumListProps, AlbumListEvents } from '@/types/media-screen';
import AlbumItem from './AlbumItem.vue';

/**
 * AlbumList Component
 * 
 * Displays a vertical list of albums with a "Create Album" button at the bottom.
 * Allows users to select albums and create new ones.
 * 
 * @Requirements: 3.1, 3.2, 3.5, 3.6
 * - Requisito 3.1: Displays vertical list of all existing albums
 * - Requisito 3.2: Shows album name and media count for each album
 * - Requisito 3.5: Displays "Novo álbum" button as last item
 * - Requisito 3.6: Allows creation of new album when button is clicked
 */

defineProps<AlbumListProps>();

defineEmits<AlbumListEvents>();
</script>
