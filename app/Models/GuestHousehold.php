<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestHousehold extends WeddingScopedModel
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'created_by',
        'name',
        'code',
        'side',
        'category',
        'priority',
        'quota_adults',
        'quota_children',
        'plus_one_allowed',
        'tags',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'plus_one_allowed' => 'boolean',
        'tags' => 'array',
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

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'household_id');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(GuestInvite::class, 'household_id');
    }
}
