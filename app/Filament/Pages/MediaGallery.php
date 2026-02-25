<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Album;
use Filament\Panel;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Filament Page for Media Gallery.
 * 
 * Displays the media management interface with albums and media items.
 * Integrates Vue.js MediaScreen component within Filament layout.
 * 
 * @Requirements: 1.1, 2.1, 2.2, 2.3
 */
class MediaGallery extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $navigationLabel = 'Galeria de Mídias';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.media-gallery';

    protected static ?string $slug = 'media-gallery';
    
    // Ícone quando a página está ativa
    protected static ?string $activeNavigationIcon = 'heroicon-s-photo';
    
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return 'Galeria de Mídias';
    }
    
    /**
     * Retorna a URL da página para o Filament identificar corretamente
     */
    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }
    
    /**
     * Garante que o Filament reconheça esta página como ativa
     */
    public static function getNavigationUrl(): string
    {
        return static::getUrl();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Galeria de Mídias';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Galeria de Mídias';
    }

    /**
     * Get albums data for the view
     * 
     * Loads all albums with their media for the current wedding
     * and formats them for the Vue component.
     */
    public function getAlbumsProperty(): array
    {
        return Album::withCount('media')
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
            })
            ->toArray();
    }

    public static function canAccess(): bool
    {
        return false;
    }

    /**
     * Disable module routes: media management is handled in Site Editor.
     */
    public static function registerRoutes(Panel $panel): void
    {
        // Intentionally disabled.
    }
}
