<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * SiteMedia Model
 * 
 * Represents a media file (image/video) uploaded for a site.
 * Extends WeddingScopedModel for automatic wedding isolation.
 */
class SiteMedia extends WeddingScopedModel
{

    /**
     * Media status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'site_layout_id',
        'wedding_id',
        'original_name',
        'path',
        'disk',
        'size',
        'mime_type',
        'variants',
        'album_id',
        'status',
        'batch_id',
        'error_message',
        'width',
        'height',
    ];

    protected $casts = [
        'variants' => 'array',
        'size' => 'integer',
    ];

    /**
     * Get the site layout this media belongs to.
     */
    public function siteLayout(): BelongsTo
    {
        return $this->belongsTo(SiteLayout::class);
    }

    /**
     * Get the wedding this media belongs to.
     */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /**
     * Get the album this media belongs to.
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Get the upload batch this media belongs to.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }

    /**
     * Get the public URL for this media file.
     */
    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the URL for a specific variant of this media.
     *
     * @param string $variant The variant name (e.g., 'webp', '2x', 'thumbnail')
     * @return string|null The variant URL or null if not found
     */
    public function getVariantUrl(string $variant): ?string
    {
        $variants = $this->variants ?? [];
        
        if (!isset($variants[$variant])) {
            return null;
        }

        return Storage::disk($this->disk)->url($variants[$variant]);
    }
}
