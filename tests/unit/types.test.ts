/**
 * Type Definitions Test
 * 
 * This test verifies that all TypeScript interfaces are properly defined
 * and can be imported without errors.
 * 
 * @Requirements: 10.1, 10.2
 */

import { describe, it, expect } from 'vitest';
import type {
  Album,
  Media,
  UploadingFile,
  ValidationResult,
  UploadError,
  MediaScreenProps,
  AlbumListProps,
  AlbumItemProps,
  AlbumContentProps,
  UploadAreaProps,
  MediaGalleryProps,
  EmptyStateProps,
  ConfirmDialogProps,
} from '@/types/media-screen';

describe('TypeScript Type Definitions', () => {
  it('should define Album interface correctly', () => {
    const album: Album = {
      id: 1,
      name: 'Test Album',
      media_count: 5,
      media: [],
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    };

    expect(album.id).toBe(1);
    expect(album.name).toBe('Test Album');
    expect(album.media_count).toBe(5);
  });

  it('should define Media interface correctly', () => {
    const media: Media = {
      id: 1,
      album_id: 1,
      filename: 'test.jpg',
      type: 'image',
      mime_type: 'image/jpeg',
      size: 1024,
      url: 'https://example.com/test.jpg',
      thumbnail_url: 'https://example.com/thumb.jpg',
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    };

    expect(media.id).toBe(1);
    expect(media.type).toBe('image');
    expect(media.filename).toBe('test.jpg');
  });

  it('should define UploadingFile interface correctly', () => {
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    const uploadingFile: UploadingFile = {
      id: 'upload-1',
      file,
      progress: 50,
      status: 'uploading',
    };

    expect(uploadingFile.id).toBe('upload-1');
    expect(uploadingFile.progress).toBe(50);
    expect(uploadingFile.status).toBe('uploading');
  });

  it('should define ValidationResult interface correctly', () => {
    const file1 = new File(['content'], 'test1.jpg', { type: 'image/jpeg' });
    const file2 = new File(['content'], 'test2.txt', { type: 'text/plain' });
    
    const result: ValidationResult = {
      isValid: false,
      validFiles: [file1],
      invalidFiles: [file2],
      errors: ['test2.txt: tipo de arquivo não suportado'],
    };

    expect(result.isValid).toBe(false);
    expect(result.validFiles).toHaveLength(1);
    expect(result.invalidFiles).toHaveLength(1);
  });

  it('should define UploadError interface correctly', () => {
    const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
    const error: UploadError = {
      message: 'Upload failed',
      files: [file],
      code: 'NETWORK_ERROR',
    };

    expect(error.message).toBe('Upload failed');
    expect(error.code).toBe('NETWORK_ERROR');
    expect(error.files).toHaveLength(1);
  });

  it('should define component props interfaces correctly', () => {
    const album: Album = {
      id: 1,
      name: 'Test',
      media_count: 0,
      media: [],
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    };

    const mediaScreenProps: MediaScreenProps = {
      albums: [album],
      selectedAlbumId: 1,
    };

    const albumListProps: AlbumListProps = {
      albums: [album],
      selectedAlbumId: 1,
    };

    const albumItemProps: AlbumItemProps = {
      album,
      isSelected: true,
    };

    const albumContentProps: AlbumContentProps = {
      album,
    };

    const uploadAreaProps: UploadAreaProps = {
      albumId: 1,
    };

    const mediaGalleryProps: MediaGalleryProps = {
      media: [],
    };

    const emptyStateProps: EmptyStateProps = {
      type: 'no-albums',
    };

    const confirmDialogProps: ConfirmDialogProps = {
      isOpen: true,
      title: 'Confirm',
      message: 'Are you sure?',
      confirmLabel: 'Yes',
      cancelLabel: 'No',
    };

    expect(mediaScreenProps.albums).toHaveLength(1);
    expect(albumListProps.selectedAlbumId).toBe(1);
    expect(albumItemProps.isSelected).toBe(true);
    expect(albumContentProps.album.id).toBe(1);
    expect(uploadAreaProps.albumId).toBe(1);
    expect(mediaGalleryProps.media).toHaveLength(0);
    expect(emptyStateProps.type).toBe('no-albums');
    expect(confirmDialogProps.isOpen).toBe(true);
  });
});
