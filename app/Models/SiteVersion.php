<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SiteVersion Model
 * 
 * Represents a historical snapshot of a site layout's content.
 */
class SiteVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'site_layout_id',
        'user_id',
        'content',
        'summary',
        'is_published',
    ];

    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
    ];

    /**
     * Get the site layout this version belongs to.
     */
    public function siteLayout(): BelongsTo
    {
        return $this->belongsTo(SiteLayout::class);
    }

    /**
     * Get the user who created this version.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
