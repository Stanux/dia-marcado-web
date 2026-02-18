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
     * Rename a media file display name.
     *
     * Keeps the original extension and ensures unique names inside the album.
     */
    public function rename(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:180',
            ]);

            $user = $request->user();
            $weddingId = $user->current_wedding_id;

            if (!$weddingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum casamento selecionado.',
                ], 400);
            }

            $media = SiteMedia::where('id', $id)
                ->where('wedding_id', $weddingId)
                ->firstOrFail();

            $baseName = $this->sanitizeBaseName($validated['name']);
            if ($baseName === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Informe um nome válido para a mídia.',
                ], 422);
            }

            $extension = $this->resolveMediaExtension($media);
            $finalName = $this->generateUniqueMediaName($media, $baseName, $extension);

            $media->original_name = $finalName;
            $media->save();

            Log::info('Media renamed', [
                'media_id' => $media->id,
                'album_id' => $media->album_id,
                'wedding_id' => $weddingId,
                'new_name' => $finalName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mídia renomeada com sucesso.',
                'data' => [
                    'id' => $media->id,
                    'filename' => $media->original_name,
                ],
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
                'message' => 'Mídia não encontrada.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error renaming media', [
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao renomear mídia. Tente novamente.',
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
                'output_width' => 'nullable|integer|min:1',
                'output_height' => 'nullable|integer|min:1',
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

            // Get the image from storage (support local, remote, and public paths)
            $image = null;
            $targetDisk = $media->disk;
            $imagePath = Storage::disk($media->disk)->path($media->path);
            $createImageFromPath = function (string $path) use ($media) {
                return match ($media->mime_type) {
                    'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
                    'image/png' => imagecreatefrompng($path),
                    'image/gif' => imagecreatefromgif($path),
                    default => null,
                };
            };

            if (file_exists($imagePath)) {
                $image = $createImageFromPath($imagePath);
            } elseif (Storage::disk($media->disk)->exists($media->path)) {
                $contents = Storage::disk($media->disk)->get($media->path);
                $image = imagecreatefromstring($contents);
            } else {
                $publicCandidates = [];
                if (str_starts_with($media->path, 'storage/')) {
                    $publicCandidates[] = public_path($media->path);
                } else {
                    $publicCandidates[] = public_path('storage/' . $media->path);
                }

                foreach ($publicCandidates as $publicPath) {
                    if (file_exists($publicPath)) {
                        $image = $createImageFromPath($publicPath);
                        $targetDisk = 'public';
                        break;
                    }
                }

                if (!$image) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Arquivo de imagem não encontrado.'
                    ], 404);
                }
            }

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de imagem não suportado.'
                ], 400);
            }

            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);

            if (
                ($validated['x'] + $validated['width']) > $sourceWidth ||
                ($validated['y'] + $validated['height']) > $sourceHeight
            ) {
                imagedestroy($image);

                return response()->json([
                    'success' => false,
                    'message' => 'Área de recorte inválida para o tamanho da imagem.',
                ], 422);
            }

            $outputWidth = (int) ($validated['output_width'] ?? $validated['width']);
            $outputHeight = (int) ($validated['output_height'] ?? $validated['height']);

            if ($outputWidth > $validated['width'] || $outputHeight > $validated['height']) {
                imagedestroy($image);

                return response()->json([
                    'success' => false,
                    'message' => 'O redimensionamento de saída deve ser menor ou igual à área de recorte.',
                ], 422);
            }

            // Create cropped image
            $croppedImage = imagecreatetruecolor($outputWidth, $outputHeight);
            
            // Preserve transparency for PNG
            if ($media->mime_type === 'image/png') {
                imagealphablending($croppedImage, false);
                imagesavealpha($croppedImage, true);
            }

            // Copy, crop and optionally downscale output
            imagecopyresampled(
                $croppedImage,
                $image,
                0,
                0,
                $validated['x'],
                $validated['y'],
                $outputWidth,
                $outputHeight,
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

            if (Storage::disk('public')->exists($media->path)) {
                $targetDisk = 'public';
            }

            // Upload to storage
            $fileContents = file_get_contents($tempPath);
            Storage::disk($targetDisk)->put($newPath, $fileContents);
            @unlink($tempPath);

            $croppedDisplayName = $this->generateUniqueCroppedMediaName($media, $outputWidth, $outputHeight);

            // Create new media record
            $newMedia = SiteMedia::create([
                'wedding_id' => $weddingId,
                'album_id' => $media->album_id,
                'site_layout_id' => $media->site_layout_id,
                'filename' => $newFilename,
                'original_name' => $croppedDisplayName,
                'path' => $newPath,
                'disk' => $targetDisk,
                'mime_type' => $media->mime_type,
                'size' => Storage::disk($targetDisk)->size($newPath),
                'width' => $outputWidth,
                'height' => $outputHeight,
                'alt' => $media->alt,
                'variants' => [],
                'metadata' => array_merge($media->metadata ?? [], [
                    'cropped_from' => $media->id,
                    'crop_coordinates' => $validated,
                    'output_dimensions' => [
                        'width' => $outputWidth,
                        'height' => $outputHeight,
                    ],
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

    private function sanitizeBaseName(string $name): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($value === '') {
            return '';
        }

        $value = pathinfo($value, PATHINFO_FILENAME) ?: $value;
        $value = preg_replace('/[\/\\\\:*?"<>|]+/u', '', $value) ?? '';
        $value = trim($value, ". \t\n\r\0\x0B");

        return $value;
    }

    private function resolveMediaExtension(SiteMedia $media): string
    {
        $fromOriginalName = pathinfo((string) $media->original_name, PATHINFO_EXTENSION);
        if (is_string($fromOriginalName) && $fromOriginalName !== '') {
            return mb_strtolower($fromOriginalName);
        }

        $fromPath = pathinfo((string) $media->path, PATHINFO_EXTENSION);
        return is_string($fromPath) ? mb_strtolower($fromPath) : '';
    }

    private function generateUniqueMediaName(SiteMedia $media, string $baseName, string $extension): string
    {
        $index = 0;

        do {
            $candidateBase = $index === 0 ? $baseName : "{$baseName}_{$index}";
            $candidate = $extension !== '' ? "{$candidateBase}.{$extension}" : $candidateBase;

            $exists = SiteMedia::query()
                ->where('wedding_id', $media->wedding_id)
                ->where('album_id', $media->album_id)
                ->where('id', '!=', $media->id)
                ->whereRaw('LOWER(original_name) = ?', [mb_strtolower($candidate)])
                ->exists();

            if (!$exists) {
                return $candidate;
            }

            $index++;
        } while (true);
    }

    private function generateUniqueCroppedMediaName(SiteMedia $media, int $width, int $height): string
    {
        $sourceBase = $this->sanitizeBaseName(
            (string) pathinfo((string) $media->original_name, PATHINFO_FILENAME)
        );

        if ($sourceBase === '') {
            $sourceBase = 'imagem';
        }

        $baseWithSize = "{$sourceBase}_{$width}x{$height}";
        $extension = $this->resolveMediaExtension($media);
        $index = 1;

        do {
            $candidateBase = "{$baseWithSize}_{$index}";
            $candidate = $extension !== '' ? "{$candidateBase}.{$extension}" : $candidateBase;

            $exists = SiteMedia::query()
                ->where('wedding_id', $media->wedding_id)
                ->where('album_id', $media->album_id)
                ->whereRaw('LOWER(original_name) = ?', [mb_strtolower($candidate)])
                ->exists();

            if (!$exists) {
                return $candidate;
            }

            $index++;
        } while (true);
    }
}
