<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestHouseholdResource\Pages;
use App\Filament\Resources\GuestHouseholdResource\RelationManagers\GuestsRelationManager;
use App\Filament\Resources\GuestHouseholdResource\RelationManagers\InvitesRelationManager;
use App\Models\Guest;
use App\Models\GuestHousehold;
use App\Services\GuestInviteNotificationService;
use App\Services\Guests\InviteManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class GuestHouseholdResource extends WeddingScopedResource
{
    protected static ?string $model = GuestHousehold::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'CONVIDADOS';

    protected static ?string $navigationLabel = 'Núcleos';

    protected static ?string $modelLabel = 'Núcleo';

    protected static ?string $pluralModelLabel = 'Núcleos';

    protected static ?int $navigationSort = 1;

    protected static ?string $module = 'guests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Núcleo')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Núcleo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->maxLength(20)
                            ->helperText('Código curto para identificação rápida.'),

                        Forms\Components\Select::make('side')
                            ->label('Lado')
                            ->options([
                                'bride' => 'Noiva',
                                'groom' => 'Noivo',
                                'both' => 'Ambos',
                                'other' => 'Outro',
                            ])
                            ->native(false)
                            ->placeholder('Selecione'),

                        Forms\Components\TextInput::make('category')
                            ->label('Categoria')
                            ->maxLength(50)
                            ->placeholder('Ex: família, padrinhos, amigos'),

                        Forms\Components\TextInput::make('priority')
                            ->label('Prioridade')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('quota_adults')
                                    ->label('Cota Adultos')
                                    ->numeric()
                                    ->minValue(0),

                                Forms\Components\TextInput::make('quota_children')
                                    ->label('Cota Crianças')
                                    ->numeric()
                                    ->minValue(0),

                                Forms\Components\Toggle::make('plus_one_allowed')
                                    ->label('Plus One')
                                    ->inline(false),
                            ]),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Adicionar tags'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3)
                            ->maxLength(2000),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Núcleo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('side')
                    ->label('Lado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bride' => 'Noiva',
                        'groom' => 'Noivo',
                        'both' => 'Ambos',
                        'other' => 'Outro',
                        default => 'Não definido',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'bride' => 'info',
                        'groom' => 'warning',
                        'both' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('guests_count')
                    ->label('Convidados')
                    ->counts('guests')
                    ->sortable(),

                Tables\Columns\TextColumn::make('invites_count')
                    ->label('Convites')
                    ->counts('invites')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('plus_one_allowed')
                    ->label('Plus One')
                    ->boolean(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('side')
                    ->label('Lado')
                    ->options([
                        'bride' => 'Noiva',
                        'groom' => 'Noivo',
                        'both' => 'Ambos',
                        'other' => 'Outro',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('invite')
                    ->label('Gerar convite')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('guest_id')
                            ->label('Convidado específico (opcional)')
                            ->options(fn (GuestHousehold $record): array => Guest::query()
                                ->where('wedding_id', $record->wedding_id)
                                ->where('household_id', $record->id)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->native(false),
                        Forms\Components\Select::make('channel')
                            ->label('Canal')
                            ->options([
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                                'sms' => 'SMS',
                            ])
                            ->default('email')
                            ->required(),
                        Forms\Components\TextInput::make('expires_in_days')
                            ->label('Expira em (dias)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->helperText('Deixe em branco para não expirar.'),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Máximo de usos')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->helperText('Deixe em branco para uso ilimitado.'),
                    ])
                    ->action(function (GuestHousehold $record, array $data) {
                        try {
                            $invite = app(InviteManagementService::class)->createForHousehold(
                                household: $record,
                                data: $data,
                                actorId: auth()->id(),
                            );
                        } catch (\InvalidArgumentException $exception) {
                            Notification::make()
                                ->title('Erro ao gerar convite')
                                ->danger()
                                ->body($exception->getMessage())
                                ->send();
                            return;
                        }

                        $siteSlug = $record->wedding?->siteLayout?->slug;
                        $link = $siteSlug ? url('/site/' . $siteSlug . '?token=' . $invite->token) : null;

                        $result = app(GuestInviteNotificationService::class)->send($invite);
                        if (!$result['ok']) {
                            Notification::make()
                                ->title('Convite gerado, mas não enviado')
                                ->danger()
                                ->body($result['message'])
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Convite gerado')
                            ->success()
                            ->body($link ? ('Link: ' . $link) : ('Token: ' . $invite->token))
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            GuestsRelationManager::class,
            InvitesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuestHouseholds::route('/'),
            'create' => Pages\CreateGuestHousehold::route('/create'),
            'edit' => Pages\EditGuestHousehold::route('/{record}/edit'),
        ];
    }

    public static function getSlug(): string
    {
        return 'guest-households';
    }
}
