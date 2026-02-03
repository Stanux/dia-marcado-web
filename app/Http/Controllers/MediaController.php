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
        private readonly QuotaTrackingService $quotaService
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
}
