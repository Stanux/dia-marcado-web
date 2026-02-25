<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        'template_category_id',
        'editor_site_layout_id',
        'name',
        'slug',
        'description',
        'thumbnail',
        'content',
        'is_public',
    ];

    protected $casts = [
        'content' => 'array',
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (SiteTemplate $template) {
            if (!is_string($template->slug) || trim($template->slug) === '') {
                $template->slug = static::generateUniqueSlug((string) $template->name);
            } else {
                $template->slug = static::generateUniqueSlug((string) $template->slug);
            }
        });

        static::updating(function (SiteTemplate $template) {
            if (!$template->isDirty('slug') && !$template->isDirty('name')) {
                return;
            }

            $source = is_string($template->slug) && trim($template->slug) !== ''
                ? $template->slug
                : $template->name;

            $template->slug = static::generateUniqueSlug((string) $source, $template->id);
        });
    }

    /**
     * Get the wedding this template belongs to (null for system templates).
     */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /**
     * Get the category this template belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id');
    }

    /**
     * Get the workspace editor site associated with this template.
     */
    public function editorSite(): BelongsTo
    {
        return $this->belongsTo(SiteLayout::class, 'editor_site_layout_id');
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

    /**
     * Generate a unique slug for templates.
     */
    public static function generateUniqueSlug(string $value, ?string $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'template';
        }

        $slug = $base;
        $counter = 1;

        while (static::query()
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $counter++;
            $slug = $base . '-' . $counter;
        }

        return $slug;
    }
}
