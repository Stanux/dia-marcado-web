<?php

declare(strict_types=1);

namespace App\Contracts\Media;

use App\Models\Album;
use App\Models\SiteMedia;
use App\Models\Wedding;
use Illuminate\Support\Collection;

/**
 * Interface for album management service.
 * 
 * Provides methods to create, update, delete, and organize albums,
 * as well as move media between albums. Albums are categorized by type
 * (pre-wedding, post-wedding, site usage) and belong to a specific wedding.
 * 
 * @see Requirements 2.2 - Album creation requires album type selection
 * @see Requirements 2.4 - Albums grouped by type in listing
 * @see Requirements 2.5 - Moving media between albums preserves history
 */
interface AlbumManagementServiceInterface
{
    /**
     * Create a new album for a wedding.
     * 
     * Creates an album with the specified type and data. The album type
     * must be one of the valid types: pre_casamento, pos_casamento, uso_site.
     * 
     * @param Wedding $wedding The wedding to create the album for
     * @param string $typeSlug The album type slug (pre_casamento, pos_casamento, uso_site)
     * @param array{name: string, description?: string, cover_media_id?: string} $data Album data including name and optional description/cover
     * @return Album The newly created album
     * 
     * @throws \InvalidArgumentException If the type slug is invalid
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * 
     * @see Requirements 2.2
     */
    public function createAlbum(Wedding $wedding, string $typeSlug, array $data): Album;

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
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function updateAlbum(Album $album, array $data): Album;

    /**
     * Delete an album.
     * 
     * Deletes the album. If the album contains media files, they can optionally
     * be moved to another album. If no target album is specified and the album
     * contains media, the operation should prompt for confirmation or fail.
     * 
     * @param Album $album The album to delete
     * @param Album|null $moveToAlbum Optional album to move media to before deletion
     * @return bool True if deletion was successful
     * 
     * @throws \RuntimeException If album has media and no target album specified
     * 
     * @see Requirements 2.6
     */
    public function deleteAlbum(Album $album, ?Album $moveToAlbum = null): bool;

    /**
     * Move a media file to a different album.
     * 
     * Updates the media's album association without duplicating the physical file.
     * The media's path and file remain unchanged; only the album_id is updated.
     * This operation maintains the history of the move.
     * 
     * @param SiteMedia $media The media file to move
     * @param Album $targetAlbum The album to move the media to
     * @return SiteMedia The updated media with new album association
     * 
     * @throws \InvalidArgumentException If media or album belongs to different wedding
     * 
     * @see Requirements 2.5, 8.4
     */
    public function moveMedia(SiteMedia $media, Album $targetAlbum): SiteMedia;

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
     * @see Requirements 2.4
     */
    public function getAlbumsByType(Wedding $wedding): Collection;
}
