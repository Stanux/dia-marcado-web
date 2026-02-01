<?php

namespace App\Models;

use App\Models\Scopes\WeddingScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Base model for all entities that belong to a wedding.
 * 
 * Automatically applies WeddingScope to filter queries by wedding_id
 * and injects wedding_id when creating new records.
 */
abstract class WeddingScopedModel extends Model
{
    use HasUuids;

    /**
     * Boot the model and register global scope and event listeners.
     */
    protected static function booted(): void
    {
        // Apply global scope to filter by wedding_id
        static::addGlobalScope(new WeddingScope());

        // Auto-inject wedding_id when creating records
        static::creating(function (Model $model) {
            if (!$model->wedding_id && auth()->check()) {
                $user = auth()->user();
                
                // Don't auto-inject for admin users unless they have a current wedding
                if ($user->current_wedding_id) {
                    $model->wedding_id = $user->current_wedding_id;
                }
            }
        });
    }

    /**
     * Get the wedding that owns this model.
     */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /**
     * Scope to filter by specific wedding.
     */
    public function scopeForWedding($query, string $weddingId)
    {
        return $query->where('wedding_id', $weddingId);
    }
}
