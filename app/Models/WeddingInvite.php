<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingInvite extends WeddingScopedModel
{
    protected $table = 'wedding_invites';

    protected $fillable = [
        'wedding_id',
        'event_id',
        'created_by',
        'primary_contact_id',
        'invite_type',
        'token',
        'confirmation_code',
        'adult_quota',
        'child_quota',
        'expires_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'adult_quota' => 'integer',
        'child_quota' => 'integer',
        'metadata' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(WeddingEvent::class, 'event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(WeddingGuest::class, 'primary_contact_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(WeddingEventRsvp::class, 'invite_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
