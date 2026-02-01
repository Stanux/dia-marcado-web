<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Contracts\Media\AlbumManagementServiceInterface;
use App\Models\Album;
use App\Models\AlbumType;
use App\Models\SiteMedia;
use App\Models\Wedding;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service for managing albums and organizing media.
 * 
 * Provides methods to create, update, delete, and organize albums,
 * as well as move media between albums. Albums are categorized by type
 * (pre-wedding, post-wedding, site usage) and belong to a specific wedding.
 * 
 * @see Requirements 2.2 - Album creation requires album type selection
 * @see Requirements 2.4 - Albums grouped by type in listing
 * @see Requirements 2.5 - Moving media between albums preserves history
 * @see Requirements 2.6 - Album deletion with optional media migration
 */
class AlbumManagementService implements AlbumManagementServiceInterface
{
    /**
     * Create a new album for a wedding.
     * 
     * Creates an album with the specified type and data. The album type
     * must be one of the valid types: pre_casamento, pos_casamento, uso_site.
     *
     * @param Wedding $wedding The wedding to create the album for
     * @param string $typeSlug The album type slug (pre_casamento, pos_casamento, uso_site)
     * @param array{name: string, description?: string, cover_media_id?: string} $data Album data
     * @return Album The newly created album
     * 
     * @throws InvalidArgumentException If the type slug is invalid or empty
     * @throws ValidationException If validation fails
     * 
     * @see Property 4: Integridade Referencial de Álbum e Mídia
     * @see Requirements 2.2
     */
    public function createAlbum(Wedding $wedding, string $typeSlug, array $data): Album
    {
        // Validate type slug is not empty
        if (empty($typeSlug)) {
            throw new InvalidArgumentException('O tipo de álbum é obrigatório.');
        }

        // Validate album type exists
        $albumType = AlbumType::where('slug', $typeSlug)->first();
        
        if (!$albumType) {
            throw new InvalidArgumentException(
                "Tipo de álbum inválido: '{$typeSlug}'. Tipos válidos: " . 
                implode(', ', AlbumType::getSlugs())
            );
        }

        // Validate required fields
        if (empty($data['name'])) {
            throw ValidationException::withMessages([
                'name' => ['O nome do álbum é obrigatório.'],
            ]);
        }

        // Validate cover_media_id if provided
        if (!empty($data['cover_media_id'])) {
            $coverMedia = SiteMedia::where('id', $data['cover_media_id'])
                ->where('wedding_id', $wedding->id)
                ->first();
            
            if (!$coverMedia) {
                throw ValidationException::withMessages([
                    'cover_media_id' => ['A mídia de capa não foi encontrada ou não pertence a este casamento.'],
                ]);
            }
        }

        // Create the album
        return Album::create([
            'wedding_id' => $wedding->id,
            'album_type_id' => $albumType->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'cover_media_id' => $data['cover_media_id'] ?? null,
        ]);
    }

    /**
     * Update an existing album.
     * 
     * Updates the album's name, description, or cover media.
     * The album type cannot be changed after creation.
     *
     * @param Album $album The album to update
     * @param array{name?: string, description?: string, cover_media_id?: string|null} $data Data to update
     * @return Album The updated album
     * 
     * @throws ValidationException If validation fails
     */
    public function updateAlbum(Album $album, array $data): Album
    {
        $updateData = [];

        // Update name if provided
        if (array_key_exists('name', $data)) {
            if (empty($data['name'])) {
                throw ValidationException::withMessages([
                    'name' => ['O nome do álbum não pode ser vazio.'],
                ]);
            }
            $updateData['name'] = $data['name'];
        }

        // Update description if provided
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        // Update cover_media_id if provided
        if (array_key_exists('cover_media_id', $data)) {
            if ($data['cover_media_id'] !== null) {
                $coverMedia = SiteMedia::where('id', $data['cover_media_id'])
                    ->where('wedding_id', $album->wedding_id)
                    ->first();
                
                if (!$coverMedia) {
                    throw ValidationException::withMessages([
                        'cover_media_id' => ['A mídia de capa não foi encontrada ou não pertence a este casamento.'],
                    ]);
                }
            }
            $updateData['cover_media_id'] = $data['cover_media_id'];
        }

        if (!empty($updateData)) {
            $album->update($updateData);
        }

        return $album->fresh();
    }

