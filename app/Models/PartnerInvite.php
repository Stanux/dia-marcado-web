<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * PartnerInvite Model
 * 
 * Represents an invitation sent to a partner to join a wedding.
 */
class PartnerInvite extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wedding_id',
        'inviter_id',
        'email',
        'name',
        'token',
        'status',
        'existing_user_id',
        'previous_wedding_id',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PartnerInvite $invite) {
            if (empty($invite->token)) {
                $invite->token = Str::random(64);
            }
            if (empty($invite->expires_at)) {
                $invite->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Get the wedding this invite belongs to.
     */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /**
     * Get the user who sent this invite.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * Get the existing user if the email already exists in the platform.
     */
    public function existingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'existing_user_id');
    }

    /**
     * Get the previous wedding the existing user was associated with.
     */
    public function previousWedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class, 'previous_wedding_id');
    }

    /**
     * Scope to filter pending invites.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter expired invites.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope to filter valid (non-expired pending) invites.
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>=', now());
    }

    /**
     * Check if the invite is for a new user (email doesn't exist in platform).
     */
    public function isForNewUser(): bool
    {
        return $this->existing_user_id === null;
    }

    /**
     * Check if the invite is for an existing user.
     */
    public function isForExistingUser(): bool
    {
        return $this->existing_user_id !== null;
    }

    /**
     * Check if the invite has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the invite is still valid (pending and not expired).
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Mark the invite as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * Mark the invite as declined.
     */
    public function markAsDeclined(): void
    {
        $this->update(['status' => 'declined']);
    }

    /**
     * Mark the invite as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }
}
