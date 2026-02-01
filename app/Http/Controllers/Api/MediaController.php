<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Media\AlbumManagementServiceInterface;
use App\Contracts\Media\BatchUploadServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\BatchDeleteRequest;
use App\Http\Requests\Media\CreateBatchRequest;
use App\Http\Requests\Media\MediaIndexRequest;
use App\Http\Requests\Media\MoveMediaRequest;
use App\Http\Requests\Media\UploadFileRequest;
use App\Models\Album;
use App\Models\SiteMedia;
use App\Models\UploadBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controller for media management operations.
 * 
 * Handles batch uploads, media listing, deletion, and moving between albums.
 * 
 * @Requirements: 1.1, 1.6, 8.1, 8.3, 8.4, 8.5, 8.6
 */
class MediaController extends Controller
{
    public function __construct(
        private readonly BatchUploadServiceInterface $batchUploadService,
        private readonly AlbumManagementServiceInterface $albumManagementService,
    ) {}

    /**
     * Create a new upload batch.
     * 
     * POST /api/media/batch
     * 
     * @Requirements: 1.1
     */
    public function createBatch(CreateBatchRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding;
        $totalFiles = $request->validated('total_files');
        $albumId = $request->validated('album_id');

        $album = $albumId ? Album::find($albumId) : null;

        // Verify album belongs to wedding
        if ($album && $album->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'O álbum não pertence a este casamento.',
            ], 403);
        }

        $batch = $this->batchUploadService->createBatch($wedding, $totalFiles, $album);

        return response()->json([
            'data' => [
                'batch_id' => $batch->id,
                'total_files' => $batch->total_files,
                'status' => $batch->status,
            ],
            'message' => 'Batch criado com sucesso.',
        ], 201);
    }

    /**
     * Upload a file to a batch.
     * 
     * POST /api/media/batch/{batch}/upload
     * 
     * @Requirements: 1.1, 1.6
     */
    public function uploadFile(UploadFileRequest $request, UploadBatch $batch): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify batch belongs to wedding
        if ($batch->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'O batch não pertence a este casamento.',
            ], 403);
        }

        $file = $request->file('file');
        $originalName = $request->validated('original_name') ?? $file->getClientOriginalName();

        $result = $this->batchUploadService->processFile($batch, $file, $originalName);

        if (!$result->success) {
            return response()->json([
                'error' => 'Upload Failed',
                'message' => $result->error,
            ], 422);
        }

        return response()->json([
            'data' => $this->formatMediaResponse($result->media),
            'message' => 'Arquivo enviado com sucesso.',
        ], 201);
    }

    /**
     * Cancel an upload (mark as cancelled).
     * 
     * DELETE /api/media/{media}/cancel
     * 
     * @Requirements: 1.6
     */
    public function cancelUpload(Request $request, SiteMedia $media): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify media belongs to wedding
        if ($media->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'A mídia não pertence a este casamento.',
            ], 403);
        }

        // Only pending uploads can be cancelled
        if ($media->status !== 'pending') {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Apenas uploads pendentes podem ser cancelados.',
            ], 400);
        }

        $media->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Upload cancelado com sucesso.',
        ]);
    }

    /**
     * List media with filters.
     * 
     * GET /api/media
     * 
     * @Requirements: 8.1, 8.6
     */
    public function index(MediaIndexRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding;
        $validated = $request->validated();

        $query = SiteMedia::where('wedding_id', $wedding->id)
            ->where('status', 'completed');

        // Filter by album
        if (!empty($validated['album_id'])) {
            $query->where('album_id', $validated['album_id']);
        }

        // Filter by album type
        if (!empty($validated['album_type'])) {
            $query->whereHas('album', function ($q) use ($validated) {
                $q->whereHas('albumType', function ($q2) use ($validated) {
                    $q2->where('slug', $validated['album_type']);
                });
            });
        }

        // Filter by mime type
        if (!empty($validated['mime_type'])) {
            $query->where('mime_type', 'like', $validated['mime_type'] . '%');
        }

        // Search by name
        if (!empty($validated['search'])) {
            $query->where('original_name', 'ilike', '%' . $validated['search'] . '%');
        }

        // Pagination
        $perPage = $validated['per_page'] ?? 20;
        $media = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $media->map(fn (SiteMedia $item) => $this->formatMediaResponse($item)),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    /**
     * Get media details.
     * 
     * GET /api/media/{media}
     * 
     * @Requirements: 8.1
     */
    public function show(Request $request, SiteMedia $media): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify media belongs to wedding
        if ($media->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'A mídia não pertence a este casamento.',
            ], 403);
        }

        return response()->json([
            'data' => $this->formatMediaResponse($media, true),
        ]);
    }

    /**
     * Delete a media file.
     * 
     * DELETE /api/media/{media}
     * 
     * @Requirements: 8.3
     */
    public function destroy(Request $request, SiteMedia $media): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify media belongs to wedding
        if ($media->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'A mídia não pertence a este casamento.',
            ], 403);
        }

        // Delete file and variants from storage
        $this->deleteMediaFiles($media);

        // Delete database record
        $media->delete();

        return response()->json(null, 204);
    }

    /**
     * Batch delete media files.
     * 
     * POST /api/media/batch-delete
     * 
     * @Requirements: 8.5
     */
    public function batchDestroy(BatchDeleteRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding;
        $mediaIds = $request->validated('media_ids');

        $media = SiteMedia::whereIn('id', $mediaIds)
            ->where('wedding_id', $wedding->id)
            ->get();

        $deletedCount = 0;
        foreach ($media as $item) {
            $this->deleteMediaFiles($item);
            $item->delete();
            $deletedCount++;
        }

        return response()->json([
            'message' => "{$deletedCount} arquivo(s) excluído(s) com sucesso.",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Move media to another album.
     * 
     * POST /api/media/{media}/move
     * 
     * @Requirements: 8.4
     */
    public function move(MoveMediaRequest $request, SiteMedia $media): JsonResponse
    {
        $wedding = $request->user()->currentWedding;
        $albumId = $request->validated('album_id');

        // Verify media belongs to wedding
        if ($media->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'A mídia não pertence a este casamento.',
            ], 403);
        }

        $targetAlbum = Album::find($albumId);

        // Verify album belongs to wedding
        if ($targetAlbum->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'O álbum de destino não pertence a este casamento.',
            ], 403);
        }

        $updatedMedia = $this->albumManagementService->moveMedia($media, $targetAlbum);

        return response()->json([
            'data' => $this->formatMediaResponse($updatedMedia),
            'message' => 'Mídia movida com sucesso.',
        ]);
    }

    /**
     * Get batch status.
     * 
     * GET /api/media/batch/{batch}
     */
    public function batchStatus(Request $request, UploadBatch $batch): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify batch belongs to wedding
        if ($batch->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'O batch não pertence a este casamento.',
            ], 403);
        }

        $status = $this->batchUploadService->getBatchStatus($batch);

        return response()->json([
            'data' => [
                'batch_id' => $status->batchId,
                'total' => $status->total,
                'completed' => $status->completed,
                'failed' => $status->failed,
                'pending' => $status->pending,
                'progress_percentage' => $status->getProgressPercentage(),
                'is_complete' => $status->isComplete(),
                'errors' => $status->errors,
            ],
        ]);
    }

    /**
     * Delete media files from storage.
     */
    private function deleteMediaFiles(SiteMedia $media): void
    {
        $disk = $media->disk ?? 'public';

        // Delete main file
        if ($media->path && Storage::disk($disk)->exists($media->path)) {
            Storage::disk($disk)->delete($media->path);
        }

        // Delete variants
        if ($media->variants) {
            foreach ($media->variants as $variantPath) {
                if (Storage::disk($disk)->exists($variantPath)) {
                    Storage::disk($disk)->delete($variantPath);
                }
            }
        }
    }

    /**
     * Format media response data.
     */
    private function formatMediaResponse(SiteMedia $media, bool $detailed = false): array
    {
        $variants = $media->variants ?? [];
        $variantUrls = [];

        foreach ($variants as $name => $path) {
            $variantUrls[$name] = $media->getVariantUrl($name);
        }

        $response = [
            'id' => $media->id,
            'original_name' => $media->original_name,
            'url' => $media->getUrl(),
            'variants' => $variantUrls,
            'size' => $media->size,
            'size_mb' => round($media->size / 1024 / 1024, 2),
            'mime_type' => $media->mime_type,
            'album_id' => $media->album_id,
            'status' => $media->status,
            'created_at' => $media->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $response['album'] = $media->album ? [
                'id' => $media->album->id,
                'name' => $media->album->name,
                'type' => $media->album->albumType?->slug,
            ] : null;
            $response['batch_id'] = $media->batch_id;
            $response['error_message'] = $media->error_message;
        }

        return $response;
    }
}
