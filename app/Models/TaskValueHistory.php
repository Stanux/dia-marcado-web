<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskValueHistory extends WeddingScopedModel
{
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'task_id',
        'changed_by',
        'estimated_value',
        'actual_value',
        'source',
        'meta',
        'changed_at',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'changed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
