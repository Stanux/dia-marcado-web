<script setup>
/**
 * MediaUploader Component
 * 
 * Drag & drop media uploader with progress bar, preview, and validation.
 * Supports image and video uploads with client-side validation.
 * 
 * @Requirements: 16.1-16.7
 */
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    siteId: {
        type: String,
        required: true,
    },
    accept: {
        type: String,
        default: 'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm',
    },
    maxSize: {
        type: Number,
        default: 10 * 1024 * 1024, // 10MB default
    },
    allowedExtensions: {
        type: Array,
        default: () => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'],
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    showVariants: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['upload', 'error', 'remove']);

const fileInputRef = ref(null);
const isDragging = ref(false);
const uploadProgress = ref(0);
const isUploading = ref(false);
const uploadedMedia = ref(null);
const errorMessage = ref('');

/**
 * Format file size for display
 */
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

/**
 * Get file extension
 */
const getFileExtension = (filename) => {
    return filename.split('.').pop().toLowerCase();
};

/**
 * Validate file before upload
 */
const validateFile = (file) => {
    const errors = [];
    
    // Check file size
    if (file.size > props.maxSize) {
        errors.push(`Arquivo muito grande. Máximo: ${formatFileSize(props.maxSize)}`);
    }
    
    // Check extension
    const ext = getFileExtension(file.name);
    if (!props.allowedExtensions.includes(ext)) {
        errors.push(`Extensão não permitida: .${ext}. Use: ${props.allowedExtensions.join(', ')}`);
    }
    
    // Check MIME type
    const allowedMimes = props.accept.split(',').map(m => m.trim());
    if (!allowedMimes.some(mime => file.type.match(mime.replace('*', '.*')))) {
        errors.push(`Tipo de arquivo não permitido: ${file.type}`);
    }
    
    return errors;
};

/**
 * Handle file selection
 */
const handleFileSelect = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        processFile(files[0]);
    }
};

/**
 * Handle drag over
 */
const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

/**
 * Handle drag leave
 */
const handleDragLeave = () => {
    isDragging.value = false;
};

/**
 * Handle drop
 */
const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;
    
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        processFile(files[0]);
    }
};

/**
 * Process and upload file
 */
const processFile = async (file) => {
    errorMessage.value = '';
    
    // Validate file
    const errors = validateFile(file);
    if (errors.length > 0) {
        errorMessage.value = errors.join('. ');
        emit('error', errors);
        return;
    }
    
    // Start upload
    isUploading.value = true;
    uploadProgress.value = 0;
    
    try {
        const formData = new FormData();
        formData.append('file', file);
        
        const response = await axios.post(`/api/sites/${props.siteId}/media`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round(
                    (progressEvent.loaded * 100) / progressEvent.total
                );
            },
        });
        
        uploadedMedia.value = response.data.data || response.data;
        emit('upload', uploadedMedia.value);
    } catch (error) {
        const message = error.response?.data?.message || 'Erro ao fazer upload do arquivo';
        errorMessage.value = message;
        emit('error', [message]);
    } finally {
        isUploading.value = false;
        uploadProgress.value = 0;
        
        // Reset file input
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    }
};

/**
 * Open file dialog
 */
const openFileDialog = () => {
    fileInputRef.value?.click();
};

/**
 * Remove uploaded media
 */
const removeMedia = () => {
    emit('remove', uploadedMedia.value);
    uploadedMedia.value = null;
};

/**
 * Check if media is an image
 */
const isImage = computed(() => {
    if (!uploadedMedia.value) return false;
    return uploadedMedia.value.mime_type?.startsWith('image/');
});

/**
 * Check if media is a video
 */
const isVideo = computed(() => {
    if (!uploadedMedia.value) return false;
    return uploadedMedia.value.mime_type?.startsWith('video/');
});

/**
 * Get media URL
 */
const mediaUrl = computed(() => {
    if (!uploadedMedia.value) return '';
    return uploadedMedia.value.url || '';
});

/**
 * Get available variants
 */
const variants = computed(() => {
    if (!uploadedMedia.value?.variants) return [];
    return Object.entries(uploadedMedia.value.variants).map(([key, url]) => ({
        key,
        url,
        label: key.charAt(0).toUpperCase() + key.slice(1),
    }));
});
</script>

