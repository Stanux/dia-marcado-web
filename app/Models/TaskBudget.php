<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use App\Models\Concerns\PlanningAuditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskBudget extends WeddingScopedModel
{
    use HasFactory;
    use SoftDeletes;
    use Blameable;
    use PlanningAuditable;

    protected $fillable = [
        'wedding_id',
        'task_id',
        'wedding_vendor_id',
        'status',
        'value',
        'valid_until',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'valid_until' => 'date',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function weddingVendor(): BelongsTo
    {
        return $this->belongsTo(WeddingVendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
