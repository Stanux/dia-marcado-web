<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GuestRsvp extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_MAYBE = 'maybe';
    public const STATUS_NO_RESPONSE = 'no_response';

    public const STATUSES = [
        self::STATUS_CONFIRMED,
        self::STATUS_DECLINED,
        self::STATUS_MAYBE,
        self::STATUS_NO_RESPONSE,
    ];

    public const STATUS_ALIASES = [
        'confirmado' => self::STATUS_CONFIRMED,
        'recusado' => self::STATUS_DECLINED,
        'talvez' => self::STATUS_MAYBE,
        'pendente' => self::STATUS_NO_RESPONSE,
        'sem_resposta' => self::STATUS_NO_RESPONSE,
        'sem resposta' => self::STATUS_NO_RESPONSE,
    ];

    protected $fillable = [
        'guest_id',
        'event_id',
        'updated_by',
        'status',
        'responses',
        'responded_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'responded_at' => 'datetime',
    ];

    public static function validationStatuses(): array
    {
        return array_values(array_unique(array_merge(
            self::STATUSES,
            array_keys(self::STATUS_ALIASES),
        )));
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

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(GuestEvent::class, 'event_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
