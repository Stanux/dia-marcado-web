/**
 * Unit Tests for useMediaUpload Composable
 * 
 * Tests the media upload composable including file validation,
 * upload progress tracking, and error handling.
 * 
 * @Requirements: 4.3, 4.5, 4.6, 5.1, 5.2, 5.3, 5.4
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useMediaUpload } from '@/Composables/useMediaUpload';
import { router } from '@inertiajs/vue3';
import type { Media, UploadError } from '@/types/media-screen';

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn(),
  }
}));

describe('useMediaUpload', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  describe('validateFiles', () => {
    it('should accept valid image files (JPEG)', () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(1);
      expect(result.invalidFiles).toHaveLength(0);
      expect(result.errors).toBeUndefined();
    });

    it('should accept valid image files (PNG)', () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.png', { type: 'image/png' });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(1);
      expect(result.invalidFiles).toHaveLength(0);
    });

    it('should accept valid image files (GIF)', () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.gif', { type: 'image/gif' });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(1);
      expect(result.invalidFiles).toHaveLength(0);
    });

    it('should accept valid video files (MP4)', () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'video.mp4', { type: 'video/mp4' });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(1);
      expect(result.invalidFiles).toHaveLength(0);
    });

    it('should accept valid video files (QuickTime)', () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'video.mov', { type: 'video/quicktime' });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(1);
      expect(result.invalidFiles).toHaveLength(0);
    });

    it('should reject unsupported file types (text)', () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'document.txt', { type: 'text/plain' });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(false);
      expect(result.validFiles).toHaveLength(0);
      expect(result.invalidFiles).toHaveLength(1);
      expect(result.errors).toBeDefined();
      expect(result.errors![0]).toContain('tipo de arquivo não suportado');
    });

    it('should reject unsupported file types (PDF)', () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'document.pdf', { type: 'application/pdf' });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(false);
      expect(result.invalidFiles).toHaveLength(1);
      expect(result.errors![0]).toContain('tipo de arquivo não suportado');
    });

    it('should reject files exceeding size limit', () => {
      const { validateFiles } = useMediaUpload();
      // Mock a file larger than 100MB by setting size property
      const file = new File(['content'], 'large.jpg', { type: 'image/jpeg' });
      Object.defineProperty(file, 'size', { value: 101 * 1024 * 1024 });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(false);
      expect(result.validFiles).toHaveLength(0);
      expect(result.invalidFiles).toHaveLength(1);
      expect(result.errors).toBeDefined();
      expect(result.errors![0]).toContain('arquivo muito grande');
      expect(result.errors![0]).toContain('100MB');
    });

    it('should accept files at size limit (100MB)', () => {
      const { validateFiles } = useMediaUpload();
      // Mock a file exactly 100MB by setting size property
      const file = new File(['content'], 'max.jpg', { type: 'image/jpeg' });
      Object.defineProperty(file, 'size', { value: 100 * 1024 * 1024 });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(1);
      expect(result.invalidFiles).toHaveLength(0);
    });

    it('should handle multiple valid files', () => {
      const { validateFiles } = useMediaUpload();
      const files = [
        new File(['content'], 'photo1.jpg', { type: 'image/jpeg' }),
        new File(['content'], 'photo2.png', { type: 'image/png' }),
        new File(['content'], 'video.mp4', { type: 'video/mp4' })
      ];
      
      const result = validateFiles(files);
      
      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(3);
      expect(result.invalidFiles).toHaveLength(0);
    });

    it('should separate valid and invalid files', () => {
      const { validateFiles } = useMediaUpload();
      const files = [
        new File(['content'], 'photo.jpg', { type: 'image/jpeg' }),
        new File(['content'], 'document.txt', { type: 'text/plain' }),
        new File(['content'], 'video.mp4', { type: 'video/mp4' })
      ];
      
      const result = validateFiles(files);
      
      expect(result.isValid).toBe(false);
      expect(result.validFiles).toHaveLength(2);
      expect(result.invalidFiles).toHaveLength(1);
      expect(result.errors).toHaveLength(1);
    });

    it('should provide error message for each invalid file', () => {
      const { validateFiles } = useMediaUpload();
      const files = [
        new File(['content'], 'doc1.txt', { type: 'text/plain' }),
        new File(['content'], 'doc2.pdf', { type: 'application/pdf' })
      ];
      
      const result = validateFiles(files);
      
      expect(result.errors).toHaveLength(2);
      expect(result.errors![0]).toContain('doc1.txt');
      expect(result.errors![1]).toContain('doc2.pdf');
    });

    it('should handle empty file array', () => {
      const { validateFiles } = useMediaUpload();
      
      const result = validateFiles([]);
      
      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(0);
      expect(result.invalidFiles).toHaveLength(0);
    });

    it('should reject file with both invalid type and size', () => {
      const { validateFiles } = useMediaUpload();
      // Mock a large file with invalid type
      const file = new File(['content'], 'large.txt', { type: 'text/plain' });
      Object.defineProperty(file, 'size', { value: 101 * 1024 * 1024 });
      
      const result = validateFiles([file]);
      
      expect(result.isValid).toBe(false);
      expect(result.invalidFiles).toHaveLength(1);
      // Should have error for type (size check happens after type check fails)
      expect(result.errors![0]).toContain('tipo de arquivo não suportado');
    });
  });

  describe('uploadFiles', () => {
    it('should upload valid files successfully', async () => {
      const { uploadFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const mockMedia: Media = {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/media/photo.jpg',
        thumbnail_url: '/media/thumb/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        options.onSuccess({ props: { media: mockMedia } });
      });

      const result = await uploadFiles(1, [file]);

      expect(router.post).toHaveBeenCalledWith(
        '/media/upload',
        expect.any(FormData),
        expect.objectContaining({
          preserveScroll: true,
          forceFormData: true
        })
      );
      expect(result).toHaveLength(1);
      expect(result[0]).toEqual(mockMedia);
    });

    it('should reject invalid files before upload', async () => {
      const { uploadFiles } = useMediaUpload();
      const file = new File(['content'], 'document.txt', { type: 'text/plain' });

      await expect(uploadFiles(1, [file])).rejects.toThrow();
      expect(router.post).not.toHaveBeenCalled();
    });

    it('should throw validation error with invalid files', async () => {
      const { uploadFiles } = useMediaUpload();
      const file = new File(['content'], 'document.txt', { type: 'text/plain' });

      try {
        await uploadFiles(1, [file]);
        expect.fail('Should have thrown error');
      } catch (error) {
        const uploadError = error as UploadError;
        expect(uploadError.message).toContain('inválidos');
        expect(uploadError.code).toBe('VALIDATION_FAILED');
        expect(uploadError.files).toHaveLength(1);
      }
    });

    it('should upload multiple files in parallel', async () => {
      const { uploadFiles } = useMediaUpload();
      const files = [
        new File(['content1'], 'photo1.jpg', { type: 'image/jpeg' }),
        new File(['content2'], 'photo2.jpg', { type: 'image/jpeg' })
      ];

      let callCount = 0;
      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        callCount++;
        const media: Media = {
          id: callCount,
          album_id: 1,
          filename: `photo${callCount}.jpg`,
          type: 'image',
          mime_type: 'image/jpeg',
          size: 1024,
          url: `/media/photo${callCount}.jpg`,
          thumbnail_url: `/media/thumb/photo${callCount}.jpg`,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-01T00:00:00Z'
        };
        options.onSuccess({ props: { media } });
      });

      const result = await uploadFiles(1, files);

      expect(router.post).toHaveBeenCalledTimes(2);
      expect(result).toHaveLength(2);
      expect(result[0].filename).toBe('photo1.jpg');
      expect(result[1].filename).toBe('photo2.jpg');
    });
  });

  describe('uploadingFiles tracking', () => {
    it('should track uploading files', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const mockMedia: Media = {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/media/photo.jpg',
        thumbnail_url: '/media/thumb/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      let uploadingDuringCall = 0;
      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        uploadingDuringCall = uploadingFiles.value.length;
        options.onSuccess({ props: { media: mockMedia } });
      });

      await uploadFiles(1, [file]);

      expect(uploadingDuringCall).toBe(1);
    });

    it('should set initial progress to 0', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const mockMedia: Media = {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/media/photo.jpg',
        thumbnail_url: '/media/thumb/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      let initialProgress = -1;
      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        initialProgress = uploadingFiles.value[0]?.progress ?? -1;
        options.onSuccess({ props: { media: mockMedia } });
      });

      await uploadFiles(1, [file]);

      expect(initialProgress).toBe(0);
    });

    it('should update progress during upload', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const mockMedia: Media = {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/media/photo.jpg',
        thumbnail_url: '/media/thumb/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      let progressUpdated = false;
      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        // Simulate progress update
        options.onProgress({ percentage: 50 });
        progressUpdated = uploadingFiles.value[0]?.progress === 50;
        options.onSuccess({ props: { media: mockMedia } });
      });

      await uploadFiles(1, [file]);

      expect(progressUpdated).toBe(true);
    });

    it('should set status to completed on success', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const mockMedia: Media = {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/media/photo.jpg',
        thumbnail_url: '/media/thumb/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      let finalStatus = '';
      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        options.onSuccess({ props: { media: mockMedia } });
        finalStatus = uploadingFiles.value[0]?.status ?? '';
      });

      await uploadFiles(1, [file]);

      expect(finalStatus).toBe('completed');
    });

    it('should remove completed upload after delay', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const mockMedia: Media = {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/media/photo.jpg',
        thumbnail_url: '/media/thumb/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        options.onSuccess({ props: { media: mockMedia } });
      });

      await uploadFiles(1, [file]);
      expect(uploadingFiles.value).toHaveLength(1);

      // Fast-forward time by 2 seconds
      vi.advanceTimersByTime(2000);

      expect(uploadingFiles.value).toHaveLength(0);
    });
  });

  describe('error handling', () => {
    it('should handle upload error', async () => {
      const { uploadFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });

      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        options.onError({ file: 'Upload failed' });
      });

      await expect(uploadFiles(1, [file])).rejects.toThrow();
    });

    it('should set status to failed on error', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });

      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        options.onError({ file: 'Upload failed' });
      });

      try {
        await uploadFiles(1, [file]);
      } catch (error) {
        // Expected error
      }

      // Check status after error is handled
      expect(uploadingFiles.value[0]?.status).toBe('failed');
    });

    it('should store error message on failure', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const errorMessage = 'Network error';

      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        options.onError({ file: errorMessage });
      });

      try {
        await uploadFiles(1, [file]);
      } catch (error) {
        // Expected error
      }

      // Check error message after error is handled
      expect(uploadingFiles.value[0]?.error).toBe(errorMessage);
    });

    it('should remove failed upload after delay', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });

      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        options.onError({ file: 'Upload failed' });
      });

      try {
        await uploadFiles(1, [file]);
      } catch (error) {
        // Expected error
      }

      expect(uploadingFiles.value).toHaveLength(1);

      // Fast-forward time by 3 seconds
      vi.advanceTimersByTime(3000);

      expect(uploadingFiles.value).toHaveLength(0);
    });

    it('should handle missing media in response', async () => {
      const { uploadFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });

      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        options.onSuccess({ props: {} }); // No media in response
      });

      await expect(uploadFiles(1, [file])).rejects.toThrow('No media returned from server');
    });
  });

  describe('cancelUpload', () => {
    it('should remove file from uploading list', () => {
      const { uploadingFiles, cancelUpload } = useMediaUpload();
      
      // Manually add a file to the uploading list
      const mockUploadingFile = {
        id: 'test-id-123',
        file: new File(['content'], 'photo.jpg', { type: 'image/jpeg' }),
        progress: 50,
        status: 'uploading' as const,
      };
      
      uploadingFiles.value.push(mockUploadingFile);
      expect(uploadingFiles.value).toHaveLength(1);

      cancelUpload('test-id-123');

      expect(uploadingFiles.value).toHaveLength(0);
    });

    it('should handle canceling non-existent upload', () => {
      const { uploadingFiles, cancelUpload } = useMediaUpload();

      expect(() => cancelUpload('non-existent-id')).not.toThrow();
      expect(uploadingFiles.value).toHaveLength(0);
    });
  });

  describe('Edge Cases', () => {
    it('should handle progress update with undefined percentage', async () => {
      const { uploadFiles, uploadingFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const mockMedia: Media = {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/media/photo.jpg',
        thumbnail_url: '/media/thumb/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      let progressBeforeSuccess = 0;
      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        // Simulate progress update with undefined percentage
        options.onProgress({});
        progressBeforeSuccess = uploadingFiles.value[0]?.progress ?? -1;
        options.onSuccess({ props: { media: mockMedia } });
      });

      await uploadFiles(1, [file]);

      // Should not crash, progress should remain at initial value (0) before success
      expect(progressBeforeSuccess).toBe(0);
    });

    it('should handle progress update with null', async () => {
      const { uploadFiles } = useMediaUpload();
      const file = new File(['content'], 'photo.jpg', { type: 'image/jpeg' });
      const mockMedia: Media = {
        id: 1,
        album_id: 1,
        filename: 'photo.jpg',
        type: 'image',
        mime_type: 'image/jpeg',
        size: 1024,
        url: '/media/photo.jpg',
        thumbnail_url: '/media/thumb/photo.jpg',
        created_at: '2024-01-01T00:00:00Z',
        updated_at: '2024-01-01T00:00:00Z'
      };

      vi.mocked(router.post).mockImplementation((url, data, options: any) => {
        // Simulate progress update with null
        options.onProgress(null);
        options.onSuccess({ props: { media: mockMedia } });
      });

      // Should not crash
      await expect(uploadFiles(1, [file])).resolves.toBeDefined();
    });

    it('should handle file with special characters in name', async () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'photo (1) [test].jpg', { type: 'image/jpeg' });

      const result = validateFiles([file]);

      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(1);
    });

    it('should handle file with unicode characters in name', async () => {
      const { validateFiles } = useMediaUpload();
      const file = new File(['content'], 'фото.jpg', { type: 'image/jpeg' });

      const result = validateFiles([file]);

      expect(result.isValid).toBe(true);
      expect(result.validFiles).toHaveLength(1);
    });
  });
});
