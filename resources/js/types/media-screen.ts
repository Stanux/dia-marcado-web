/**
 * TypeScript Type Definitions for Media Screen
 * 
 * This file contains all TypeScript interfaces and types for the Media Screen feature.
 * These types ensure type safety across components, composables, and props/events.
 * 
 * @Requirements: 10.1, 10.2
 */

/**
 * Album Model
 * Represents a collection of media files
 */
export interface Album {
  id: string;
  name: string;
  type: string;
  media_count: number;
  media: Media[];
  created_at: string;
  updated_at: string;
}

/**
 * Media Model
 * Represents an individual media file (image or video)
 */
export interface Media {
  id: string;
  album_id: string;
  filename: string;
  type: 'image' | 'video';
  mime_type: string;
  size: number;
  url: string;
  thumbnail_url: string;
  created_at: string;
  updated_at: string;
}

/**
 * UploadingFile
 * Tracks the state of a file being uploaded
 */
export interface UploadingFile {
  id: string;
  file: File;
  progress: number;
  status: 'uploading' | 'completed' | 'failed';
  error?: string;
}

/**
 * ValidationResult
 * Result of file validation before upload
 */
export interface ValidationResult {
  isValid: boolean;
  validFiles: File[];
  invalidFiles: File[];
  errors?: string[];
}

/**
 * UploadError
 * Error information for failed uploads
 */
export interface UploadError {
  message: string;
  files: File[];
  code?: string;
}

/**
 * Component Props Types
 */

export interface MediaScreenProps {
  albums: Album[];
  selectedAlbumId?: string;
}

export interface AlbumListProps {
  albums: Album[];
  selectedAlbumId?: string;
}

export interface AlbumItemProps {
  album: Album;
  isSelected: boolean;
}

export interface AlbumContentProps {
  album: Album;
}

export interface UploadAreaProps {
  albumId: number;
}

export interface MediaGalleryProps {
  media: Media[];
}

export interface MediaItemProps {
  media: Media;
}

export interface EmptyStateProps {
  type: 'no-albums' | 'no-media';
}

export interface ConfirmDialogProps {
  isOpen: boolean;
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
}

/**
 * Component Events Types
 */

export interface AlbumListEvents {
  'album-selected': (albumId: string) => void;
  'create-album': () => void;
  'edit-album': (albumId: string) => void;
  'delete-album': (albumId: string) => void;
}

export interface AlbumContentEvents {
  'media-uploaded': (media: Media[]) => void;
  'media-deleted': (mediaId: string) => void;
}

export interface UploadAreaEvents {
  'upload-started': (files: File[]) => void;
  'upload-completed': (media: Media[]) => void;
  'upload-failed': (error: UploadError) => void;
}

export interface MediaGalleryEvents {
  'delete-media': (mediaId: string) => void;
}

export interface MediaItemEvents {
  'delete': (mediaId: string) => void;
}

export interface ConfirmDialogEvents {
  'confirm': () => void;
  'cancel': () => void;
}

/**
 * Composable Return Types
 */

export interface UseAlbumsReturn {
  albums: import('vue').Ref<Album[]>;
  selectedAlbum: import('vue').Ref<Album | null>;
  isLoading: import('vue').Ref<boolean>;
  selectAlbum: (albumId: string) => void;
  createAlbum: (name: string, type: string) => Promise<Album>;
  updateAlbum: (id: string, name: string, type: string) => Promise<Album>;
  deleteAlbum: (id: string) => Promise<void>;
  refreshAlbums: () => Promise<void>;
}

export interface UseMediaUploadReturn {
  uploadingFiles: import('vue').Ref<UploadingFile[]>;
  uploadFiles: (albumId: string, files: File[]) => Promise<Media[]>;
  validateFiles: (files: File[]) => ValidationResult;
  cancelUpload: (fileId: string) => void;
}

export interface UseMediaGalleryReturn {
  media: import('vue').Ref<Media[]>;
  deleteMedia: (mediaId: string) => Promise<void>;
  refreshMedia: (albumId: string) => Promise<void>;
}

/**
 * Internal State Types
 */

export interface MediaScreenState {
  selectedAlbum: Album | null;
  isLoading: boolean;
}

export interface UploadAreaState {
  isDragOver: boolean;
  uploadingFiles: UploadingFile[];
}

/**
 * Notification Types
 */

export type NotificationType = 'error' | 'warning' | 'info' | 'success';

export interface Notification {
  id: string;
  type: NotificationType;
  message: string;
  duration?: number | null; // Duration in milliseconds, null/undefined means no auto-dismiss
}

export interface NotificationToastProps {
  notification: Notification;
}

export interface NotificationToastEvents {
  'dismiss': (id: string) => void;
}

export interface UseNotificationsReturn {
  notifications: import('vue').Ref<Notification[]>;
  show: (message: string, type?: NotificationType, duration?: number | null) => string;
  dismiss: (id: string) => void;
}

export interface ErrorNotification {
  type: 'error' | 'warning' | 'info' | 'success';
  message: string;
  action?: {
    label: string;
    handler: () => void;
  };
}

/**
 * Log Entry Type
 */

export interface LogEntry {
  timestamp: string;
  level: 'info' | 'warning' | 'error';
  action: string;
  details: Record<string, any>;
  userId?: number;
}
