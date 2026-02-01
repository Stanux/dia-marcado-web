<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Site\MediaUploadServiceInterface;
use App\Exceptions\MediaUploadException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\UploadMediaRequest;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\SystemConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing media files for wedding sites.
 * 
 * Handles listing, uploading, deleting media files, and
 * checking storage usage for wedding sites.
 */
class SiteMediaController extends Controller
{
    public function __construct(
        private readonly MediaUploadServiceInterface $mediaUploadService
    ) {}

    /**
     * List all media files for a site.
     * 
     * GET /api/sites/{site}/media
     * 
     * @Requirements: 16.6
     */
    public function index(Request $request, SiteLayout $site): JsonResponse
    {
        // Authorize via policy 'view'
        if ($request->user()->cannot('view', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para visualizar as mídias deste site.',
            ], 403);
        }

        $media = $site->media()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $media->map(fn (SiteMedia $item) => $this->formatMediaResponse($item)),
        ]);
    }

    /**
     * Upload a new media file to the site.
     * 
     * POST /api/sites/{site}/media
     * 
     * @Requirements: 16.1-16.7
     */
    public function store(UploadMediaRequest $request, SiteLayout $site): JsonResponse
    {
        $file = $request->file('file');

        try {
            $media = $this->mediaUploadService->upload($file, $site);

            return response()->json([
                'data' => $this->formatMediaResponse($media),
                'message' => 'Arquivo enviado com sucesso.',
            ], 201);
        } catch (MediaUploadException $e) {
            return response()->json([
                'error' => 'Upload Failed',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        }
    }

    /**
     * Delete a media file from the site.
     * 
     * DELETE /api/sites/{site}/media/{media}
     * 
     * @Requirements: 16.6
     */
    public function destroy(Request $request, SiteLayout $site, SiteMedia $media): JsonResponse
    {
        // Authorize via policy 'update'
        if ($request->user()->cannot('update', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para excluir mídias deste site.',
            ], 403);
        }

        // Verify media belongs to this site
        if ($media->site_layout_id !== $site->id) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Mídia não encontrada neste site.',
            ], 404);
        }

        $this->mediaUploadService->delete($media);

        return response()->json(null, 204);
    }

    /**
     * Get storage usage for the wedding.
     * 
     * GET /api/sites/{site}/media/usage
     * 
     * @Requirements: 16.8
     */
    public function usage(Request $request, SiteLayout $site): JsonResponse
    {
        // Authorize via policy 'view'
        if ($request->user()->cannot('view', $site)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Você não tem permissão para visualizar o uso de armazenamento.',
            ], 403);
        }

        $wedding = $site->wedding;
        $usedBytes = $this->mediaUploadService->getStorageUsage($wedding);
        $limitBytes = SystemConfig::get('site.max_storage_per_wedding', 524288000);

        return response()->json([
            'data' => [
                'used_bytes' => $usedBytes,
                'limit_bytes' => $limitBytes,
                'used_mb' => round($usedBytes / 1024 / 1024, 2),
                'limit_mb' => round($limitBytes / 1024 / 1024, 2),
                'percentage' => $limitBytes > 0 ? round(($usedBytes / $limitBytes) * 100, 2) : 0,
            ],
        ]);
    }

    /**
     * Format media response data.
     */
    private function formatMediaResponse(SiteMedia $media): array
    {
        $variants = $media->variants ?? [];
        $variantUrls = [];

        foreach ($variants as $name => $path) {
            $variantUrls[$name] = $media->getVariantUrl($name);
        }

        return [
            'id' => $media->id,
            'original_name' => $media->original_name,
            'url' => $media->getUrl(),
            'variants' => $variantUrls,
            'size' => $media->size,
            'size_mb' => round($media->size / 1024 / 1024, 2),
            'mime_type' => $media->mime_type,
            'created_at' => $media->created_at->toIso8601String(),
        ];
    }
}
