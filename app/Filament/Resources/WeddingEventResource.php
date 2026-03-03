<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeddingEventResource\Pages;
use App\Models\WeddingEvent;
use Carbon\CarbonInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class WeddingEventResource extends WeddingScopedResource
{
    protected static ?string $model = WeddingEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Eventos';

    protected static ?string $navigationGroup = null;

    protected static ?string $module = 'guests';

    protected static ?string $modelLabel = 'Evento';

    protected static ?string $pluralModelLabel = 'Eventos';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Evento')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('event_type')
                            ->label('Tipo')
                            ->options([
                                'open' => 'Aberto',
                                'closed' => 'Fechado',
                            ])
                            ->default('open')
                            ->required()
                            ->native(false),

                        Forms\Components\DateTimePicker::make('event_datetime')
                            ->label('Data e Hora')
                            ->seconds(false)
                            ->afterStateHydrated(function (Forms\Components\DateTimePicker $component, ?WeddingEvent $record): void {
                                if (! $record?->event_date) {
                                    return;
                                }

                                $date = $record->event_date instanceof CarbonInterface
                                    ? $record->event_date->format('Y-m-d')
                                    : (string) $record->event_date;
                                $time = filled($record->event_time)
                                    ? substr((string) $record->event_time, 0, 8)
                                    : '00:00:00';

                                $component->state("{$date} {$time}");
                            }),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->inline(false)
                            ->default(true),

                        Forms\Components\Textarea::make('instructions')
                            ->label('Instruções')
                            ->rows(4)
                            ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_time')
                    ->label('Hora')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'closed' ? 'Fechado' : 'Aberto')
                    ->color(fn (string $state): string => $state === 'closed' ? 'warning' : 'success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('event_date');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeddingEvents::route('/'),
            'create' => Pages\CreateWeddingEvent::route('/create'),
            'edit' => Pages\EditWeddingEvent::route('/{record}/edit'),
        ];
    }

    public static function getSlug(): string
    {
        return 'events';
    }
}
