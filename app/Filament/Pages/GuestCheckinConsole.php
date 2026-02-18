<?php

namespace App\Filament\Pages;

use App\Models\Guest;
use App\Models\GuestCheckin;
use App\Models\GuestEvent;
use App\Models\Wedding;
use App\Services\Guests\GuestCheckinQrService;
use App\Services\Guests\GuestCheckinService;
use App\Services\PermissionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GuestCheckinConsole extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Operação Check-in';

    protected static ?string $navigationGroup = 'CONVIDADOS';

    protected static ?string $title = 'Operação de Check-in';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'guest-checkin-console';

    protected static string $view = 'filament.pages.guest-checkin-console';

    public ?string $eventId = null;

    public string $methodFilter = '';

    public string $qrCode = '';

    public string $deviceId = '';

    public string $scanNotes = '';

    public string $manualSearch = '';

    public string $manualNotes = '';

    public int $limit = 20;

    public function mount(): void
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            redirect()->route('filament.admin.pages.dashboard');

            return;
        }

        $this->eventId = GuestEvent::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->where('is_active', true)
            ->orderBy('event_at')
            ->value('id');

        $this->deviceId = request()->ip() ?? '';
    }

    public function getEventOptions(): array
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            return [];
        }

        return GuestEvent::withoutGlobalScopes()
            ->where('wedding_id', $weddingId)
            ->orderByRaw('CASE WHEN is_active THEN 0 ELSE 1 END')
            ->orderBy('event_at')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->mapWithKeys(fn (GuestEvent $event): array => [
                $event->id => $event->name . ($event->is_active ? '' : ' (inativo)'),
            ])
            ->all();
    }

    public function getConsoleData(): array
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            return [
                'items' => collect(),
                'summary' => [
                    'total_checkins' => 0,
                    'unique_checked_in_guests' => 0,
                    'checkins_today' => 0,
                    'duplicates_ignored_24h' => 0,
                    'by_method' => collect(),
                    'by_event' => collect(),
                ],
            ];
        }

        try {
            return app(GuestCheckinService::class)->listForWedding($weddingId, [
                'event_id' => $this->eventId ?: null,
                'method' => $this->methodFilter ?: null,
                'limit' => $this->limit,
            ]);
        } catch (\InvalidArgumentException $exception) {
            Notification::make()
                ->title('Erro ao carregar check-ins')
                ->warning()
                ->body($exception->getMessage())
                ->send();

            return [
                'items' => collect(),
                'summary' => [
                    'total_checkins' => 0,
                    'unique_checked_in_guests' => 0,
                    'checkins_today' => 0,
                    'duplicates_ignored_24h' => 0,
                    'by_method' => collect(),
                    'by_event' => collect(),
                ],
            ];
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getManualCandidates(): Collection
    {
        $weddingId = $this->getWeddingId();
        $search = trim($this->manualSearch);

        if (!$weddingId || mb_strlen($search) < 2) {
            return collect();
        }

        $searchLike = '%' . mb_strtolower($search) . '%';

        $guests = Guest::withoutGlobalScopes()
            ->with(['household:id,name'])
            ->where('wedding_id', $weddingId)
            ->where(function (Builder $query) use ($searchLike): void {
                $query
                    ->whereRaw('LOWER(name) LIKE ?', [$searchLike])
                    ->orWhereRaw("LOWER(COALESCE(email, '')) LIKE ?", [$searchLike])
                    ->orWhereRaw("LOWER(COALESCE(phone, '')) LIKE ?", [$searchLike]);
            })
            ->orderBy('name')
            ->limit(15)
            ->get();

        return $guests
            ->map(function (Guest $guest): array {
                $alreadyCheckedIn = GuestCheckin::query()
                    ->where('guest_id', $guest->id)
                    ->when(
                        $this->eventId,
                        fn (Builder $query): Builder => $query->where('event_id', $this->eventId),
                        fn (Builder $query): Builder => $query->whereNull('event_id'),
                    )
                    ->exists();

                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'household' => $guest->household?->name,
                    'email' => $guest->email,
                    'phone' => $guest->phone,
                    'already_checked_in' => $alreadyCheckedIn,
                ];
            })
            ->values();
    }

    public function scanQr(): void
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            $this->warn('Casamento nao selecionado', 'Selecione um casamento antes de registrar check-ins.');

            return;
        }

        if (trim($this->qrCode) === '') {
            $this->warn('QR vazio', 'Informe um codigo QR valido para registrar o check-in.');

            return;
        }

        try {
            $guestId = app(GuestCheckinQrService::class)->resolveGuestId(
                rawCode: $this->qrCode,
                weddingId: $weddingId,
            );

            $result = app(GuestCheckinService::class)->record(
                weddingId: $weddingId,
                guestId: $guestId,
                eventId: $this->eventId,
                method: 'qr',
                deviceId: $this->deviceId !== '' ? $this->deviceId : null,
                notes: $this->scanNotes !== '' ? $this->scanNotes : null,
                operatorId: auth()->id(),
            );
        } catch (\InvalidArgumentException $exception) {
            $this->warn('Falha no check-in', $exception->getMessage());

            return;
        }

        $this->qrCode = '';

        $notification = Notification::make()
            ->title($result['duplicate'] ? 'Check-in ja existente' : 'Check-in registrado')
            ->body($result['guest']->name ?? 'Convidado');

        if ($result['duplicate']) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $notification->send();
    }

    public function scanQrFromCamera(string $rawCode): void
    {
        $this->qrCode = $rawCode;
        $this->scanQr();
    }

    public function manualCheckin(string $guestId): void
    {
        $weddingId = $this->getWeddingId();

        if (!$weddingId) {
            $this->warn('Casamento nao selecionado', 'Selecione um casamento antes de registrar check-ins.');

            return;
        }

        try {
            $result = app(GuestCheckinService::class)->record(
                weddingId: $weddingId,
                guestId: $guestId,
                eventId: $this->eventId,
                method: 'manual',
                deviceId: $this->deviceId !== '' ? $this->deviceId : null,
                notes: $this->manualNotes !== '' ? $this->manualNotes : null,
                operatorId: auth()->id(),
            );
        } catch (\InvalidArgumentException $exception) {
            $this->warn('Falha no check-in manual', $exception->getMessage());

            return;
        }

        $notification = Notification::make()
            ->title($result['duplicate'] ? 'Check-in ja existente' : 'Check-in manual registrado')
            ->body($result['guest']->name ?? 'Convidado');

        if ($result['duplicate']) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $notification->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $weddingId = session('filament_wedding_id') ?? $user?->current_wedding_id;

        if (!$user || !$weddingId) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $wedding = Wedding::find($weddingId);

        return $wedding && app(PermissionService::class)->canAccess($user, 'guests', $wedding);
    }

    private function getWeddingId(): ?string
    {
        return session('filament_wedding_id') ?? auth()->user()?->current_wedding_id;
    }

    private function warn(string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->warning()
            ->body($body)
            ->send();
    }
}