    /**
     * Delete an album.
     * 
     * Deletes the album. If the album contains media files, they can optionally
     * be moved to another album. If no target album is specified and the album
     * contains media, the operation will fail.
     *
     * @param Album $album The album to delete
     * @param Album|null $moveToAlbum Optional album to move media to before deletion
     * @return bool True if deletion was successful
     * 
     * @throws RuntimeException If album has media and no target album specified
     * @throws InvalidArgumentException If target album belongs to different wedding
     * 
     * @see Requirements 2.6
     */
    public function deleteAlbum(Album $album, ?Album $moveToAlbum = null): bool
    {
        // Check if album has media
        $mediaCount = $album->media()->count();

        if ($mediaCount > 0) {
            if ($moveToAlbum === null) {
                throw new RuntimeException(
                    "O álbum contém {$mediaCount} arquivo(s) de mídia. " .
                    "Especifique um álbum de destino para mover os arquivos ou exclua-os primeiro."
                );
            }

            // Validate target album belongs to same wedding
            if ($moveToAlbum->wedding_id !== $album->wedding_id) {
                throw new InvalidArgumentException(
                    'O álbum de destino deve pertencer ao mesmo casamento.'
                );
            }

            // Validate target album is not the same as source
            if ($moveToAlbum->id === $album->id) {
                throw new InvalidArgumentException(
                    'O álbum de destino não pode ser o mesmo álbum que está sendo excluído.'
                );
            }

            // Move all media to target album
            $album->media()->update(['album_id' => $moveToAlbum->id]);
        }

        // Clear cover_media_id if set (to avoid foreign key issues)
        if ($album->cover_media_id) {
            $album->update(['cover_media_id' => null]);
        }

        return $album->delete();
    }

    /**
     * Move a media file to a different album.
     * 
     * Updates the media's album association without duplicating the physical file.
     * The media's path and file remain unchanged; only the album_id is updated.
     *
     * @param SiteMedia $media The media file to move
     * @param Album $targetAlbum The album to move the media to
     * @return SiteMedia The updated media with new album association
     * 
     * @throws InvalidArgumentException If media or album belongs to different wedding
     * 
     * @see Property 6: Mover Mídia Preserva Arquivo Único
     * @see Requirements 2.5, 8.4
     */
    public function moveMedia(SiteMedia $media, Album $targetAlbum): SiteMedia
    {
        // Validate media and album belong to same wedding
        if ($media->wedding_id !== $targetAlbum->wedding_id) {
            throw new InvalidArgumentException(
                'A mídia e o álbum de destino devem pertencer ao mesmo casamento.'
            );
        }

        // Store original path for verification (Property 6)
        $originalPath = $media->path;

        // Update only the album_id - no file operations
        $media->update(['album_id' => $targetAlbum->id]);

        // Refresh the model to get updated relationships
        $media->refresh();

        // Verify path was not changed (Property 6 requirement)
        assert($media->path === $originalPath, 'Media path should not change when moving between albums');

        return $media;
    }

    /**
     * Get all albums for a wedding grouped by album type.
     * 
     * Returns a collection of albums organized by their album type.
     * The collection is keyed by album type slug (pre_casamento, pos_casamento, uso_site)
     * with each key containing a collection of albums of that type.
     *
     * @param Wedding $wedding The wedding to get albums for
     * @return Collection<string, Collection<int, Album>> Albums grouped by type slug
     * 
     * @see Property 5: Agrupamento de Álbuns por Tipo
     * @see Requirements 2.4
     */
    public function getAlbumsByType(Wedding $wedding): Collection
    {
        // Get all albums for the wedding with their album type
        $albums = Album::where('wedding_id', $wedding->id)
            ->with('albumType')
            ->get();

        // Group by album type slug
        $grouped = $albums->groupBy(function (Album $album) {
            return $album->albumType->slug;
        });

        // Ensure all album types are present in the result (even if empty)
        $allTypes = AlbumType::getSlugs();
        
        foreach ($allTypes as $typeSlug) {
            if (!$grouped->has($typeSlug)) {
                $grouped[$typeSlug] = collect();
            }
        }

        return $grouped;
    }
}
