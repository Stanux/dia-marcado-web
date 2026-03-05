<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeddingInviteResource\Pages;
use App\Models\WeddingEvent;
use App\Models\WeddingEventRsvp;
use App\Models\WeddingGuest;
use App\Models\WeddingInvite;
use App\Models\SiteLayout;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Component;

class WeddingInviteResource extends WeddingScopedResource
{
    protected static ?string $model = WeddingInvite::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Convites';

    protected static ?string $navigationGroup = null;

    protected static ?string $module = 'invites';

    protected static ?string $modelLabel = 'Convite';

    protected static ?string $pluralModelLabel = 'Convites';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Convite')
                    ->schema([
                        Forms\Components\Select::make('event_id')
                            ->label('Evento')
                            ->relationship('event', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateHydrated(function (Forms\Set $set, ?string $state): void {
                                if (static::isClosedEvent($state)) {
                                    $set('adult_quota', 0);
                                    $set('child_quota', 0);
                                }
                            })
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                if (static::isClosedEvent($state)) {
                                    $set('adult_quota', 0);
                                    $set('child_quota', 0);
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\Select::make('invite_type')
                            ->label('Tipo de Convite')
                            ->options([
                                'individual' => 'Individual',
                                'global' => 'Global',
                            ])
                            ->default('individual')
                            ->required()
                            ->native(false)
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('adult_quota')
                            ->label('Cota Adultos')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(fn (Forms\Get $get): bool => static::isClosedEvent($get('event_id')))
                            ->dehydrateStateUsing(fn ($state, Forms\Get $get): int | null => static::isClosedEvent($get('event_id'))
                                ? 0
                                : (filled($state) ? (int) $state : null))
                            ->nullable()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('child_quota')
                            ->label('Cota Crianças')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(fn (Forms\Get $get): bool => static::isClosedEvent($get('event_id')))
                            ->dehydrateStateUsing(fn ($state, Forms\Get $get): int | null => static::isClosedEvent($get('event_id'))
                                ? 0
                                : (filled($state) ? (int) $state : null))
                            ->nullable()
                            ->columnSpan(1),

                        Forms\Components\Select::make('primary_contact_id')
                            ->label('Contato Principal')
                            ->options(fn (): array => WeddingGuest::query()
                                ->primaryContacts()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->live()
                            ->required(fn (Forms\Get $get): bool => $get('invite_type') !== 'global')
                            ->visible(fn (Forms\Get $get): bool => $get('invite_type') !== 'global')
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expira em')
                            ->maxDate(fn (Forms\Get $get): ?Carbon => static::resolveEventMaxExpiresAt($get('event_id')))
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true)
                            ->columnSpan(1),

                    ])
                    ->columns(4),

                Forms\Components\Section::make('Convidados e Status')
                    ->schema([
                        Forms\Components\Placeholder::make('guest_statuses')
                            ->label('')
                            ->content(function (Forms\Get $get, Component $livewire): HtmlString {
                                $draftStatuses = method_exists($livewire, 'getGuestRsvpStatusDrafts')
                                    ? $livewire->getGuestRsvpStatusDrafts()
                                    : [];
                                $inviteId = method_exists($livewire, 'getCurrentInviteId')
                                    ? $livewire->getCurrentInviteId()
                                    : static::resolveCurrentInviteId();

                                return static::buildGuestStatusListHtml(
                                    eventId: $get('event_id'),
                                    inviteType: $get('invite_type'),
                                    primaryContactId: $get('primary_contact_id'),
                                    inviteId: $inviteId,
                                    draftStatuses: is_array($draftStatuses) ? $draftStatuses : [],
                                );
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event.name')
                    ->label('Evento')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invite_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'global' ? 'Global' : 'Individual')
                    ->color(fn (string $state): string => $state === 'global' ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('primaryContact.name')
                    ->label('Contato Principal')
                    ->placeholder('Convite Global')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('confirmation_code')
                    ->label('Código')
                    ->badge()
                    ->color('warning')
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->copyMessageDuration(1500)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_link')
                    ->label('Copiar Link')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip('Copiar link de acesso')
                    ->action(function (WeddingInvite $record, Component $livewire): void {
                        $link = static::buildInviteAccessUrl($record);

                        $livewire->js('if (navigator.clipboard) { navigator.clipboard.writeText(' . Js::from($link) . '); }');

                        Notification::make()
                            ->title('Link copiado')
                            ->body($link)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->iconButton()
                    ->url(fn (WeddingInvite $record): string => static::buildWhatsappUrl($record), shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeddingInvites::route('/'),
            'create' => Pages\CreateWeddingInvite::route('/create'),
            'edit' => Pages\EditWeddingInvite::route('/{record}/edit'),
        ];
    }

    public static function getSlug(): string
    {
        return 'invites-v2';
    }

    protected static function buildWhatsappUrl(WeddingInvite $invite): string
    {
        $link = static::buildInviteAccessUrl($invite);

        $messageParts = [
            'Evento: ' . ($invite->event?->name ?? 'Evento'),
            'Acesse para confirmar presença: ' . $link,
        ];

        if (!empty($invite->confirmation_code)) {
            $messageParts[] = 'Código de confirmação: ' . $invite->confirmation_code;
        }

        if ($invite->expires_at) {
            $messageParts[] = 'Validade: ' . $invite->expires_at->format('d/m/Y H:i');
        }

        $message = implode("\n", $messageParts);

        return 'https://wa.me/?text=' . rawurlencode($message);
    }

    protected static function buildInviteAccessUrl(WeddingInvite $invite): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $siteSlug = SiteLayout::withoutGlobalScopes()
            ->where('wedding_id', $invite->wedding_id)
            ->value('slug');

        return $siteSlug
            ? "{$appUrl}/site/{$siteSlug}/convidados?event={$invite->event_id}"
            : "{$appUrl}/";
    }

    protected static function isClosedEvent(?string $eventId): bool
    {
        if (blank($eventId)) {
            return false;
        }

        return WeddingEvent::withoutGlobalScopes()
            ->whereKey($eventId)
            ->where('event_type', 'closed')
            ->exists();
    }

    protected static function resolveEventMaxExpiresAt(?string $eventId): ?Carbon
    {
        if (blank($eventId)) {
            return null;
        }

        $event = WeddingEvent::withoutGlobalScopes()->find($eventId);

        if (! $event?->event_date) {
            return null;
        }

        $date = $event->event_date->format('Y-m-d');
        $time = filled($event->event_time) ? substr((string) $event->event_time, 0, 8) : '23:59:59';

        return Carbon::parse("{$date} {$time}");
    }

    protected static function buildGuestStatusListHtml(
        ?string $eventId,
        ?string $inviteType,
        ?string $primaryContactId,
        ?string $inviteId,
        array $draftStatuses = []
    ): HtmlString {
        if (blank($eventId)) {
            return new HtmlString('<p class="text-sm text-gray-500">Selecione o evento para visualizar os convidados e status.</p>');
        }

        if (($inviteType ?? 'individual') === 'global') {
            if (blank($inviteId)) {
                return new HtmlString('<p class="text-sm text-gray-500">Salve o convite global para começar a visualizar quem confirmou presença.</p>');
            }

            $rsvps = WeddingEventRsvp::query()
                ->where('event_id', $eventId)
                ->where('invite_id', $inviteId)
                ->with(['guest:id,name'])
                ->orderByDesc('responded_at')
                ->get();

            if ($rsvps->isEmpty()) {
                return new HtmlString('<p class="text-sm text-gray-500">Ainda não há convidados vinculados a este convite global.</p>');
            }

            $rows = $rsvps
                ->unique('guest_id')
                ->map(function (WeddingEventRsvp $rsvp) use ($inviteId, $draftStatuses): string {
                    $guestId = $rsvp->guest ? (string) $rsvp->guest_id : null;
                    $persistedStatus = (string) ($rsvp->status ?? WeddingEventRsvp::STATUS_PENDING);
                    $status = static::resolveDraftStatus(
                        guestId: $guestId,
                        persistedStatus: $persistedStatus,
                        draftStatuses: $draftStatuses,
                    );
                    $statusButtons = static::buildStatusToggleGroupHtml(
                        guestId: $guestId,
                        status: $status,
                        enabled: filled($inviteId),
                    );

                    return '<tr class="border-t border-gray-100">'
                        . '<td class="px-4 py-2 text-sm text-gray-900">' . e((string) ($rsvp->guest?->name ?? 'Convidado removido')) . '</td>'
                        . '<td class="px-4 py-2 text-sm">'
                        . $statusButtons
                        . '</td>'
                        . '</tr>';
                })
                ->implode('');

            $table = '<div class="overflow-hidden rounded-lg border border-gray-200">'
                . '<table class="min-w-full divide-y divide-gray-200">'
                . '<thead class="bg-gray-50">'
                . '<tr>'
                . '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Convidado</th>'
                . '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status no Evento</th>'
                . '</tr>'
                . '</thead>'
                . '<tbody class="bg-white">'
                . $rows
                . '</tbody>'
                . '</table>'
                . '</div>';

            return new HtmlString($table);
        }

        if (blank($primaryContactId)) {
            return new HtmlString('<p class="text-sm text-gray-500">Selecione o contato principal para visualizar os convidados vinculados.</p>');
        }

        $guests = WeddingGuest::query()
            ->where(function ($query) use ($primaryContactId): void {
                $query->whereKey($primaryContactId)
                    ->orWhere('primary_contact_id', $primaryContactId);
            })
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$primaryContactId])
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($guests->isEmpty()) {
            return new HtmlString('<p class="text-sm text-gray-500">Nenhum convidado encontrado para este contato principal.</p>');
        }

        $rsvps = WeddingEventRsvp::query()
            ->where('event_id', $eventId)
            ->whereIn('guest_id', $guests->pluck('id')->all())
            ->get(['guest_id', 'status'])
            ->keyBy('guest_id');

        $rows = $guests->map(function (WeddingGuest $guest) use ($rsvps, $inviteId, $draftStatuses): string {
            $guestId = (string) $guest->id;
            $persistedStatus = (string) ($rsvps->get($guest->id)?->status ?? WeddingEventRsvp::STATUS_PENDING);
            $status = static::resolveDraftStatus(
                guestId: $guestId,
                persistedStatus: $persistedStatus,
                draftStatuses: $draftStatuses,
            );
            $statusButtons = static::buildStatusToggleGroupHtml(
                guestId: $guestId,
                status: $status,
                enabled: filled($inviteId),
            );

            return '<tr class="border-t border-gray-100">'
                . '<td class="px-4 py-2 text-sm text-gray-900">' . e((string) $guest->name) . '</td>'
                . '<td class="px-4 py-2 text-sm">'
                . $statusButtons
                . '</td>'
                . '</tr>';
        })->implode('');

        $table = '<div class="overflow-hidden rounded-lg border border-gray-200">'
            . '<table class="min-w-full divide-y divide-gray-200">'
            . '<thead class="bg-gray-50">'
            . '<tr>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Convidado</th>'
            . '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status no Evento</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody class="bg-white">'
            . $rows
            . '</tbody>'
            . '</table>'
            . '</div>';

        return new HtmlString($table);
    }

    protected static function resolveDraftStatus(?string $guestId, string $persistedStatus, array $draftStatuses): string
    {
        if (blank($guestId)) {
            return $persistedStatus;
        }

        $draftStatus = $draftStatuses[$guestId] ?? null;

        return in_array($draftStatus, [
            WeddingEventRsvp::STATUS_PENDING,
            WeddingEventRsvp::STATUS_CONFIRMED,
            WeddingEventRsvp::STATUS_DECLINED,
        ], true)
            ? (string) $draftStatus
            : $persistedStatus;
    }

    protected static function buildStatusToggleGroupHtml(?string $guestId, string $status, bool $enabled): string
    {
        if (!$enabled || blank($guestId)) {
            return static::buildReadonlyStatusBadgeHtml($status);
        }

        $guestIdEscaped = e($guestId);
        $buttons = [
            WeddingEventRsvp::STATUS_PENDING => 'Pendente',
            WeddingEventRsvp::STATUS_CONFIRMED => 'Confirmado',
            WeddingEventRsvp::STATUS_DECLINED => 'Recusado',
        ];

        $segments = collect($buttons)->map(function (string $label, string $buttonStatus) use ($guestIdEscaped, $status): string {
            $isActive = $status === $buttonStatus;

            $activeClasses = match ($buttonStatus) {
                WeddingEventRsvp::STATUS_CONFIRMED => 'bg-emerald-50 text-emerald-700',
                WeddingEventRsvp::STATUS_DECLINED => 'bg-rose-50 text-rose-700',
                default => 'bg-gray-100 text-gray-800',
            };

            $classes = $isActive
                ? $activeClasses
                : 'bg-white text-gray-600 hover:bg-gray-50';

            return '<button'
                . ' type="button"'
                . ' wire:click="setGuestRsvpStatusDraft(\'' . $guestIdEscaped . '\', \'' . $buttonStatus . '\')"'
                . ' class="px-3 py-1 text-xs font-medium border-l border-gray-300 first:border-l-0 transition ' . $classes . '"'
                . '>'
                . e($label)
                . '</button>';
        })->implode('');

        return '<div class="inline-flex overflow-hidden rounded-md border border-gray-300 bg-white">' . $segments . '</div>';
    }

    protected static function buildReadonlyStatusBadgeHtml(string $status): string
    {
        $statusLabel = match ($status) {
            WeddingEventRsvp::STATUS_CONFIRMED => 'Confirmado',
            WeddingEventRsvp::STATUS_DECLINED => 'Recusado',
            default => 'Pendente',
        };

        $statusClasses = match ($status) {
            WeddingEventRsvp::STATUS_CONFIRMED => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            WeddingEventRsvp::STATUS_DECLINED => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
        };

        return '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $statusClasses . '">'
            . e($statusLabel)
            . '</span>';
    }

    protected static function resolveCurrentInviteId(): ?string
    {
        $record = request()->route('record');

        if ($record instanceof WeddingInvite) {
            return (string) $record->getKey();
        }

        if (is_string($record) && $record !== '') {
            return $record;
        }

        return null;
    }
}
