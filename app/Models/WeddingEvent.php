<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingEvent extends WeddingScopedModel
{
    protected $table = 'wedding_events';

    protected $fillable = [
        'wedding_id',
        'created_by',
        'name',
        'event_date',
        'event_time',
        'event_type',
        'is_active',
        'instructions',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(WeddingInvite::class, 'event_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(WeddingEventRsvp::class, 'event_id');
    }

    public function isClosed(): bool
    {
        return $this->event_type === 'closed';
    }
}
