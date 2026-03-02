<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingGuest extends WeddingScopedModel
{
    protected $table = 'wedding_guests';

    protected $fillable = [
        'wedding_id',
        'created_by',
        'primary_contact_id',
        'name',
        'nickname',
        'email',
        'phone',
        'relationship',
        'is_child',
        'side',
        'status',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_child' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(self::class, 'primary_contact_id');
    }

    public function linkedGuests(): HasMany
    {
        return $this->hasMany(self::class, 'primary_contact_id');
    }

    public function primaryInvites(): HasMany
    {
        return $this->hasMany(WeddingInvite::class, 'primary_contact_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(WeddingEventRsvp::class, 'guest_id');
    }

    public function scopePrimaryContacts(Builder $query): Builder
    {
        return $query->whereNull('primary_contact_id');
    }
}
