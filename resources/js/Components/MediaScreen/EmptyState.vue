<script setup lang="ts">
import { computed } from 'vue';
import type { EmptyStateProps } from '@/types/media-screen';

/**
 * EmptyState Component
 * 
 * Displays empty state messages with guidance for users when there's no content.
 * Supports different types of empty states with appropriate messages and actions.
 * 
 * @Requirements: 8.1, 8.2, 8.3
 */

const props = defineProps<EmptyStateProps>();

const emit = defineEmits<{
  'create-album': [];
  'upload-media': [];
}>();

/**
 * Configuration for different empty state types
 */
const emptyStateConfig = computed(() => {
  switch (props.type) {
    case 'no-albums':
      return {
        icon: '📁',
        title: 'Nenhum álbum criado',
        message: 'Comece criando seu primeiro álbum para organizar suas fotos e vídeos do casamento.',
        actionLabel: 'Novo álbum',
        action: () => emit('create-album')
      };
    case 'no-media':
      return {
        icon: '🖼️',
        title: 'Nenhuma mídia neste álbum',
        message: 'Faça upload de fotos e vídeos para começar a preencher este álbum.',
        actionLabel: 'Fazer upload',
        action: () => emit('upload-media')
      };
    default:
      return {
        icon: '📋',
        title: 'Nenhum conteúdo',
        message: 'Não há conteúdo para exibir no momento.',
        actionLabel: null,
        action: null
      };
  }
});

const handleAction = () => {
  if (emptyStateConfig.value.action) {
    emptyStateConfig.value.action();
  }
};
</script>

<template>
  <div class="empty-state">
    <div class="empty-icon">
      {{ emptyStateConfig.icon }}
    </div>
    <h3 class="empty-title">
      {{ emptyStateConfig.title }}
    </h3>
    <p class="empty-message">
      {{ emptyStateConfig.message }}
    </p>
    <button
      v-if="emptyStateConfig.actionLabel"
      class="empty-action"
      @click="handleAction"
    >
      <span class="action-icon">+</span>
      {{ emptyStateConfig.actionLabel }}
    </button>
  </div>
</template>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  min-height: 400px;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  opacity: 0.8;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.75rem;
}

.empty-message {
  font-size: 1rem;
  color: #6b7280;
  max-width: 400px;
  line-height: 1.6;
  margin-bottom: 2rem;
}

.empty-action {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 2rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-icon {
  font-size: 1.25rem;
  font-weight: 600;
  line-height: 1;
}

.empty-action:hover {
  background-color: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.empty-action:active {
  transform: translateY(0);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.empty-action:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .empty-title {
    color: #e5e7eb;
  }
  
  .empty-message {
    color: #9ca3af;
  }
}
</style>
