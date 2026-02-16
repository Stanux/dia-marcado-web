<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use App\Models\Concerns\PlanningAuditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeddingPlan extends WeddingScopedModel
{
    use HasFactory;
    use SoftDeletes;
    use Blameable;
    use PlanningAuditable;

    protected $fillable = [
        'wedding_id',
        'title',
        'total_budget',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'archived_at' => 'datetime',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'wedding_plan_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
