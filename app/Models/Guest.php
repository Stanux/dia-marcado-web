<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends WeddingScopedModel
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'household_id',
        'user_id',
        'name',
        'email',
        'phone',
        'nickname',
        'role_in_household',
        'is_child',
        'category',
        'side',
        'status',
        'tags',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'is_child' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(GuestHousehold::class, 'household_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(GuestRsvp::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(GuestCheckin::class);
    }
}
