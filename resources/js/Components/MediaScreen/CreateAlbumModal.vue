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
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Novo Álbum
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Preencha os dados do novo álbum
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit">
        <!-- Album Type -->
        <div class="mb-4">
          <label 
            for="album-type" 
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            Tipo do Álbum
          </label>
          <select
            id="album-type"
            v-model="formData.type"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
            required
          >
            <option value="">Selecione um tipo</option>
            <option value="pre_casamento">Pré-Casamento</option>
            <option value="pos_casamento">Pós-Casamento</option>
            <option value="uso_site">Uso no Site</option>
          </select>
        </div>

        <!-- Album Name -->
        <div class="mb-6">
          <label 
            for="album-name" 
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            Nome do Álbum
          </label>
          <input
            id="album-name"
            v-model="formData.name"
            type="text"
            placeholder="Digite o nome do álbum"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
            required
            maxlength="255"
          />
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
            type="submit"
            :disabled="!formData.type || !formData.name.trim()"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
          >
            Criar Álbum
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface CreateAlbumModalProps {
  isOpen: boolean;
}

interface CreateAlbumModalEvents {
  'close': () => void;
  'create': (data: { name: string; type: string }) => void;
}

const props = defineProps<CreateAlbumModalProps>();
const emit = defineEmits<CreateAlbumModalEvents>();

const formData = ref({
  name: '',
  type: ''
});

// Reset form when modal opens
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    formData.value = {
      name: '',
      type: ''
    };
  }
});

const handleSubmit = () => {
  if (formData.value.name.trim() && formData.value.type) {
    emit('create', {
      name: formData.value.name.trim(),
      type: formData.value.type
    });
  }
};

const handleCancel = () => {
  emit('close');
};
</script>