<template>
    <div class="media-uploader">
        <!-- Upload Zone (when no media uploaded) -->
        <div
            v-if="!uploadedMedia && !isUploading"
            class="upload-zone"
            :class="{ 'dragging': isDragging }"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            @click="openFileDialog"
        >
            <input
                ref="fileInputRef"
                type="file"
                :accept="accept"
                :multiple="multiple"
                class="hidden"
                @change="handleFileSelect"
            />
            
            <div class="upload-icon">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            
            <p class="upload-text">
                <span class="font-medium text-wedding-600">Clique para selecionar</span>
                <span class="text-gray-500"> ou arraste e solte</span>
            </p>
            
            <p class="upload-hint">
                {{ allowedExtensions.map(e => e.toUpperCase()).join(', ') }} até {{ formatFileSize(maxSize) }}
            </p>
        </div>
        
        <!-- Upload Progress -->
        <div v-if="isUploading" class="upload-progress">
            <div class="progress-content">
                <svg class="animate-spin w-8 h-8 text-wedding-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                
                <div class="progress-info">
                    <p class="text-sm font-medium text-gray-700">Enviando arquivo...</p>
                    <div class="progress-bar-container">
                        <div class="progress-bar" :style="{ width: uploadProgress + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-500">{{ uploadProgress }}%</p>
                </div>
            </div>
        </div>
        
        <!-- Uploaded Media Preview -->
        <div v-if="uploadedMedia && !isUploading" class="media-preview">
            <!-- Image Preview -->
            <div v-if="isImage" class="preview-image-container">
                <img :src="mediaUrl" :alt="uploadedMedia.original_name" class="preview-image" />
            </div>
            
            <!-- Video Preview -->
            <div v-else-if="isVideo" class="preview-video-container">
                <video :src="mediaUrl" controls class="preview-video"></video>
            </div>
            
            <!-- File Info -->
            <div class="preview-info">
                <div class="preview-details">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ uploadedMedia.original_name }}</p>
                    <p class="text-xs text-gray-500">{{ formatFileSize(uploadedMedia.size) }}</p>
                </div>
                
                <button
                    type="button"
                    @click="removeMedia"
                    class="remove-btn"
                    title="Remover"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
            
            <!-- Variants (optimized versions) -->
            <div v-if="showVariants && variants.length > 0" class="variants-section">
                <p class="text-xs font-medium text-gray-700 mb-2">Versões otimizadas:</p>
                <div class="variants-list">
                    <a
                        v-for="variant in variants"
                        :key="variant.key"
                        :href="variant.url"
                        target="_blank"
                        class="variant-badge"
                    >
                        {{ variant.label }}
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Error Message -->
        <div v-if="errorMessage" class="error-message">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-red-600">{{ errorMessage }}</p>
        </div>
        
        <!-- Replace Button (when media is uploaded) -->
        <button
            v-if="uploadedMedia && !isUploading"
            type="button"
            @click="openFileDialog"
            class="replace-btn"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Substituir arquivo
        </button>
    </div>
</template>

<style scoped>
.media-uploader {
    @apply space-y-3;
}

.upload-zone {
    @apply border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer transition-colors;
}

.upload-zone:hover {
    @apply border-wedding-400 bg-wedding-50;
}

.upload-zone.dragging {
    @apply border-wedding-500 bg-wedding-100;
}

.upload-icon {
    @apply flex justify-center mb-4;
}

.upload-text {
    @apply text-sm mb-1;
}

.upload-hint {
    @apply text-xs text-gray-400;
}

.upload-progress {
    @apply border border-gray-200 rounded-lg p-6;
}

.progress-content {
    @apply flex items-center space-x-4;
}

.progress-info {
    @apply flex-1;
}

.progress-bar-container {
    @apply w-full h-2 bg-gray-200 rounded-full mt-2 mb-1 overflow-hidden;
}

.progress-bar {
    @apply h-full bg-wedding-600 rounded-full transition-all duration-300;
}

.media-preview {
    @apply border border-gray-200 rounded-lg overflow-hidden;
}

.preview-image-container {
    @apply bg-gray-100;
}

.preview-image {
    @apply w-full h-48 object-contain;
}

.preview-video-container {
    @apply bg-black;
}

.preview-video {
    @apply w-full h-48;
}

.preview-info {
    @apply flex items-center justify-between p-3 bg-white border-t border-gray-100;
}

.preview-details {
    @apply min-w-0 flex-1;
}

.remove-btn {
    @apply p-2 text-gray-400 hover:text-red-500 transition-colors;
}

.variants-section {
    @apply px-3 pb-3;
}

.variants-list {
    @apply flex flex-wrap gap-2;
}

.variant-badge {
    @apply inline-block px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded hover:bg-gray-200 transition-colors;
}

.error-message {
    @apply flex items-start space-x-2 p-3 bg-red-50 border border-red-200 rounded-lg;
}

.replace-btn {
    @apply w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors;
}

/* Wedding theme colors */
.text-wedding-600 {
    color: #a18072;
}

.border-wedding-400 {
    border-color: #c4a99a;
}

.border-wedding-500 {
    border-color: #b8998a;
}

.bg-wedding-50 {
    background-color: #faf7f5;
}

.bg-wedding-100 {
    background-color: #f5ebe4;
}

.bg-wedding-600 {
    background-color: #a18072;
}
</style>
