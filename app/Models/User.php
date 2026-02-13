<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'current_wedding_id',
        'created_by',
        'onboarding_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_completed' => 'boolean',
        ];
    }

    /**
     * Get the weddings that the user belongs to.
     */
    public function weddings(): BelongsToMany
    {
        return $this->belongsToMany(Wedding::class, 'wedding_user')
            ->using(WeddingUser::class)
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    /**
     * Get the current wedding the user is working on.
     */
    public function currentWedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class, 'current_wedding_id');
    }

    /**
     * Get the user who created this user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get guest profiles associated with this user.
     */
    public function guestProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * Get the users created by this user.
     */
    public function createdUsers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user has completed the onboarding process.
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed === true;
    }

    /**
     * Mark the user as having completed the onboarding process.
     */
    public function markOnboardingComplete(): void
    {
        $this->update(['onboarding_completed' => true]);
    }

    /**
     * Check if the user is a couple member in a specific wedding.
     */
    public function isCoupleIn(Wedding $wedding): bool
    {
        $pivot = $this->weddings()
            ->where('wedding_id', $wedding->id)
            ->first()
            ?->pivot;

        return $pivot?->role === 'couple';
    }

    /**
     * Check if the user is an organizer in a specific wedding.
     */
    public function isOrganizerIn(Wedding $wedding): bool
    {
        $pivot = $this->weddings()
            ->where('wedding_id', $wedding->id)
            ->first()
            ?->pivot;

        return $pivot?->role === 'organizer';
    }

    /**
     * Check if the user is a guest in a specific wedding.
     */
    public function isGuestIn(Wedding $wedding): bool
    {
        $pivot = $this->weddings()
            ->where('wedding_id', $wedding->id)
            ->first()
            ?->pivot;

        return $pivot?->role === 'guest';
    }

    /**
     * Check if the user has access to a specific wedding.
     */
    public function hasAccessTo(Wedding $wedding): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->weddings()
            ->where('wedding_id', $wedding->id)
            ->exists();
    }

    /**
     * Get the user's role in a specific wedding.
     */
    public function roleIn(Wedding $wedding): ?string
    {
        if ($this->isAdmin()) {
            return 'admin';
        }

        $pivot = $this->weddings()
            ->where('wedding_id', $wedding->id)
            ->first()
            ?->pivot;

        return $pivot?->role;
    }

    /**
     * Get the user's permissions in a specific wedding.
     */
    public function permissionsIn(Wedding $wedding): array
    {
        $pivot = $this->weddings()
            ->where('wedding_id', $wedding->id)
            ->first()
            ?->pivot;

        $permissions = $pivot?->permissions ?? [];

        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($permissions) ? $permissions : [];
    }

    /**
     * Check if the user has a specific permission in a wedding.
     */
    public function hasPermissionIn(Wedding $wedding, string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isCoupleIn($wedding)) {
            return true;
        }

        $permissions = $this->permissionsIn($wedding);
        return in_array($module, $permissions);
    }
}
