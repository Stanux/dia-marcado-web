<template>
  <div class="w-full mb-8">
    <!-- Main Upload Area with Drag-and-Drop -->
    <div
      ref="uploadAreaRef"
      class="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-all"
      :class="isDragOver 
        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 border-solid' 
        : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 hover:border-gray-400 dark:hover:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'"
      @dragover.prevent="handleDragOver"
      @dragleave.prevent="handleDragLeave"
      @drop.prevent="handleDrop"
      @click="triggerFileSelect"
    >
      <div class="flex justify-center mb-4">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="w-12 h-12 transition-colors"
          :class="isDragOver ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500'"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
          />
        </svg>
      </div>
      
      <div class="text-gray-600 dark:text-gray-400">
        <p 
          class="text-base font-medium mb-1 transition-colors"
          :class="isDragOver ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-700 dark:text-gray-300'"
        >
          {{ isDragOver ? 'Solte os arquivos aqui' : 'Arraste arquivos ou clique para selecionar' }}
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Imagens (JPEG, PNG, GIF) e vídeos (MP4, QuickTime) até 100MB
        </p>
      </div>
    </div>

    <!-- Hidden File Input -->
    <input
      ref="fileInputRef"
      type="file"
      multiple
      accept="image/jpeg,image/png,image/gif,video/mp4,video/quicktime"
      class="hidden"
      @change="handleFileSelect"
    />

    <!-- Uploading Files List -->
    <div v-if="uploadingFiles.length > 0" class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">
        Enviando arquivos
      </h3>
      
      <div
        v-for="uploadingFile in uploadingFiles"
        :key="uploadingFile.id"
        class="bg-white dark:bg-gray-900 border rounded-md p-3 mb-3 last:mb-0"
        :class="{
          'border-gray-200 dark:border-gray-700': uploadingFile.status === 'uploading',
          'border-green-500 dark:border-green-600 bg-green-50 dark:bg-green-900/20': uploadingFile.status === 'completed',
          'border-red-500 dark:border-red-600 bg-red-50 dark:bg-red-900/20': uploadingFile.status === 'failed'
        }"
      >
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0">
            <!-- Uploading Icon -->
            <svg
              v-if="uploadingFile.status === 'uploading'"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-6 h-6 text-gray-600 dark:text-gray-400 animate-spin"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
              />
            </svg>
            
            <!-- Completed Icon -->
            <svg
              v-else-if="uploadingFile.status === 'completed'"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-6 h-6 text-green-600 dark:text-green-400"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            
            <!-- Failed Icon -->
            <svg
              v-else-if="uploadingFile.status === 'failed'"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-6 h-6 text-red-600 dark:text-red-400"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
              />
            </svg>
          </div>
          
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
              {{ uploadingFile.file.name }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              {{ formatFileSize(uploadingFile.file.size) }}
            </p>
            
            <p v-if="uploadingFile.status === 'failed' && uploadingFile.error" class="text-xs text-red-600 dark:text-red-400 mt-1">
              {{ uploadingFile.error }}
            </p>
          </div>
        </div>
        
        <!-- Progress Bar -->
        <div v-if="uploadingFile.status === 'uploading'" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-sm overflow-hidden mt-2">
          <div 
            class="h-full bg-blue-500 dark:bg-blue-600 rounded-sm transition-all duration-300"
            :style="{ width: `${uploadingFile.progress}%` }"
          ></div>
        </div>
        
        <!-- Status Text -->
        <div class="text-xs font-medium mt-1 text-right">
          <span v-if="uploadingFile.status === 'uploading'" class="text-gray-600 dark:text-gray-400">
            {{ uploadingFile.progress }}%
          </span>
          <span v-else-if="uploadingFile.status === 'completed'" class="text-green-600 dark:text-green-400">
            Concluído
          </span>
          <span v-else-if="uploadingFile.status === 'failed'" class="text-red-600 dark:text-red-400">
            Falhou
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useMediaUpload } from '@/Composables/useMediaUpload';
import { useNotifications } from '@/Composables/useNotifications';
import type { UploadAreaProps, UploadAreaEvents } from '@/types/media-screen';

/**
 * UploadArea Component
 * 
 * Provides drag-and-drop and click-to-select file upload functionality.
 * Displays upload progress for each file being uploaded.
 * 
 * @Requirements:
 * - 4.1: Display upload area in upper section of right column
 * - 4.2: Provide visual feedback when dragging files over upload area
 * - 4.3: Initiate upload process when files are dropped
 * - 4.4: Open file selector when upload area is clicked
 * - 5.1: Display individual loading indicator for each file
 * - 5.2: Display visual feedback while upload is in progress
 */

