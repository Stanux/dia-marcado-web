<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TemplateCategory Model
 *
 * Represents a grouping bucket used to control template access by plan.
 */
class TemplateCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get all templates assigned to this category.
     */
    public function templates(): HasMany
    {
        return $this->hasMany(SiteTemplate::class, 'template_category_id');
    }

    /**
     * Get plans allowed to use this category.
     */
    public function allowedPlans(): BelongsToMany
    {
        return $this->belongsToMany(
            PlanLimit::class,
            'plan_template_category',
            'template_category_id',
            'plan_slug',
            'id',
            'plan_slug'
        )->withTimestamps();
    }
}
