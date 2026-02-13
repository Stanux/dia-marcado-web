<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestCheckin extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'guest_id',
        'event_id',
        'operator_id',
        'method',
        'device_id',
        'checked_in_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(GuestEvent::class, 'event_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
