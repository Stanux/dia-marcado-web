<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Media\AlbumManagementServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CreateAlbumRequest;
use App\Http\Requests\Media\DeleteAlbumRequest;
use App\Http\Requests\Media\UpdateAlbumRequest;
use App\Models\Album;
use App\Models\AlbumType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for album management operations.
 * 
 * Handles CRUD operations for albums and listing by type.
 * 
 * @Requirements: 2.2, 2.4, 2.6
 */
class AlbumController extends Controller
{
    public function __construct(
        private readonly AlbumManagementServiceInterface $albumManagementService,
    ) {}

    /**
     * List all albums grouped by type.
     * 
     * GET /api/albums
     * 
     * @Requirements: 2.4
     */
    public function index(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        $albums = Album::where('wedding_id', $wedding->id)
            ->with('albumType', 'coverMedia')
            ->withCount('media')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($album) {
                return [
                    'id' => $album->id,
                    'name' => $album->name,
                    'description' => $album->description,
                    'type_slug' => $album->albumType?->slug,
                    'type_name' => $album->albumType?->name,
                    'cover_url' => $album->coverMedia?->getVariantUrl('thumbnail') ?? $album->coverMedia?->getUrl(),
                    'media_count' => $album->media_count,
                    'created_at' => $album->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $albums,
        ]);
    }

    /**
     * Get album types.
     * 
     * GET /api/albums/types
     * 
     * @Requirements: 2.1
     */
    public function types(): JsonResponse
    {
        $types = AlbumType::all();

        return response()->json([
            'data' => $types->map(fn (AlbumType $type) => [
                'slug' => $type->slug,
                'name' => $type->name,
                'description' => $type->description,
            ]),
        ]);
    }

    /**
     * Create a new album.
     * 
     * POST /api/albums
     * 
     * @Requirements: 2.2
     */
    public function store(CreateAlbumRequest $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding;
        $validated = $request->validated();

        try {
            $album = $this->albumManagementService->createAlbum(
                $wedding,
                $validated['type_slug'],
                [
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'cover_media_id' => $validated['cover_media_id'] ?? null,
                ]
            );

            return response()->json([
                'data' => $this->formatAlbumResponse($album),
                'message' => 'Álbum criado com sucesso.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get album details.
     * 
     * GET /api/albums/{album}
     */
    public function show(Request $request, Album $album): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify album belongs to wedding
        if ($album->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'O álbum não pertence a este casamento.',
            ], 403);
        }

        return response()->json([
            'data' => $this->formatAlbumResponse($album, true),
        ]);
    }

    /**
     * Update an album.
     * 
     * PUT /api/albums/{album}
     */
    public function update(UpdateAlbumRequest $request, Album $album): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify album belongs to wedding
        if ($album->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'O álbum não pertence a este casamento.',
            ], 403);
        }

        $validated = $request->validated();

        $album = $this->albumManagementService->updateAlbum($album, $validated);

        return response()->json([
            'data' => $this->formatAlbumResponse($album),
            'message' => 'Álbum atualizado com sucesso.',
        ]);
    }

    /**
     * Delete an album.
     * 
     * DELETE /api/albums/{album}
     * 
     * @Requirements: 2.6
     */
    public function destroy(DeleteAlbumRequest $request, Album $album): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify album belongs to wedding
        if ($album->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'O álbum não pertence a este casamento.',
            ], 403);
        }

        $validated = $request->validated();
        $moveToAlbumId = $validated['move_to_album_id'] ?? null;
        $force = $validated['force'] ?? false;

        // Check if album has media
        $mediaCount = $album->media()->count();
        if ($mediaCount > 0 && !$moveToAlbumId && !$force) {
            return response()->json([
                'error' => 'Album Not Empty',
                'message' => "O álbum contém {$mediaCount} arquivo(s). Forneça um álbum de destino ou use force=true para excluir.",
                'media_count' => $mediaCount,
            ], 422);
        }

        $moveToAlbum = null;
        if ($moveToAlbumId) {
            $moveToAlbum = Album::find($moveToAlbumId);
            if ($moveToAlbum && $moveToAlbum->wedding_id !== $wedding->id) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'O álbum de destino não pertence a este casamento.',
                ], 403);
            }
        }

        try {
            $this->albumManagementService->deleteAlbum($album, $moveToAlbum);

            return response()->json([
                'message' => 'Álbum excluído com sucesso.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => 'Delete Failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get media from an album.
     * 
     * GET /api/albums/{album}/media
     */
    public function media(Request $request, Album $album): JsonResponse
    {
        $wedding = $request->user()->currentWedding;

        // Verify album belongs to wedding
        if ($album->wedding_id !== $wedding->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'O álbum não pertence a este casamento.',
            ], 403);
        }

        // Get media
        $media = $album->media()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($media) {
                return [
                    'id' => $media->id,
                    'filename' => $media->original_name,
                    'type' => str_starts_with($media->mime_type, 'image/') ? 'image' : 'video',
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'width' => $media->width,
                    'height' => $media->height,
                    'url' => $media->getUrl(),
                    'thumbnail_url' => $media->getVariantUrl('thumbnail') ?? $media->getUrl(),
                    'alt' => $media->alt,
                    'created_at' => $media->created_at->toIso8601String(),
                    'updated_at' => $media->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $media,
        ]);
    }

    /**
     * Format album response data.
     */
    private function formatAlbumResponse(Album $album, bool $detailed = false): array
    {
        $response = [
            'id' => $album->id,
            'name' => $album->name,
            'description' => $album->description,
            'type_slug' => $album->albumType?->slug,
            'type_name' => $album->albumType?->name,
            'cover_url' => $album->coverMedia?->getUrl(),
            'media_count' => $album->media()->count(),
            'created_at' => $album->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $response['cover_media'] = $album->coverMedia ? [
                'id' => $album->coverMedia->id,
                'url' => $album->coverMedia->getUrl(),
                'original_name' => $album->coverMedia->original_name,
            ] : null;
            $response['updated_at'] = $album->updated_at?->toIso8601String();
        }

        return $response;
    }
}
