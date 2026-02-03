<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Wedding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for the Media Screen page.
 * 
 * Handles rendering the main media management interface with albums.
 */
class MediaScreenController extends Controller
{
    /**
     * Display the media screen with albums.
     * 
     * Loads all albums for the current wedding with their media count
     * and renders the MediaScreen Vue component.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $weddingId = $user->current_wedding_id;
        
        // Load albums with media count for the current wedding
        $albums = Album::where('wedding_id', $weddingId)
            ->withCount('media')
            ->with(['media' => function ($query) {
                $query->where('status', 'completed')
                    ->orderBy('created_at', 'desc');
            }, 'albumType'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($album) {
                return [
                    'id' => $album->id,
                    'name' => $album->name,
                    'type' => $album->albumType?->slug ?? 'uso_site',
                    'description' => $album->description,
                    'media_count' => $album->media_count,
                    'media' => $album->media->map(function ($media) {
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
                ];
            });
        
        return Inertia::render('MediaScreen', [
            'albums' => $albums,
        ]);
    }
}
