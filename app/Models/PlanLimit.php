<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * PlanLimit Model
 * 
 * Represents the storage and file limits for a subscription plan.
 */
class PlanLimit extends Model
{
    /**
     * Plan slug constants
     */
    const PLAN_BASIC = 'basic';
    const PLAN_PREMIUM = 'premium';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plan_slug',
        'max_files',
        'max_storage_bytes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_files' => 'integer',
            'max_storage_bytes' => 'integer',
        ];
    }

    /**
     * Get the plan limit by slug.
     *
     * @param string $slug
     * @return PlanLimit|null
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('plan_slug', $slug)->first();
    }

    /**
     * Template categories enabled for this plan.
     */
    public function templateCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            TemplateCategory::class,
            'plan_template_category',
            'plan_slug',
            'template_category_id',
            'plan_slug',
            'id'
        )->withTimestamps();
    }
}
