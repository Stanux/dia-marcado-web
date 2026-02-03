<template>
  <div 
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    @click.self="handleCancel"
  >
    <div 
      class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6"
      @click.stop
    >
      <!-- Header -->
      <div class="mb-4">
        <h2 class="text-xl font-semibold text-red-600 dark:text-red-400">
          Excluir Álbum
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Esta ação não pode ser desfeita
        </p>
      </div>

      <!-- Content -->
      <div class="mb-6">
        <p class="text-gray-700 dark:text-gray-300">
          Tem certeza que deseja excluir o álbum <strong>"{{ albumName }}"</strong>?
        </p>
      </div>

      <!-- Actions -->
      <div class="flex gap-3 justify-end">
        <button
          type="button"
          @click="handleCancel"
          class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
        >
          Cancelar
        </button>
        <button
          type="button"
          @click="handleConfirm"
          class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
        >
          Excluir Álbum
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface DeleteAlbumModalProps {
  isOpen: boolean;
  albumId: string;
  albumName: string;
}

interface DeleteAlbumModalEvents {
  'close': () => void;
  'confirm': (albumId: string) => void;
}

const props = defineProps<DeleteAlbumModalProps>();
const emit = defineEmits<DeleteAlbumModalEvents>();

const handleConfirm = () => {
  emit('confirm', props.albumId);
};

const handleCancel = () => {
  emit('close');
};
</script>
