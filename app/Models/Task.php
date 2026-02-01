<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends WeddingScopedModel
{
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'title',
        'description',
        'status',
        'due_date',
        'assigned_to',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
