<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestMessageLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'message_id',
        'guest_id',
        'status',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(GuestMessage::class, 'message_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
