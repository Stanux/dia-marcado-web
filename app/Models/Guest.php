<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Guest extends WeddingScopedModel
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_MAYBE = 'maybe';

    public const OVERALL_RSVP_STATUS_NO_RESPONSE = 'no_response';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_DECLINED,
        self::STATUS_MAYBE,
    ];

    public const OVERALL_RSVP_STATUSES = [
        self::OVERALL_RSVP_STATUS_NO_RESPONSE,
        self::STATUS_CONFIRMED,
        self::STATUS_DECLINED,
        self::STATUS_MAYBE,
    ];

    public const STATUS_ALIASES = [
        'pendente' => self::STATUS_PENDING,
        'confirmed' => self::STATUS_CONFIRMED,
        'confirmado' => self::STATUS_CONFIRMED,
        'declined' => self::STATUS_DECLINED,
        'recusado' => self::STATUS_DECLINED,
        'maybe' => self::STATUS_MAYBE,
        'talvez' => self::STATUS_MAYBE,
        'no_response' => self::STATUS_PENDING,
        'sem_resposta' => self::STATUS_PENDING,
        'sem resposta' => self::STATUS_PENDING,
    ];

    public const OVERALL_STATUS_ALIASES = [
        'pending' => self::OVERALL_RSVP_STATUS_NO_RESPONSE,
        'pendente' => self::OVERALL_RSVP_STATUS_NO_RESPONSE,
        'no_response' => self::OVERALL_RSVP_STATUS_NO_RESPONSE,
        'sem_resposta' => self::OVERALL_RSVP_STATUS_NO_RESPONSE,
        'sem resposta' => self::OVERALL_RSVP_STATUS_NO_RESPONSE,
        'confirmed' => self::STATUS_CONFIRMED,
        'confirmado' => self::STATUS_CONFIRMED,
        'declined' => self::STATUS_DECLINED,
        'recusado' => self::STATUS_DECLINED,
        'maybe' => self::STATUS_MAYBE,
        'talvez' => self::STATUS_MAYBE,
    ];

    protected static ?bool $hasNormalizedEmailColumn = null;
    protected static ?bool $hasNormalizedPhoneColumn = null;
    protected static ?bool $hasOverallRsvpStatusColumn = null;

    protected $fillable = [
        'wedding_id',
        'household_id',
        'user_id',
        'name',
        'email',
        'normalized_email',
        'phone',
        'normalized_phone',
        'nickname',
        'role_in_household',
        'is_child',
        'category',
        'side',
        'status',
        'overall_rsvp_status',
        'tags',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'is_child' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $guest): void {
            if (static::hasNormalizedEmailColumn()) {
                $guest->normalized_email = static::normalizeEmail($guest->email);
            }

            if (static::hasNormalizedPhoneColumn()) {
                $guest->normalized_phone = static::normalizePhone($guest->phone);
            }

            $guest->status = static::normalizeStatus($guest->status) ?? self::STATUS_PENDING;

            if (static::hasOverallRsvpStatusColumn()) {
                $guest->overall_rsvp_status = static::normalizeOverallRsvpStatus($guest->overall_rsvp_status)
                    ?? self::OVERALL_RSVP_STATUS_NO_RESPONSE;
            }
        });
    }

    public static function validationStatuses(): array
    {
        return array_values(array_unique(array_merge(
            self::STATUSES,
            array_keys(self::STATUS_ALIASES),
        )));
    }

    public static function normalizeEmail(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = Str::lower(trim($value));

        return $normalized !== '' ? $normalized : null;
    }

    public static function normalizePhone(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits ?: null;
    }

    public static function normalizeStatus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = Str::lower(trim($value));

        if (isset(self::STATUS_ALIASES[$normalized])) {
            return self::STATUS_ALIASES[$normalized];
        }

        return in_array($normalized, self::STATUSES, true) ? $normalized : null;
    }

    public static function normalizeOverallRsvpStatus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = Str::lower(trim($value));

        if (isset(self::OVERALL_STATUS_ALIASES[$normalized])) {
            return self::OVERALL_STATUS_ALIASES[$normalized];
        }

        return in_array($normalized, self::OVERALL_RSVP_STATUSES, true) ? $normalized : null;
    }

    public static function calculateOverallRsvpStatus(array $statuses): string
    {
        $normalized = collect($statuses)
            ->map(fn ($status) => GuestRsvp::normalizeStatus($status))
            ->filter()
            ->values();

        if ($normalized->contains(GuestRsvp::STATUS_CONFIRMED)) {
            return self::STATUS_CONFIRMED;
        }

        if ($normalized->contains(GuestRsvp::STATUS_MAYBE)) {
            return self::STATUS_MAYBE;
        }

        if ($normalized->contains(GuestRsvp::STATUS_DECLINED)) {
            return self::STATUS_DECLINED;
        }

        return self::OVERALL_RSVP_STATUS_NO_RESPONSE;
    }

    public function refreshOverallRsvpStatus(bool $save = true): string
    {
        $overall = self::calculateOverallRsvpStatus(
            $this->rsvps()->pluck('status')->all(),
        );

        if (self::hasOverallRsvpStatusColumn()) {
            $this->overall_rsvp_status = $overall;
        }

        // Keep legacy guest.status coherent with the aggregate RSVP state.
        $this->status = $overall === self::OVERALL_RSVP_STATUS_NO_RESPONSE
            ? self::STATUS_PENDING
            : $overall;

        if ($save) {
            $this->saveQuietly();
        }

        return $overall;
    }

    protected static function hasNormalizedEmailColumn(): bool
    {
        if (static::$hasNormalizedEmailColumn !== true) {
            static::$hasNormalizedEmailColumn = Schema::hasColumn('guests', 'normalized_email');
        }

        return static::$hasNormalizedEmailColumn;
    }

    protected static function hasNormalizedPhoneColumn(): bool
    {
        if (static::$hasNormalizedPhoneColumn !== true) {
            static::$hasNormalizedPhoneColumn = Schema::hasColumn('guests', 'normalized_phone');
        }

        return static::$hasNormalizedPhoneColumn;
    }

    protected static function hasOverallRsvpStatusColumn(): bool
    {
        if (static::$hasOverallRsvpStatusColumn !== true) {
            static::$hasOverallRsvpStatusColumn = Schema::hasColumn('guests', 'overall_rsvp_status');
        }

        return static::$hasOverallRsvpStatusColumn;
    }

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
