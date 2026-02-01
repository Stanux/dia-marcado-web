<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlbumResource\Pages;
use App\Models\Album;
use App\Models\AlbumType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament Resource for managing Albums.
 * 
 * Accessible by users with wedding context.
 * Manages photo albums organized by type.
 * 
 * @Requirements: 2.2, 2.4, 2.6
 */
class AlbumResource extends Resource
{
    protected static ?string $model = Album::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Mídia';

    protected static ?string $modelLabel = 'Álbum';

    protected static ?string $pluralModelLabel = 'Álbuns';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Álbum')
                    ->schema([
                        Forms\Components\Select::make('album_type_id')
                            ->label('Tipo de Álbum')
                            ->required()
                            ->options(AlbumType::pluck('name', 'id'))
                            ->native(false)
                            ->helperText('Categoria do álbum'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
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

                Tables\Columns\TextColumn::make('albumType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pré-Casamento' => 'info',
                        'Pós-Casamento' => 'success',
                        'Uso no Site' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('media_count')
                    ->label('Mídias')
                    ->counts('media')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('album_type_id')
                    ->label('Tipo')
                    ->options(AlbumType::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Tem certeza que deseja excluir este álbum? As mídias associadas ficarão sem álbum.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if ($user && !$user->isAdmin()) {
            $wedding = $user->currentWedding;
            if ($wedding) {
                $query->where('wedding_id', $wedding->id);
            } else {
                $query->whereRaw('1 = 0'); // No results if no wedding context
            }
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlbums::route('/'),
            'create' => Pages\CreateAlbum::route('/create'),
            'edit' => Pages\EditAlbum::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Admin can always access
        if ($user->isAdmin()) {
            return true;
        }

        // Others need wedding context and couple/organizer role
        $wedding = $user->currentWedding;
        if (!$wedding) {
            return false;
        }

        $role = $user->roleIn($wedding);
        return in_array($role, ['couple', 'organizer']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
