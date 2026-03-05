<?php

namespace App\Filament\Resources\WeddingEventResource\Pages;

use App\Filament\Resources\WeddingEventResource;
use App\Models\WeddingEventRsvp;
use App\Models\WeddingGuest;
use App\Models\WeddingInvite;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class EventDashboard extends Page
{
    use InteractsWithRecord;

    protected static string $resource = WeddingEventResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Dashboard do Evento';

    protected static string $view = 'filament.resources.wedding-event-resource.pages.event-dashboard';

    public array $totals = [
        'total' => 0,
        'confirmed' => 0,
        'pending' => 0,
        'declined' => 0,
        'adults' => 0,
        'children' => 0,
    ];

    public array $statusBreakdown = [];

    public array $sideBreakdown = [];

    public array $ageBreakdown = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(static::getResource()::canView($this->getRecord()), 403);

        $this->loadDashboard();
    }

    public function getHeading(): string
    {
        return '';
    }

    private function loadDashboard(): void
    {
        $event = $this->getRecord();
        $eventId = (string) $event->getKey();
        $weddingId = (string) $event->wedding_id;

        $rsvps = WeddingEventRsvp::query()
            ->where('event_id', $this->getRecord()->getKey())
            ->with('guest:id,side,is_child')
            ->get();

        $rsvpsByGuest = $rsvps->keyBy('guest_id');

        $primaryContactIds = WeddingInvite::query()
            ->where('event_id', $eventId)
            ->where('wedding_id', $weddingId)
            ->whereNotNull('primary_contact_id')
            ->pluck('primary_contact_id')
            ->map(fn ($id): string => (string) $id)
            ->unique()
            ->values();

        $scopedGuestIds = collect();

        if ($primaryContactIds->isNotEmpty()) {
            $scopedGuestIds = WeddingGuest::query()
                ->where('wedding_id', $weddingId)
                ->where(function ($query) use ($primaryContactIds): void {
                    $query->whereIn('id', $primaryContactIds->all())
                        ->orWhereIn('primary_contact_id', $primaryContactIds->all());
                })
                ->pluck('id')
                ->map(fn ($id): string => (string) $id)
                ->unique()
                ->values();
        }

        $baseGuestIds = $scopedGuestIds
            ->concat(
                $rsvps->pluck('guest_id')
                    ->map(fn ($id): string => (string) $id)
            )
            ->unique()
            ->values();

        if ($baseGuestIds->isEmpty()) {
            $this->totals = [
                'total' => 0,
                'confirmed' => 0,
                'pending' => 0,
                'declined' => 0,
                'adults' => 0,
                'children' => 0,
            ];

            $this->statusBreakdown = $this->toBreakdown(
                [
                    WeddingEventRsvp::STATUS_CONFIRMED => 'Confirmado',
                    WeddingEventRsvp::STATUS_PENDING => 'Pendente',
                    WeddingEventRsvp::STATUS_DECLINED => 'Recusado',
                ],
                collect(),
                0
            );
            $this->sideBreakdown = $this->toBreakdown(
                [
                    'bride' => 'Noiva',
                    'groom' => 'Noivo',
                    'both' => 'Ambos',
                ],
                collect(),
                0
            );
            $this->ageBreakdown = $this->toBreakdown(
                [
                    'adults' => 'Adulto',
                    'children' => 'Criança',
                ],
                collect(),
                0
            );

            return;
        }

        $guests = WeddingGuest::query()
            ->where('wedding_id', $weddingId)
            ->whereIn('id', $baseGuestIds->all())
            ->get(['id', 'side', 'is_child']);

        $total = $guests->count();
        $statusCounts = $guests->countBy(function (WeddingGuest $guest) use ($rsvpsByGuest): string {
            /** @var WeddingEventRsvp|null $rsvp */
            $rsvp = $rsvpsByGuest->get((string) $guest->id);

            return (string) ($rsvp?->status ?: WeddingEventRsvp::STATUS_PENDING);
        });
        $sideCounts = $guests->countBy(fn (WeddingGuest $guest): string => (string) ($guest->side ?: 'both'));

        $adults = $guests
            ->filter(fn (WeddingGuest $guest): bool => ! (bool) $guest->is_child)
            ->count();
        $children = $guests
            ->filter(fn (WeddingGuest $guest): bool => (bool) $guest->is_child)
            ->count();

        $this->totals = [
            'total' => $total,
            'confirmed' => (int) ($statusCounts[WeddingEventRsvp::STATUS_CONFIRMED] ?? 0),
            'pending' => (int) ($statusCounts[WeddingEventRsvp::STATUS_PENDING] ?? 0),
            'declined' => (int) ($statusCounts[WeddingEventRsvp::STATUS_DECLINED] ?? 0),
            'adults' => $adults,
            'children' => $children,
        ];

        $this->statusBreakdown = $this->toBreakdown(
            [
                WeddingEventRsvp::STATUS_CONFIRMED => 'Confirmado',
                WeddingEventRsvp::STATUS_PENDING => 'Pendente',
                WeddingEventRsvp::STATUS_DECLINED => 'Recusado',
            ],
            $statusCounts,
            $total
        );

        $this->sideBreakdown = $this->toBreakdown(
            [
                'bride' => 'Noiva',
                'groom' => 'Noivo',
                'both' => 'Ambos',
            ],
            $sideCounts,
            $total
        );

        $this->ageBreakdown = $this->toBreakdown(
            [
                'adults' => 'Adulto',
                'children' => 'Criança',
            ],
            collect([
                'adults' => $adults,
                'children' => $children,
            ]),
            $total
        );
    }

    /**
     * @param array<string, string> $labels
     * @param Collection<string, int> $counts
     * @return array<int, array{key: string, label: string, count: int, percentage: float}>
     */
    private function toBreakdown(array $labels, Collection $counts, int $total): array
    {
        return collect($labels)
            ->map(function (string $label, string $key) use ($counts, $total): array {
                $count = (int) ($counts[$key] ?? 0);
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;

                return [
                    'key' => $key,
                    'label' => $label,
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            })
            ->values()
            ->all();
    }
}