// Props
const props = defineProps<UploadAreaProps>();

// Events
const emit = defineEmits<UploadAreaEvents>();

// Composable for upload logic
const { uploadingFiles, uploadFiles, validateFiles } = useMediaUpload();

// Composable for notifications
const { show: showNotification } = useNotifications();

// Template refs
const uploadAreaRef = ref<HTMLDivElement | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Local state
const isDragOver = ref(false);

/**
 * Handle dragover event
 * Provides visual feedback that area is ready to receive files
 * 
 * @Requirements: 4.2 - Visual feedback during drag
 */
const handleDragOver = (event: DragEvent): void => {
  event.preventDefault();
  isDragOver.value = true;
};

/**
 * Handle dragleave event
 * Removes visual feedback when drag leaves the area
 * 
 * @Requirements: 4.2 - Visual feedback during drag
 */
const handleDragLeave = (event: DragEvent): void => {
  event.preventDefault();
  
  // Only remove drag-over state if leaving the upload area itself
  // (not when moving between child elements)
  if (event.target === uploadAreaRef.value) {
    isDragOver.value = false;
  }
};

/**
 * Handle drop event
 * Initiates upload process for dropped files
 * 
 * @Requirements: 4.3 - Initiate upload when files are dropped
 */
const handleDrop = async (event: DragEvent): Promise<void> => {
  event.preventDefault();
  isDragOver.value = false;
  
  // Get files from drop event
  const files = Array.from(event.dataTransfer?.files || []);
  
  if (files.length > 0) {
    await processFiles(files);
  }
};

/**
 * Trigger file input click
 * Opens system file selector when upload area is clicked
 * 
 * @Requirements: 4.4 - Open file selector on click
 */
const triggerFileSelect = (): void => {
  fileInputRef.value?.click();
};

/**
 * Handle file selection from input
 * Initiates upload process for selected files
 * 
 * @Requirements: 4.3 - Initiate upload process
 */
const handleFileSelect = async (event: Event): Promise<void> => {
  const input = event.target as HTMLInputElement;
  const files = Array.from(input.files || []);
  
  if (files.length > 0) {
    await processFiles(files);
    
    // Reset input so same file can be selected again
    input.value = '';
  }
};

/**
 * Process and upload files
 * Validates files and initiates upload
 * 
 * @Requirements:
 * - 4.3: Initiate upload process
 * - 4.5: Accept only image and video files
 * - 4.6: Reject unsupported files with message
 * - 5.1: Display individual loading indicator
 * - 5.3: Show success notification after upload completes
 * - 5.4: Show error notification when upload fails
 */
const processFiles = async (files: File[]): Promise<void> => {
  // Emit upload started event
  emit('upload-started', files);
  
  try {
    // Validate files first
    const validation = validateFiles(files);
    
    // If there are invalid files, emit error and show notification
    if (!validation.isValid) {
      const errorMessage = validation.errors?.join('\n') || 'Alguns arquivos são inválidos';
      
      emit('upload-failed', {
        message: errorMessage,
        files: validation.invalidFiles,
        code: 'VALIDATION_FAILED'
      });
      
      // Show error notification for invalid files
      showNotification(errorMessage, 'error');
      
      // If there are no valid files, stop here
      if (validation.validFiles.length === 0) {
        return;
      }
    }
    
    // Upload valid files
    const uploadedMedia = await uploadFiles(props.albumId, validation.validFiles);
    
    // Emit success event
    emit('upload-completed', uploadedMedia);
    
    // Show success notification
    const fileCount = uploadedMedia.length;
    const successMessage = fileCount === 1 
      ? 'Arquivo enviado com sucesso!' 
      : `${fileCount} arquivos enviados com sucesso!`;
    showNotification(successMessage, 'success');
    
  } catch (error: any) {
    // Emit failure event
    const errorMessage = error.message || 'Erro ao fazer upload';
    
    emit('upload-failed', {
      message: errorMessage,
      files: error.files || files,
      code: error.code || 'UPLOAD_FAILED'
    });
    
    // Show error notification
    showNotification(errorMessage, 'error');
  }
};

/**
 * Format file size for display
 * Converts bytes to human-readable format
 */
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>
