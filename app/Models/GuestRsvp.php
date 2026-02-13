<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestRsvp extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'guest_id',
        'event_id',
        'updated_by',
        'status',
        'responses',
        'responded_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'responded_at' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(GuestEvent::class, 'event_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
