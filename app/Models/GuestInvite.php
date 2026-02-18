<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GuestInvite extends Model
{
    use HasFactory, HasUuids;

    protected static ?bool $hasTokenHashColumn = null;

    protected $fillable = [
        'household_id',
        'guest_id',
        'created_by',
        'token',
        'token_hash',
        'channel',
        'status',
        'uses_count',
        'max_uses',
        'expires_at',
        'used_at',
        'revoked_at',
        'revoked_reason',
        'metadata',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'uses_count' => 'integer',
        'max_uses' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public static function generateToken(): string
    {
        return Str::random(48);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(?string $token): ?self
    {
        if (!$token) {
            return null;
        }

        $query = static::query();

        if (static::hasTokenHashColumn()) {
            $hash = static::hashToken($token);

            return $query
                ->where('token_hash', $hash)
                ->orWhere(function ($inner) use ($token) {
                    $inner->whereNull('token_hash')
                        ->where('token', $token);
                })
                ->first();
        }

        return $query->where('token', $token)->first();
    }

    public function setTokenAttribute(?string $value): void
    {
        $this->attributes['token'] = $value;

        if (static::hasTokenHashColumn()) {
            $this->attributes['token_hash'] = $value ? static::hashToken($value) : null;
        }
    }

    public function canBeUsed(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function markUsed(): void
    {
        $this->used_at = now();
        $this->uses_count = ($this->uses_count ?? 0) + 1;
        $this->status = 'opened';
        $this->save();
    }

    protected static function hasTokenHashColumn(): bool
    {
        if (static::$hasTokenHashColumn !== true) {
            static::$hasTokenHashColumn = Schema::hasColumn('guest_invites', 'token_hash');
        }

        return static::$hasTokenHashColumn;
    }

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
