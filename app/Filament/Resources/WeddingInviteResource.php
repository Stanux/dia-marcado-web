<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeddingInviteResource\Pages;
use App\Models\WeddingEvent;
use App\Models\WeddingGuest;
use App\Models\WeddingInvite;
use Illuminate\Support\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

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
                            }),

                        Forms\Components\Select::make('invite_type')
                            ->label('Tipo de Convite')
                            ->options([
                                'individual' => 'Individual',
                                'global' => 'Global',
                            ])
                            ->default('individual')
                            ->required()
                            ->native(false)
                            ->live(),

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
                            ->required(fn (Forms\Get $get): bool => $get('invite_type') !== 'global')
                            ->visible(fn (Forms\Get $get): bool => $get('invite_type') !== 'global'),

                        Forms\Components\TextInput::make('adult_quota')
                            ->label('Cota Adultos')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(fn (Forms\Get $get): bool => static::isClosedEvent($get('event_id')))
                            ->dehydrateStateUsing(fn ($state, Forms\Get $get): int | null => static::isClosedEvent($get('event_id'))
                                ? 0
                                : (filled($state) ? (int) $state : null))
                            ->nullable(),

                        Forms\Components\TextInput::make('child_quota')
                            ->label('Cota Crianças')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(fn (Forms\Get $get): bool => static::isClosedEvent($get('event_id')))
                            ->dehydrateStateUsing(fn ($state, Forms\Get $get): int | null => static::isClosedEvent($get('event_id'))
                                ? 0
                                : (filled($state) ? (int) $state : null))
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expira em')
                            ->maxDate(fn (Forms\Get $get): ?Carbon => static::resolveEventMaxExpiresAt($get('event_id')))
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),

                        Forms\Components\TextInput::make('token')
                            ->label('Token')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation === 'edit')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('confirmation_code')
                            ->label('Código de Confirmação (Evento Fechado)')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Envio')
                    ->schema([
                        Forms\Components\Select::make('sent_via')
                            ->label('Canal de Envio')
                            ->options([
                                'whatsapp' => 'WhatsApp',
                                'email' => 'E-mail',
                            ])
                            ->native(false)
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Enviado em')
                            ->nullable(),
                    ])
                    ->columns(2),
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
        $appUrl = rtrim((string) config('app.url'), '/');
        $link = $appUrl . '/convite/' . $invite->token;

        $messageParts = [
            'Evento: ' . ($invite->event?->name ?? 'Evento'),
            'Acesse seu convite: ' . $link,
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
}
