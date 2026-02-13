<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestEventResource\Pages;
use App\Models\GuestEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class GuestEventResource extends WeddingScopedResource
{
    protected static ?string $model = GuestEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'CONVIDADOS';

    protected static ?string $navigationLabel = 'Eventos RSVP';

    protected static ?string $modelLabel = 'Evento RSVP';

    protected static ?string $pluralModelLabel = 'Eventos RSVP';

    protected static ?int $navigationSort = 3;

    protected static ?string $module = 'guests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Evento')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Identificador curto e único dentro do casamento.'),

                        Forms\Components\DateTimePicker::make('event_at')
                            ->label('Data do evento'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Perguntas do RSVP')
                    ->schema([
                        Forms\Components\Repeater::make('questions')
                            ->label('Perguntas')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Pergunta')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Select::make('type')
                                    ->label('Tipo')
                                    ->options([
                                        'text' => 'Texto',
                                        'textarea' => 'Texto longo',
                                        'select' => 'Seleção',
                                        'number' => 'Número',
                                    ])
                                    ->default('text')
                                    ->required()
                                    ->native(false),

                                Forms\Components\TagsInput::make('options')
                                    ->label('Opções')
                                    ->placeholder('Adicionar opções')
                                    ->helperText('Somente para perguntas do tipo seleção.'),

                                Forms\Components\Toggle::make('required')
                                    ->label('Obrigatória')
                                    ->inline(false)
                                    ->default(false),
                            ])
                            ->collapsed()
                            ->defaultItems(0),
                    ]),

                Forms\Components\Section::make('Regras e Configurações')
                    ->schema([
                        Forms\Components\KeyValue::make('rules')
                            ->label('Regras')
                            ->addButtonLabel('Adicionar regra')
                            ->keyLabel('Chave')
                            ->valueLabel('Valor'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Evento')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('event_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuestEvents::route('/'),
            'create' => Pages\CreateGuestEvent::route('/create'),
            'edit' => Pages\EditGuestEvent::route('/{record}/edit'),
        ];
    }

    public static function getSlug(): string
    {
        return 'guest-events';
    }
}
