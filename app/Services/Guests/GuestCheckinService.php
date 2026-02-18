<?php

namespace App\Services\Guests;

use App\Models\Guest;
use App\Models\GuestAuditLog;
use App\Models\GuestCheckin;
use App\Models\GuestEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GuestCheckinService
{
    public function __construct(
        private readonly GuestAuditLogService $guestAuditLogService,
    ) {}

    /**
     * @return array{
     *   created:bool,
     *   duplicate:bool,
     *   checkin:GuestCheckin,
     *   guest:Guest,
     *   event:GuestEvent|null
     * }
     */
    public function record(
        string $weddingId,
        string $guestId,
        ?string $eventId = null,
        string $method = 'qr',
        ?string $deviceId = null,
        ?string $notes = null,
        ?string $operatorId = null,
    ): array {
        $method = strtolower(trim($method));
        if (!in_array($method, ['qr', 'manual'], true)) {
            throw new \InvalidArgumentException('Metodo de check-in invalido.');
        }

        return DB::transaction(function () use ($weddingId, $guestId, $eventId, $method, $deviceId, $notes, $operatorId): array {
            $guest = Guest::withoutGlobalScopes()
                ->whereKey($guestId)
                ->lockForUpdate()
                ->first();

            if (!$guest) {
                throw new \InvalidArgumentException('Convidado nao encontrado.');
            }

            if ((string) $guest->wedding_id !== (string) $weddingId) {
                throw new \InvalidArgumentException('Convidado invalido para este casamento.');
            }

            $event = $this->resolveEventForWedding($eventId, $weddingId, mustBeActive: true);

            $existing = GuestCheckin::query()
                ->where('guest_id', $guest->id)
                ->when(
                    $event,
                    fn (Builder $query): Builder => $query->where('event_id', $event->id),
                    fn (Builder $query): Builder => $query->whereNull('event_id'),
                )
                ->orderByDesc('checked_in_at')
                ->first();

            if ($existing) {
                $this->recordAudit(
                    weddingId: $weddingId,
                    action: 'guest.checkin.duplicate_ignored',
                    context: [
                        'checkin_id' => $existing->id,
                        'guest_id' => $guest->id,
                        'event_id' => $event?->id,
                        'method' => $method,
                    ],
                    actorId: $operatorId,
                );

                return [
                    'created' => false,
                    'duplicate' => true,
                    'checkin' => $existing->loadMissing(['event:id,name', 'operator:id,name']),
                    'guest' => $guest,
                    'event' => $event,
                ];
            }

            $checkin = GuestCheckin::create([
                'guest_id' => $guest->id,
                'event_id' => $event?->id,
                'operator_id' => $operatorId,
                'method' => $method,
                'device_id' => $deviceId,
                'notes' => $notes,
                'checked_in_at' => now(),
            ]);

            $this->recordAudit(
                weddingId: $weddingId,
                action: 'guest.checkin.recorded',
                context: [
                    'checkin_id' => $checkin->id,
                    'guest_id' => $guest->id,
                    'event_id' => $event?->id,
                    'method' => $method,
                    'device_id' => $deviceId,
                ],
                actorId: $operatorId,
            );

            return [
                'created' => true,
                'duplicate' => false,
                'checkin' => $checkin->loadMissing(['event:id,name', 'operator:id,name']),
                'guest' => $guest,
                'event' => $event,
            ];
        });
    }

    /**
     * @param  array{
     *   event_id?:string,
     *   method?:string,
     *   search?:string,
     *   limit?:int|string
     * }  $filters
     * @return array{
     *   items:Collection<int, array<string, mixed>>,
     *   summary:array<string, mixed>
     * }
     */
    public function listForWedding(string $weddingId, array $filters = []): array
    {
        $query = $this->baseQuery($weddingId);

        if (!empty($filters['event_id'])) {
            $event = $this->resolveEventForWedding((string) $filters['event_id'], $weddingId);
            $query->where('event_id', $event?->id);
        }

        if (!empty($filters['method'])) {
            $method = strtolower(trim((string) $filters['method']));
            if (!in_array($method, ['qr', 'manual'], true)) {
                throw new \InvalidArgumentException('Metodo de check-in invalido.');
            }

            $query->where('method', $method);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $searchLike = '%' . mb_strtolower($search) . '%';

            $query->whereHas('guest', function (Builder $guestQuery) use ($searchLike): void {
                $guestQuery->withoutGlobalScopes()
                    ->where(function (Builder $innerQuery) use ($searchLike): void {
                        $innerQuery
                            ->whereRaw('LOWER(name) LIKE ?', [$searchLike])
                            ->orWhereRaw("LOWER(COALESCE(email, '')) LIKE ?", [$searchLike])
                            ->orWhereRaw("LOWER(COALESCE(phone, '')) LIKE ?", [$searchLike]);
                    });
            });
        }

        $limit = max(1, min((int) ($filters['limit'] ?? 50), 200));

        $totalCheckins = (int) (clone $query)->count();
        $uniqueGuests = (int) (clone $query)->distinct()->count('guest_id');
        $checkinsToday = (int) $this->baseQuery($weddingId)
            ->where('checked_in_at', '>=', now()->startOfDay())
            ->count();

        $duplicatesIgnored24h = (int) GuestAuditLog::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->where('action', 'guest.checkin.duplicate_ignored')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $byMethod = (clone $query)
            ->selectRaw('method, COUNT(*) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'method' => $row->method ?: 'manual',
                'label' => $this->methodLabel($row->method ?: 'manual'),
                'total' => (int) $row->total,
            ])
            ->values();

        $byEventRows = (clone $query)
            ->selectRaw('event_id, COUNT(*) as total')
            ->groupBy('event_id')
            ->orderByDesc('total')
            ->get();

        $eventIds = $byEventRows->pluck('event_id')->filter()->values();
        $eventNames = GuestEvent::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->whereIn('id', $eventIds)
            ->pluck('name', 'id');

        $byEvent = $byEventRows
            ->map(fn ($row): array => [
                'event_id' => $row->event_id,
                'event_name' => $row->event_id ? ($eventNames[$row->event_id] ?? 'Evento removido') : 'Sem evento',
                'total' => (int) $row->total,
            ])
            ->values();

        $items = (clone $query)
            ->with([
                'guest' => fn ($guestQuery) => $guestQuery->withoutGlobalScopes()->select(['id', 'name', 'email', 'phone', 'wedding_id']),
                'event' => fn ($eventQuery) => $eventQuery->withoutGlobalScopes()->select(['id', 'name', 'wedding_id']),
                'operator:id,name',
            ])
            ->orderByDesc('checked_in_at')
            ->limit($limit)
            ->get()
            ->map(fn (GuestCheckin $checkin): array => [
                'id' => $checkin->id,
                'checked_in_at' => $checkin->checked_in_at,
                'method' => $checkin->method,
                'method_label' => $this->methodLabel($checkin->method),
                'device_id' => $checkin->device_id,
                'notes' => $checkin->notes,
                'guest' => [
                    'id' => $checkin->guest?->id,
                    'name' => $checkin->guest?->name,
                    'email' => $checkin->guest?->email,
                    'phone' => $checkin->guest?->phone,
                ],
                'event' => [
                    'id' => $checkin->event?->id,
                    'name' => $checkin->event?->name,
                ],
                'operator' => [
                    'id' => $checkin->operator?->id,
                    'name' => $checkin->operator?->name,
                ],
            ])
            ->values();

        return [
            'items' => $items,
            'summary' => [
                'total_checkins' => $totalCheckins,
                'unique_checked_in_guests' => $uniqueGuests,
                'checkins_today' => $checkinsToday,
                'duplicates_ignored_24h' => $duplicatesIgnored24h,
                'by_method' => $byMethod,
                'by_event' => $byEvent,
            ],
        ];
    }

    private function baseQuery(string $weddingId): Builder
    {
        return GuestCheckin::query()
            ->whereHas('guest', fn (Builder $query): Builder => $query->withoutGlobalScopes()->where('wedding_id', $weddingId));
    }

    private function resolveEventForWedding(?string $eventId, string $weddingId, bool $mustBeActive = false): ?GuestEvent
    {
        if (!$eventId) {
            return null;
        }

        $event = GuestEvent::withoutGlobalScopes()->find($eventId);

        if (!$event) {
            throw new \InvalidArgumentException('Evento nao encontrado.');
        }

        if ((string) $event->wedding_id !== (string) $weddingId) {
            throw new \InvalidArgumentException('Evento invalido para este casamento.');
        }

        if ($mustBeActive && !$event->is_active) {
            throw new \InvalidArgumentException('Evento inativo para check-in.');
        }

        return $event;
    }

    private function recordAudit(string $weddingId, string $action, array $context, ?string $actorId): void
    {
        $this->guestAuditLogService->record(
            weddingId: $weddingId,
            action: $action,
            context: $context,
            actorId: $actorId,
        );
    }

    private function methodLabel(?string $method): string
    {
        return match ($method) {
            'qr' => 'QR',
            'manual' => 'Manual',
            default => ucfirst((string) ($method ?: 'manual')),
        };
    }
}
