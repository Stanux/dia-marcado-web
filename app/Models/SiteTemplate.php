<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SiteTemplate Model
 * 
 * Represents a pre-defined or custom template for site layouts.
 * System templates have wedding_id = null.
 */
class SiteTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'wedding_id',
        'name',
        'description',
        'thumbnail',
        'content',
        'is_public',
    ];

    protected $casts = [
        'content' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the wedding this template belongs to (null for system templates).
     */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /**
     * Scope to filter only public templates.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter templates available for a specific wedding.
     * Returns public templates and templates owned by the wedding.
     */
    public function scopeForWedding(Builder $query, string $weddingId): Builder
    {
        return $query->where(function (Builder $q) use ($weddingId) {
            $q->where('wedding_id', $weddingId)
              ->orWhere('is_public', true);
        });
    }
}
