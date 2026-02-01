<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SiteLayout Model
 * 
 * Represents a wedding site with draft and published content.
 * Extends WeddingScopedModel for automatic wedding isolation.
 */
class SiteLayout extends WeddingScopedModel
{
    use HasFactory;
    protected $fillable = [
        'wedding_id',
        'draft_content',
        'published_content',
        'slug',
        'custom_domain',
        'access_token',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'draft_content' => 'array',
        'published_content' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Resolve the route binding without the global scope.
     * This allows the policy to handle authorization instead of the scope.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $user = auth()->user();
        
        // If no user or admin, use default behavior without scope
        if (!$user || $user->isAdmin()) {
            return $this->withoutGlobalScopes()
                ->where($field ?? $this->getRouteKeyName(), $value)
                ->first();
        }
        
        // For regular users, find the site and verify access
        $site = $this->withoutGlobalScopes()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
            
        if (!$site) {
            return null;
        }
        
        // Verify user has access to the site's wedding
        $hasAccess = $user->weddings()
            ->where('wedding_id', $site->wedding_id)
            ->exists();
            
        return $hasAccess ? $site : null;
    }

    /**
     * Get all versions for this site layout, ordered by most recent first.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(SiteVersion::class)->orderByDesc('created_at');
    }

    /**
     * Get all media files for this site layout.
     */
    public function media(): HasMany
    {
        return $this->hasMany(SiteMedia::class);
    }

    /**
     * Check if the site has unpublished changes (draft differs from published).
     */
    public function isDraft(): bool
    {
        if ($this->published_content === null) {
            return true;
        }

        return $this->draft_content !== $this->published_content;
    }

    /**
     * Get the public URL for this site based on its slug.
     */
    public function getPublicUrl(): string
    {
        $baseUrl = config('app.url');
        return rtrim($baseUrl, '/') . '/' . $this->slug;
    }
}
