<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Album Model
 * 
 * Represents a collection of media files grouped by theme or event.
 */
class Album extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wedding_id',
        'album_type_id',
        'name',
        'description',
        'cover_media_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'album_type_id' => 'integer',
        ];
    }

    /**
     * Get the wedding this album belongs to.
     */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /**
     * Get the album type.
     */
    public function albumType(): BelongsTo
    {
        return $this->belongsTo(AlbumType::class);
    }

    /**
     * Get the media files in this album.
     */
    public function media(): HasMany
    {
        return $this->hasMany(SiteMedia::class);
    }

    /**
     * Get the cover media for this album.
     */
    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(SiteMedia::class, 'cover_media_id');
    }
}
