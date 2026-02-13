<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestAuditLog extends WeddingScopedModel
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'actor_id',
        'action',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
