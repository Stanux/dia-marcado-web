/**
 * useMediaUpload Composable
 * 
 * Manages media upload logic including file validation, upload progress tracking,
 * and error handling. Uses axios for file uploads to enable reactive updates
 * without page reloads.
 * 
 * @Requirements: 4.3, 4.5, 4.6, 5.1, 5.2, 5.3, 5.4
 */

import { ref } from 'vue';
import axios from 'axios';
import type { 
  UploadingFile, 
  ValidationResult, 
  Media, 
  UploadError,
  UseMediaUploadReturn 
} from '@/types/media-screen';

/**
 * Generate a unique ID for tracking uploads
 * @returns Unique string ID
 */
function generateId(): string {
  return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Composable for managing media uploads
 * 
 * @returns Object containing upload state and operations
 */
export function useMediaUpload(): UseMediaUploadReturn {
  // Reactive state for tracking files being uploaded
  const uploadingFiles = ref<UploadingFile[]>([]);

  /**
   * Validate files before upload
   * Checks file type and size against allowed values
   * 
   * @Requirements: 4.5 - Accept only image and video files
   * @Requirements: 4.6 - Reject unsupported files with explanatory message
   * 
   * @param files - Array of files to validate
   * @returns ValidationResult with valid/invalid files and error messages
   */
  const validateFiles = (files: File[]): ValidationResult => {
    // Allowed MIME types for images and videos
    const allowedTypes = [
      'image/jpeg',
      'image/png',
      'image/gif',
      'video/mp4',
      'video/quicktime'
    ];
    
    // Maximum file size: 100MB
    const maxSize = 100 * 1024 * 1024;
    
    const errors: string[] = [];
    const validFiles: File[] = [];
    const invalidFiles: File[] = [];
    
    files.forEach(file => {
      let isValid = true;
      
      // Check file type
      if (!allowedTypes.includes(file.type)) {
        errors.push(
          `${file.name}: tipo de arquivo não suportado. ` +
          `Apenas imagens (JPEG, PNG, GIF) e vídeos (MP4, QuickTime) são permitidos.`
        );
        isValid = false;
      }
      
      // Check file size
      if (file.size > maxSize) {
        errors.push(
          `${file.name}: arquivo muito grande. ` +
          `O tamanho máximo permitido é 100MB.`
        );
        isValid = false;
      }
      
      // Categorize file
      if (isValid) {
        validFiles.push(file);
      } else {
        invalidFiles.push(file);
      }
    });
    
    return {
      isValid: invalidFiles.length === 0,
      validFiles,
      invalidFiles,
      errors: errors.length > 0 ? errors : undefined
    };
  };

  /**
   * Upload a single file with progress tracking
   * 
   * @Requirements: 5.1 - Display individual loading indicator for each file
   * @Requirements: 5.2 - Display visual feedback while upload is in progress
   * @Requirements: 5.3 - Remove loading indicator and add media to gallery on success
   * @Requirements: 5.4 - Display specific error message and allow retry on failure
   * 
   * @param albumId - ID of the album to upload to
   * @param file - File to upload
   * @returns Promise resolving to the created Media object
   * @throws UploadError if upload fails
   */
  const uploadSingleFile = async (albumId: string, file: File): Promise<Media> => {
    // Create tracking object for this upload
    const uploadingFile: UploadingFile = {
      id: generateId(),
      file,
      progress: 0,
      status: 'uploading'
    };
    
    // Add to tracking list
    uploadingFiles.value.push(uploadingFile);

    try {
      // Prepare form data
      const formData = new FormData();
      formData.append('file', file);
      formData.append('album_id', albumId);

      // Upload file using axios with progress tracking
      const response = await axios.post('/admin/media/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            // Calculate percentage (0-100)
            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            uploadingFile.progress = percentCompleted;
          }
        },
      });

      // Check if upload was successful
      if (response.data.success && response.data.media) {
        uploadingFile.status = 'completed';
        uploadingFile.progress = 100;
        
        const media = response.data.media as Media;

        // Remove from uploading list after a delay (to show completion state)
        setTimeout(() => {
          uploadingFiles.value = uploadingFiles.value.filter(f => f.id !== uploadingFile.id);
        }, 2000);

        return media;
      } else {
        throw new Error(response.data.message || 'Erro ao fazer upload');
      }

    } catch (error: any) {
      // Mark upload as failed
      uploadingFile.status = 'failed';
      
      // Extract error message from response
      const errorMessage = error.response?.data?.message || error.message || 'Erro desconhecido';
      uploadingFile.error = errorMessage;

      // Create structured error
      const uploadError: UploadError = {
        message: errorMessage,
        files: [file],
        code: error.response?.status === 422 ? 'VALIDATION_FAILED' : 'UPLOAD_FAILED'
      };

      // Remove from uploading list after a delay (to show error state)
      setTimeout(() => {
        uploadingFiles.value = uploadingFiles.value.filter(f => f.id !== uploadingFile.id);
      }, 3000);

      throw uploadError;
    }
  };

  /**
   * Upload multiple files
   * Validates files first, then uploads all valid files in parallel
   * 
   * @Requirements: 4.3 - Initiate upload process for dropped files
   * @Requirements: 4.6 - Reject invalid files with explanatory message
   * 
   * @param albumId - ID of the album to upload to
   * @param files - Array of files to upload
   * @returns Promise resolving to array of created Media objects
   * @throws UploadError if validation fails or all uploads fail
   */
  const uploadFiles = async (albumId: string, files: File[]): Promise<Media[]> => {
    // Validate files first
    const validation = validateFiles(files);
    
    // If there are invalid files, throw validation error
    if (!validation.isValid) {
      const error: UploadError = {
        message: 'Alguns arquivos são inválidos e não podem ser enviados.',
        files: validation.invalidFiles,
        code: 'VALIDATION_FAILED'
      };
      throw error;
    }

    // Upload all valid files in parallel
    const uploadPromises = validation.validFiles.map(file => 
      uploadSingleFile(albumId, file)
    );

    try {
      // Wait for all uploads to complete
      const uploadedMedia = await Promise.all(uploadPromises);
      return uploadedMedia;
    } catch (error: any) {
      // If any upload fails, re-throw the error
      throw error;
    }
  };

  /**
   * Cancel an ongoing upload
   * Removes the file from the uploading list
   * 
   * @param fileId - ID of the file to cancel
   */
  const cancelUpload = (fileId: string): void => {
    uploadingFiles.value = uploadingFiles.value.filter(f => f.id !== fileId);
  };

  return {
    uploadingFiles,
    uploadFiles,
    validateFiles,
    cancelUpload
  };
}
