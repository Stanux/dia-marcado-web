<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestResource\Pages;
use App\Filament\Resources\GuestResource\RelationManagers\RsvpsRelationManager;
use App\Models\Guest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuestResource extends WeddingScopedResource
{
    protected static ?string $model = Guest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'CONVIDADOS';

    protected static ?string $navigationLabel = 'Convidados';

    protected static ?string $modelLabel = 'Convidado';

    protected static ?string $pluralModelLabel = 'Convidados';

    protected static ?int $navigationSort = 2;

    protected static ?string $module = 'guests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Convidado')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(fn ($state) => trim((string) $state))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nickname')
                            ->label('Apelido')
                            ->maxLength(100),

                        Forms\Components\Select::make('household_id')
                            ->label('Núcleo')
                            ->relationship('household', 'name', function (Builder $query) {
                                $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
                                if ($weddingId) {
                                    $query->where('wedding_id', $weddingId);
                                }

                                return $query->orderBy('name');
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')
                                    ->label('Telefone')
                                    ->maxLength(30),
                            ]),

                        Forms\Components\Select::make('role_in_household')
                            ->label('Papel no núcleo')
                            ->options([
                                'head' => 'Responsável',
                                'spouse' => 'Cônjuge',
                                'child' => 'Filho(a)',
                                'plus_one' => 'Acompanhante',
                                'other' => 'Outro',
                            ])
                            ->native(false),

                        Forms\Components\Toggle::make('is_child')
                            ->label('Criança')
                            ->inline(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pendente',
                                'confirmed' => 'Confirmado',
                                'declined' => 'Recusado',
                                'maybe' => 'Talvez',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('category')
                            ->label('Categoria')
                            ->maxLength(50),

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

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags'),

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
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('household.name')
                    ->label('Núcleo')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'declined' => 'Recusado',
                        'maybe' => 'Talvez',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'declined' => 'danger',
                        'maybe' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('side')
                    ->label('Lado')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bride' => 'Noiva',
                        'groom' => 'Noivo',
                        'both' => 'Ambos',
                        'other' => 'Outro',
                        default => 'Não definido',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_child')
                    ->label('Criança')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmado',
                        'declined' => 'Recusado',
                        'maybe' => 'Talvez',
                    ]),

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
            RsvpsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
            'import' => Pages\ImportGuests::route('/import'),
        ];
    }

    public static function getSlug(): string
    {
        return 'guests';
    }
}
