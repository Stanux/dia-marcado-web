<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestEvent extends WeddingScopedModel
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'created_by',
        'name',
        'slug',
        'event_at',
        'is_active',
        'rules',
        'questions',
        'metadata',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'is_active' => 'boolean',
        'rules' => 'array',
        'questions' => 'array',
        'metadata' => 'array',
    ];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(GuestRsvp::class, 'event_id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(GuestCheckin::class, 'event_id');
    }
}
