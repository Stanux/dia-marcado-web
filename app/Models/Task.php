<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\PlanningAuditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Task extends WeddingScopedModel
{
    use HasFactory;
    use SoftDeletes;
    use Blameable;
    use PlanningAuditable;

    protected $fillable = [
        'wedding_id',
        'wedding_plan_id',
        'title',
        'description',
        'task_category_id',
        'status',
        'start_date',
        'due_date',
        'priority',
        'estimated_value',
        'actual_value',
        'executed_at',
        'assigned_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'executed_at' => 'date',
        'estimated_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(WeddingPlan::class, 'wedding_plan_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(TaskBudget::class);
    }

    public function valueHistories(): HasMany
    {
        return $this->hasMany(TaskValueHistory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        if (in_array($this->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        $timezone = $this->getWeddingTimezone();
        $dueDate = Carbon::createFromFormat('Y-m-d', $this->due_date->format('Y-m-d'), $timezone);

        return $dueDate->lt(now($timezone)->startOfDay());
    }

    public function getEffectiveStatusAttribute(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }

    public function getWeddingTimezone(): string
    {
        $wedding = $this->relationLoaded('wedding') ? $this->wedding : $this->wedding()->first();

        return $wedding?->getSetting('timezone', config('app.timezone')) ?? config('app.timezone');
    }
}
