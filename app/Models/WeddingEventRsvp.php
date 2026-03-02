<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingEventRsvp extends WeddingScopedModel
{
    protected $table = 'wedding_event_rsvps';

    protected $fillable = [
        'wedding_id',
        'event_id',
        'guest_id',
        'invite_id',
        'status',
        'responded_at',
        'response_channel',
        'notes',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(WeddingEvent::class, 'event_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(WeddingGuest::class, 'guest_id');
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(WeddingInvite::class, 'invite_id');
    }
}
