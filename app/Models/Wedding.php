<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wedding extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'wedding_date',
        'venue',
        'city',
        'state',
        'settings',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wedding_date' => 'date',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the users that belong to this wedding.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wedding_user')
            ->using(WeddingUser::class)
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    /**
     * Get the couple members of this wedding.
     */
    public function couple(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'couple');
    }

    /**
     * Get the organizers of this wedding.
     */
    public function organizers(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'organizer');
    }

    /**
     * Get the guests of this wedding.
     */
    public function guests(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'guest');
    }

    /**
     * Get the tasks for this wedding.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the site layout for this wedding.
     */
    public function siteLayout(): HasOne
    {
        return $this->hasOne(SiteLayout::class);
    }

    /**
     * Check if a user is part of this wedding.
     */
    public function hasUser(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(string $key, mixed $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        
        return $this;
    }

    /**
     * Scope to filter only active weddings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter weddings by date range.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('wedding_date', '>=', now()->toDateString());
    }
}
