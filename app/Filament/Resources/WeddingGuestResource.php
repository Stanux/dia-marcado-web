<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeddingGuestResource\Pages;
use App\Models\WeddingEvent;
use App\Models\WeddingGuest;
use App\Models\WeddingInvite;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class WeddingGuestResource extends WeddingScopedResource
{
    protected static ?string $model = WeddingGuest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Convidados';

    protected static ?string $navigationGroup = null;

    protected static ?string $module = 'guests';

    protected static ?string $modelLabel = 'Convidado';

    protected static ?string $pluralModelLabel = 'Convidados';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Convidado')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nickname')
                            ->label('Apelido')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->placeholder('(00) 00000-0000')
                            ->mask('(99) 99999-9999')
                            ->maxLength(15),

                        Forms\Components\Select::make('primary_contact_id')
                            ->label('Contato Principal')
                            ->options(fn (?WeddingGuest $record): array => WeddingGuest::query()
                                ->primaryContacts()
                                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->rule(function (?WeddingGuest $record): \Closure {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $selectedPrimary = WeddingGuest::query()->find($value);
                                    if (!$selectedPrimary) {
                                        return;
                                    }

                                    if ($record && (string) $record->getKey() === (string) $selectedPrimary->getKey()) {
                                        $fail('O contato principal não pode ser o próprio convidado.');
                                        return;
                                    }

                                    if ($selectedPrimary->primary_contact_id !== null) {
                                        $fail('O contato definido como Principal já está cadastrado como convidado.');
                                    }
                                };
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->helperText('Se vazio, este registro também é um contato principal.'),

                        Forms\Components\Select::make('side')
                            ->label('Lado')
                            ->options([
                                'bride' => 'Noiva',
                                'groom' => 'Noivo',
                                'both' => 'Ambos',
                            ])
                            ->default('both')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_child')
                            ->label('Criança')
                            ->inline(false)
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->inline(false)
                            ->default(true),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('primaryContact.name')
                    ->label('Contato Principal')
                    ->placeholder('Contato principal')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return static::applySortByPrimaryContactThenName($query, $direction);
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('side')
                    ->label('Lado')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bride' => 'Noiva',
                        'groom' => 'Noivo',
                        default => 'Ambos',
                    }),

                Tables\Columns\IconColumn::make('is_child')
                    ->label('Criança')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('side')
                    ->label('Lado')
                    ->options([
                        'bride' => 'Noiva',
                        'groom' => 'Noivo',
                        'both' => 'Ambos',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_generate_invites')
                        ->label('Gerar Convites em Lote')
                        ->icon('heroicon-o-ticket')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('event_id')
                                ->label('Evento')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->options(fn (): array => WeddingEvent::query()
                                    ->where('is_active', true)
                                    ->orderBy('event_date')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (WeddingEvent $event): array => [
                                        (string) $event->id => (string) $event->name,
                                    ])
                                    ->all())
                                ->helperText('Selecione o evento (aberto ou fechado) para gerar os convites em lote.'),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Nenhum convidado selecionado')
                                    ->body('Selecione ao menos um convidado para gerar os convites.')
                                    ->send();
                                return;
                            }

                            $eventId = (string) ($data['event_id'] ?? '');
                            $event = WeddingEvent::query()->find($eventId);

                            if (!$event) {
                                Notification::make()
                                    ->danger()
                                    ->title('Evento inválido')
                                    ->body('Selecione um evento válido para continuar.')
                                    ->send();
                                return;
                            }

                            $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
                            if (!$weddingId) {
                                Notification::make()
                                    ->danger()
                                    ->title('Casamento não selecionado')
                                    ->body('Selecione um casamento antes de gerar convites.')
                                    ->send();
                                return;
                            }

                            $principalIds = $records
                                ->map(function (WeddingGuest $guest): string {
                                    return (string) ($guest->primary_contact_id ?: $guest->id);
                                })
                                ->unique()
                                ->values();

                            $created = 0;
                            $alreadyExists = 0;
                            $withoutGroup = 0;

                            DB::transaction(function () use (
                                $principalIds,
                                $event,
                                $weddingId,
                                &$created,
                                &$alreadyExists,
                                &$withoutGroup
                            ): void {
                                foreach ($principalIds as $principalId) {
                                    $principal = WeddingGuest::query()
                                        ->where('wedding_id', $weddingId)
                                        ->whereKey($principalId)
                                        ->first();

                                    if (!$principal) {
                                        $withoutGroup++;
                                        continue;
                                    }

                                    $groupGuests = WeddingGuest::query()
                                        ->where('wedding_id', $weddingId)
                                        ->where(function (Builder $query) use ($principalId): void {
                                            $query->whereKey($principalId)
                                                ->orWhere('primary_contact_id', $principalId);
                                        })
                                        ->get(['id', 'is_child']);

                                    if ($groupGuests->isEmpty()) {
                                        $withoutGroup++;
                                        continue;
                                    }

                                    $hasActiveInvite = WeddingInvite::withoutGlobalScopes()
                                        ->where('wedding_id', $weddingId)
                                        ->where('event_id', $event->id)
                                        ->where('invite_type', 'individual')
                                        ->where('primary_contact_id', $principalId)
                                        ->where('is_active', true)
                                        ->where('expires_at', '>', now())
                                        ->exists();

                                    if ($hasActiveInvite) {
                                        $alreadyExists++;
                                        continue;
                                    }

                                    $adultQuota = $event->isClosed()
                                        ? 0
                                        : $groupGuests->where('is_child', false)->count();
                                    $childQuota = $event->isClosed()
                                        ? 0
                                        : $groupGuests->where('is_child', true)->count();

                                    WeddingInvite::withoutGlobalScopes()->create([
                                        'wedding_id' => $weddingId,
                                        'event_id' => $event->id,
                                        'created_by' => auth()->id(),
                                        'primary_contact_id' => $principalId,
                                        'invite_type' => 'individual',
                                        'token' => static::generateUniqueInviteToken(),
                                        'confirmation_code' => static::generateUniqueConfirmationCode(),
                                        'adult_quota' => $adultQuota,
                                        'child_quota' => $childQuota,
                                        'expires_at' => static::resolveBulkInviteExpiration($event),
                                        'is_active' => true,
                                    ]);

                                    $created++;
                                }
                            });

                            $body = "{$created} convite(s) criado(s).";
                            if ($alreadyExists > 0) {
                                $body .= " {$alreadyExists} já possuíam convite ativo e foram ignorados.";
                            }
                            if ($withoutGroup > 0) {
                                $body .= " {$withoutGroup} não puderam ser processados por falta do contato principal.";
                            }

                            $notification = Notification::make()
                                ->title('Geração em lote concluída')
                                ->body($body);

                            if ($created > 0 && $withoutGroup === 0) {
                                $notification->success();
                            } else {
                                $notification->warning();
                            }

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(fn (Builder $query, string $direction): Builder => static::applySortByPrimaryContactThenName($query, $direction));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeddingGuests::route('/'),
            'create' => Pages\CreateWeddingGuest::route('/create'),
            'edit' => Pages\EditWeddingGuest::route('/{record}/edit'),
        ];
    }

    public static function getSlug(): string
    {
        return 'guests-v2';
    }

    private static function applySortByPrimaryContactThenName(Builder $query, string $direction): Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $joins = $query->getQuery()->joins ?? [];
        $alreadyJoined = collect($joins)
            ->contains(fn ($join): bool => (string) ($join->table ?? '') === 'wedding_guests as primary_contacts');

        if (!$alreadyJoined) {
            $query->leftJoin(
                'wedding_guests as primary_contacts',
                'primary_contacts.id',
                '=',
                'wedding_guests.primary_contact_id'
            );
        }

        return $query
            ->orderByRaw('COALESCE(primary_contacts.name, wedding_guests.name) ' . $direction)
            ->orderBy('wedding_guests.name', $direction)
            ->select('wedding_guests.*');
    }

    private static function generateUniqueInviteToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (WeddingInvite::withoutGlobalScopes()->where('token', $token)->exists());

        return $token;
    }

    private static function generateUniqueConfirmationCode(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {
            $code = '';

            for ($index = 0; $index < 6; $index++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (WeddingInvite::withoutGlobalScopes()->where('confirmation_code', $code)->exists());

        return $code;
    }

    private static function resolveBulkInviteExpiration(WeddingEvent $event): Carbon
    {
        if ($event->event_date) {
            $date = $event->event_date->format('Y-m-d');
            $time = filled($event->event_time) ? substr((string) $event->event_time, 0, 8) : '23:59:59';

            return Carbon::parse("{$date} {$time}");
        }

        return now()->addDays(7);
    }
}
