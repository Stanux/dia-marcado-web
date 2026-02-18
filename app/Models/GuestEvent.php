<?php

namespace App\Models;

use App\Services\Guests\GuestAuditLogService;
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

    protected static function booted(): void
    {
        parent::booted();

        static::created(function (self $event): void {
            $event->recordAudit(
                action: 'guest.event.created',
                context: [
                    'event_id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'is_active' => (bool) $event->is_active,
                    'questions_count' => $event->questionsCount(),
                ],
            );
        });

        static::updated(function (self $event): void {
            $changedFields = collect($event->getChanges())
                ->keys()
                ->reject(fn (string $field): bool => in_array($field, ['updated_at'], true))
                ->values();

            if ($changedFields->isEmpty()) {
                return;
            }

            $context = [
                'event_id' => $event->id,
                'changed_fields' => $changedFields->all(),
                'is_active' => (bool) $event->is_active,
            ];

            if ($event->wasChanged('questions')) {
                $context['questions_before_count'] = $event->questionsCountFrom($event->getOriginal('questions'));
                $context['questions_after_count'] = $event->questionsCount();
            }

            $event->recordAudit(
                action: 'guest.event.updated',
                context: $context,
            );
        });

        static::deleted(function (self $event): void {
            $event->recordAudit(
                action: 'guest.event.deleted',
                context: [
                    'event_id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                ],
            );
        });
    }

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

    private function recordAudit(string $action, array $context): void
    {
        if (!$this->wedding_id) {
            return;
        }

        try {
            app(GuestAuditLogService::class)->record(
                weddingId: (string) $this->wedding_id,
                action: $action,
                context: $context,
                actorId: auth()->id(),
            );
        } catch (\Throwable $exception) {
            // Keep event CRUD resilient if audit logging fails.
        }
    }

    private function questionsCount(): int
    {
        return $this->questionsCountFrom($this->questions);
    }

    private function questionsCountFrom(mixed $value): int
    {
        if (is_array($value)) {
            return count($value);
        }

        if (!is_string($value) || trim($value) === '') {
            return 0;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? count($decoded) : 0;
    }
}
