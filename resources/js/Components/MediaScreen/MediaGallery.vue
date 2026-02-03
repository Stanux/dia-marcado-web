<template>
  <div class="media-gallery">
    <!-- Grid size control -->
    <div v-if="media.length > 0" class="gallery-header">
      <GridSizeControl v-model="gridColumns" />
    </div>

    <!-- Empty state when no media -->
    <div v-if="media.length === 0" class="empty-state">
      <div class="empty-icon">
        <svg 
          xmlns="http://www.w3.org/2000/svg" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke-width="1.5" 
          stroke="currentColor" 
          class="icon"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" 
          />
        </svg>
      </div>
      <h3 class="empty-title">Nenhuma mídia neste álbum</h3>
      <p class="empty-message">
        Faça upload de fotos e vídeos usando a área acima para começar a preencher este álbum.
      </p>
    </div>

    <!-- Gallery grid when media exists -->
    <div v-else class="gallery-grid" :style="gridStyle">
      <MediaItem
        v-for="item in media"
        :key="item.id"
        :media="item"
        :is-selected="isSelected(item.id)"
        :is-selection-mode="isSelectionMode"
        @delete="handleDelete"
        @toggle-selection="handleToggleSelection"
        @move="handleMove"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { MediaGalleryProps, MediaGalleryEvents } from '@/types/media-screen';
import MediaItem from './MediaItem.vue';
import GridSizeControl from './GridSizeControl.vue';

/**
 * MediaGallery Component
 * 
 * Displays a responsive grid of media items (images and videos) from the selected album.
 * Uses CSS Grid with adjustable column count (4-8 columns).
 * Shows an empty state with instructions when no media is present.
 * Integrates with useMediaGallery composable for media operations.
 * Supports direct selection via checkbox on hover.
 * 
 * @Requirements: 6.1, 6.2, 6.4, 6.5, 8.2, Fase 1 (melhorada)
 * - Requisito 6.1: Display media from selected album in responsive grid
 * - Requisito 6.2: Display media thumbnails
 * - Requisito 6.4: Organize media in grid in lower section of right column when album contains media
 * - Requisito 6.5: Automatically adjust number of columns based on available width
 * - Requisito 8.2: Display empty state with clear instructions when album has no media
 * - Fase 1: Selection mode with checkbox always visible on hover
 */

interface ExtendedMediaGalleryProps extends MediaGalleryProps {
  isSelectionMode?: boolean;
  isSelected?: (mediaId: string) => boolean;
}

const props = withDefaults(defineProps<ExtendedMediaGalleryProps>(), {
  isSelectionMode: false,
  isSelected: () => false
});

const emit = defineEmits<MediaGalleryEvents>();

// Grid columns control (4-8)
const gridColumns = ref(4);

// Computed style for grid
const gridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${gridColumns.value}, 1fr)`
}));

/**
 * Handle delete event from MediaItem
 * Emits delete-media event to parent component
 * 
 * @param mediaId - ID of the media to delete
 */
function handleDelete(mediaId: string): void {
  emit('delete-media', mediaId);
}

/**
 * Handle toggle selection event from MediaItem
 * Emits toggle-selection event to parent component
 * 
 * @param mediaId - ID of the media to toggle selection
 */
function handleToggleSelection(mediaId: string): void {
  emit('toggle-selection', mediaId);
}

/**
 * Handle move event from MediaItem
 * Emits move-media event to parent component
 * 
 * @param mediaId - ID of the media to move
 */
function handleMove(mediaId: string): void {
  emit('move-media', mediaId);
}
</script>

<style scoped>
.media-gallery {
  width: 100%;
  min-height: 200px;
}

/* Gallery header */
.gallery-header {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 1rem 1rem 0.5rem;
}

/* Gallery grid - controlled by gridColumns */
.gallery-grid {
  display: grid;
  gap: 1rem;
  padding: 1rem;
  width: 100%;
}

/* Responsive adjustments for different screen sizes */
@media (min-width: 640px) {
  .gallery-grid {
    gap: 1.25rem;
    padding: 1.25rem;
  }
}

@media (min-width: 768px) {
  .gallery-grid {
    gap: 1.5rem;
    padding: 1.5rem;
  }
}

/* Empty state styling */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  text-align: center;
  min-height: 300px;
}

.empty-icon {
  width: 4rem;
  height: 4rem;
  margin-bottom: 1rem;
  color: #9ca3af;
}

.empty-icon .icon {
  width: 100%;
  height: 100%;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-message {
  font-size: 0.875rem;
  color: #6b7280;
  max-width: 28rem;
  line-height: 1.5;
}

/* Responsive empty state adjustments */
@media (max-width: 640px) {
  .empty-state {
    padding: 2rem 1rem;
    min-height: 250px;
  }

  .empty-icon {
    width: 3rem;
    height: 3rem;
  }

  .empty-title {
    font-size: 1rem;
  }

  .empty-message {
    font-size: 0.8125rem;
  }
}
</style>
