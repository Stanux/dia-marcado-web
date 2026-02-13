<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestInvite extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'household_id',
        'guest_id',
        'created_by',
        'token',
        'channel',
        'status',
        'expires_at',
        'used_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(GuestHousehold::class, 'household_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
