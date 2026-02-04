<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\SiteMedia;
use App\Models\Wedding;
use App\Services\Media\BatchUploadService;
use App\Services\Media\QuotaTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Controller for media file operations.
 * 
 * Handles uploading and deleting media files.
 */
class MediaController extends Controller
{
    public function __construct(
        private readonly BatchUploadService $batchUploadService,
        private readonly QuotaTrackingService $quotaService,
        private readonly \App\Contracts\Media\AlbumManagementServiceInterface $albumManagementService
    ) {}

    /**
     * Upload media file(s) to an album.
     * 
     * Handles single or multiple file uploads with quota checking
     * and batch processing.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'file' => 'required|file|mimes:jpeg,jpg,png,gif,mp4,mov,quicktime|max:102400', // 100MB max
                'album_id' => 'required|uuid|exists:albums,id',
            ]);

            $user = $request->user();
            $weddingId = $user->current_wedding_id;
            
            if (!$weddingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum casamento selecionado.'
                ], 400);
            }

            $wedding = Wedding::findOrFail($weddingId);
            
            // Verify album belongs to wedding
            $album = Album::where('id', $validated['album_id'])
                ->where('wedding_id', $weddingId)
                ->firstOrFail();

            $file = $request->file('file');
            
            // Check quota before upload
            $quotaCheck = $this->quotaService->canUpload($wedding, $file->getSize());
            
            if (!$quotaCheck->canUpload) {
                return response()->json([
                    'success' => false,
                    'message' => $quotaCheck->reason,
                    'upgrade_message' => $quotaCheck->upgradeMessage,
                ], 403);
            }

            // Create batch for single file upload
            $batch = $this->batchUploadService->createBatch($wedding, 1, $album);
            
            // Process the file
            $result = $this->batchUploadService->processFile($batch, $file);
            
            if (!$result->success) {
                return response()->json([
                    'success' => false,
                    'message' => $result->error ?? 'Erro ao fazer upload do arquivo.'
                ], 400);
            }

            // Clear quota cache
            $this->quotaService->clearCache($wedding);

            // Load the media with full details
            $media = SiteMedia::with('album')->findOrFail($result->mediaId);

            Log::info('Media uploaded', [
                'media_id' => $media->id,
                'album_id' => $album->id,
                'wedding_id' => $wedding->id,
                'filename' => $media->original_name,
            ]);

            // Return media data as JSON
            return response()->json([
                'success' => true,
                'media' => [
                    'id' => $media->id,
                    'album_id' => $media->album_id,
                    'filename' => $media->original_name,
                    'type' => str_starts_with($media->mime_type, 'image/') ? 'image' : 'video',
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'thumbnail_url' => $media->getVariantUrl('thumbnail') ?? $media->getUrl(),
                    'created_at' => $media->created_at->toISOString(),
                    'updated_at' => $media->updated_at->toISOString(),
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Álbum não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error uploading media', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Delete a media file.
     * 
     * Removes the media file from storage and database.
     * Updates the album's media count.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $weddingId = $user->current_wedding_id;
            
            if (!$weddingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum casamento selecionado.'
                ], 400);
            }

            // Find media and verify it belongs to the wedding
            $media = SiteMedia::where('id', $id)
                ->where('wedding_id', $weddingId)
                ->firstOrFail();

            $albumId = $media->album_id;
            $filename = $media->original_name;

            // Delete file from storage
            if ($media->path && Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }

            // Delete variants if they exist
            if ($media->variants && is_array($media->variants)) {
                foreach ($media->variants as $variantPath) {
                    if (Storage::disk($media->disk)->exists($variantPath)) {
                        Storage::disk($media->disk)->delete($variantPath);
                    }
                }
            }

            // Delete database record
            $media->delete();

            // Clear quota cache
            $wedding = Wedding::findOrFail($weddingId);
            $this->quotaService->clearCache($wedding);

            Log::info('Media deleted', [
                'media_id' => $id,
                'album_id' => $albumId,
                'wedding_id' => $weddingId,
                'filename' => $filename,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mídia excluída com sucesso.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mídia não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting media', [
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir mídia. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Batch move media to another album.
     * 
     * POST /admin/media/batch-move
     * 
     * @Requirements: Fase 1 - Mover múltiplas fotos entre álbuns
     */
    public function batchMove(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'media_ids' => 'required|array|min:1',
                'media_ids.*' => 'required|string|exists:site_media,id',
                'target_album_id' => 'required|string|exists:albums,id',
            ]);

            $user = $request->user();
            $weddingId = $user->current_wedding_id;
            
            if (!$weddingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum casamento selecionado.'
                ], 400);
            }

            $mediaIds = $validated['media_ids'];
            $targetAlbumId = $validated['target_album_id'];

            // Get target album
            $targetAlbum = Album::where('id', $targetAlbumId)
                ->where('wedding_id', $weddingId)
                ->firstOrFail();

            // Get media items
            $mediaItems = SiteMedia::whereIn('id', $mediaIds)
                ->where('wedding_id', $weddingId)
                ->get();

            // Verify all media belong to wedding
            if ($mediaItems->count() !== count($mediaIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uma ou mais mídias não pertencem a este casamento.'
                ], 403);
            }

            $movedCount = 0;
            foreach ($mediaItems as $media) {
                $this->albumManagementService->moveMedia($media, $targetAlbum);
                $movedCount++;
            }

            Log::info('Batch move media', [
                'media_count' => $movedCount,
                'target_album_id' => $targetAlbumId,
                'wedding_id' => $weddingId,
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$movedCount} foto(s) movida(s) com sucesso.",
                'moved_count' => $movedCount,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Álbum ou mídia não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error batch moving media', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao mover fotos. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Crop an image and create a new variant.
     * 
     * POST /api/media/{media}/crop
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function crop(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'x' => 'required|integer|min:0',
                'y' => 'required|integer|min:0',
                'width' => 'required|integer|min:1',
                'height' => 'required|integer|min:1',
            ]);

            $user = $request->user();
            $weddingId = $user->current_wedding_id;
            
            if (!$weddingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum casamento selecionado.'
                ], 400);
            }

            // Find media and verify it belongs to the wedding
            $media = SiteMedia::where('id', $id)
                ->where('wedding_id', $weddingId)
                ->firstOrFail();

            // Verify it's an image
            if (!str_starts_with($media->mime_type, 'image/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Apenas imagens podem ser cortadas.'
                ], 400);
            }

            // Get the image from storage
            $imagePath = Storage::disk($media->disk)->path($media->path);
            
            if (!file_exists($imagePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Arquivo de imagem não encontrado.'
                ], 404);
            }

            // Create image resource based on mime type
            $image = match ($media->mime_type) {
                'image/jpeg', 'image/jpg' => imagecreatefromjpeg($imagePath),
                'image/png' => imagecreatefrompng($imagePath),
                'image/gif' => imagecreatefromgif($imagePath),
                default => null,
            };

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de imagem não suportado.'
                ], 400);
            }

            // Create cropped image
            $croppedImage = imagecreatetruecolor($validated['width'], $validated['height']);
            
            // Preserve transparency for PNG
            if ($media->mime_type === 'image/png') {
                imagealphablending($croppedImage, false);
                imagesavealpha($croppedImage, true);
            }

            // Copy and crop
            imagecopy(
                $croppedImage,
                $image,
                0,
                0,
                $validated['x'],
                $validated['y'],
                $validated['width'],
                $validated['height']
            );

            // Generate new filename
            $pathInfo = pathinfo($media->path);
            $newFilename = $pathInfo['filename'] . '_cropped_' . time() . '.' . $pathInfo['extension'];
            $newPath = $pathInfo['dirname'] . '/' . $newFilename;

            // Save cropped image
            $tempPath = storage_path('app/temp/' . $newFilename);
            @mkdir(dirname($tempPath), 0755, true);

            $saved = match ($media->mime_type) {
                'image/jpeg', 'image/jpg' => imagejpeg($croppedImage, $tempPath, 90),
                'image/png' => imagepng($croppedImage, $tempPath, 9),
                'image/gif' => imagegif($croppedImage, $tempPath),
                default => false,
            };

            // Free memory
            imagedestroy($image);
            imagedestroy($croppedImage);

            if (!$saved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao salvar imagem cortada.'
                ], 500);
            }

            // Upload to storage
            $fileContents = file_get_contents($tempPath);
            Storage::disk($media->disk)->put($newPath, $fileContents);
            @unlink($tempPath);

            // Create new media record
            $newMedia = SiteMedia::create([
                'wedding_id' => $weddingId,
                'album_id' => $media->album_id,
                'site_layout_id' => $media->site_layout_id,
                'filename' => $newFilename,
                'original_name' => $media->original_name . ' (cortada)',
                'path' => $newPath,
                'disk' => $media->disk,
                'mime_type' => $media->mime_type,
                'size' => filesize(Storage::disk($media->disk)->path($newPath)),
                'width' => $validated['width'],
                'height' => $validated['height'],
                'alt' => $media->alt,
                'variants' => [],
                'metadata' => array_merge($media->metadata ?? [], [
                    'cropped_from' => $media->id,
                    'crop_coordinates' => $validated,
                ]),
            ]);

            Log::info('Media cropped', [
                'original_media_id' => $media->id,
                'new_media_id' => $newMedia->id,
                'crop_coordinates' => $validated,
                'wedding_id' => $weddingId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagem cortada com sucesso.',
                'data' => [
                    'id' => $newMedia->id,
                    'album_id' => $newMedia->album_id,
                    'filename' => $newMedia->original_name,
                    'type' => 'image',
                    'mime_type' => $newMedia->mime_type,
                    'size' => $newMedia->size,
                    'width' => $newMedia->width,
                    'height' => $newMedia->height,
                    'url' => $newMedia->getUrl(),
                    'thumbnail_url' => $newMedia->getVariantUrl('thumbnail') ?? $newMedia->getUrl(),
                    'alt' => $newMedia->alt,
                    'created_at' => $newMedia->created_at->toISOString(),
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mídia não encontrada.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error cropping media', [
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao cortar imagem. Tente novamente.'
            ], 500);
        }
    }
}
