<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumType;
use App\Models\Wedding;
use App\Services\Media\AlbumManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Controller for album management operations.
 * 
 * Handles creating, updating, and deleting albums.
 */
class AlbumController extends Controller
{
    public function __construct(
        private readonly AlbumManagementService $albumService
    ) {}

    /**
     * Create a new album.
     * 
     * Creates an album for the current wedding with the specified name and type.
     * If no type is provided, defaults to 'uso_site'.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'type' => 'nullable|string|in:pre_casamento,pos_casamento,uso_site',
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
            
            // Default to 'uso_site' if no type provided
            $typeSlug = $validated['type'] ?? 'uso_site';
            
            // Create album using service
            $album = $this->albumService->createAlbum($wedding, $typeSlug, [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            Log::info('Album created', [
                'album_id' => $album->id,
                'wedding_id' => $wedding->id,
                'name' => $album->name,
            ]);

            // Load the album type relationship
            $album->load('albumType');

            // Return JSON response
            return response()->json([
                'success' => true,
                'message' => 'Álbum criado com sucesso!',
                'album' => [
                    'id' => $album->id,
                    'name' => $album->name,
                    'type' => $album->albumType?->slug ?? 'uso_site',
                    'description' => $album->description,
                    'media_count' => 0,
                    'media' => [],
                    'created_at' => $album->created_at->toISOString(),
                    'updated_at' => $album->updated_at->toISOString(),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error creating album', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar álbum. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Update an existing album.
     * 
     * Updates the album name and type.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'type' => 'required|string|in:pre_casamento,pos_casamento,uso_site',
            ]);

            $user = $request->user();
            $weddingId = $user->current_wedding_id;
            
            if (!$weddingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum casamento selecionado.'
                ], 400);
            }

            // Find album
            $album = Album::where('id', $id)
                ->where('wedding_id', $weddingId)
                ->firstOrFail();

            // Get album type
            $albumType = AlbumType::where('slug', $validated['type'])->firstOrFail();

            // Update album
            $album->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? $album->description,
                'album_type_id' => $albumType->id,
            ]);

            Log::info('Album updated', [
                'album_id' => $album->id,
                'wedding_id' => $weddingId,
                'name' => $album->name,
            ]);

            // Load the album type relationship
            $album->load('albumType');

            // Return JSON response
            return response()->json([
                'success' => true,
                'message' => 'Álbum atualizado com sucesso!',
                'album' => [
                    'id' => $album->id,
                    'name' => $album->name,
                    'type' => $album->albumType?->slug ?? 'uso_site',
                    'description' => $album->description,
                    'media_count' => $album->media()->count(),
                    'media' => $album->media()->where('status', 'completed')->get()->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'filename' => $media->original_name,
                            'type' => str_starts_with($media->mime_type, 'image/') ? 'image' : 'video',
                            'mime_type' => $media->mime_type,
                            'size' => $media->size,
                            'url' => $media->getUrl(),
                            'thumbnail_url' => $media->getVariantUrl('thumbnail') ?? $media->getUrl(),
                            'created_at' => $media->created_at->toISOString(),
                            'updated_at' => $media->updated_at->toISOString(),
                        ];
                    }),
                    'created_at' => $album->created_at->toISOString(),
                    'updated_at' => $album->updated_at->toISOString(),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating album', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar álbum. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Delete an album.
     * 
     * Only allows deletion if the album is empty (no media).
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

            // Find album
            $album = Album::where('id', $id)
                ->where('wedding_id', $weddingId)
                ->firstOrFail();

            // Check if album has media
            $mediaCount = $album->media()->count();
            if ($mediaCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível excluir um álbum que contém mídias. Exclua todas as mídias primeiro.'
                ], 400);
            }

            $albumName = $album->name;

            // Delete album
            $album->delete();

            Log::info('Album deleted', [
                'album_id' => $id,
                'wedding_id' => $weddingId,
                'name' => $albumName,
            ]);

            // Return JSON response
            return response()->json([
                'success' => true,
                'message' => 'Álbum excluído com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting album', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir álbum. Tente novamente.'
            ], 500);
        }
    }
}
