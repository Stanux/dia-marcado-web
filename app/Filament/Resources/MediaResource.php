<?php

namespace App\Filament\Resources;

use App\Contracts\Site\MediaUploadServiceInterface;
use App\Filament\Resources\MediaResource\Pages;
use App\Models\Album;
use App\Models\SiteLayout;
use App\Models\SiteMedia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Filament Resource for managing Media files.
 * 
 * Allows uploading and managing media files within albums.
 * 
 * @Requirements: 6.1, 8.1, 8.3
 */
class MediaResource extends Resource
{
    protected static ?string $model = SiteMedia::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Mídia';

    protected static ?string $modelLabel = 'Mídia';

    protected static ?string $pluralModelLabel = 'Mídias';

    protected static ?int $navigationSort = 40;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Upload de Mídia')
                    ->schema([
                        Forms\Components\Select::make('album_id')
                            ->label('Álbum')
                            ->options(function () {
                                $user = auth()->user();
                                $wedding = $user?->currentWedding;
                                
                                if (!$wedding) {
                                    return [];
                                }

                                return Album::where('wedding_id', $wedding->id)
                                    ->with('albumType')
                                    ->get()
                                    ->mapWithKeys(fn ($album) => [
                                        $album->id => $album->albumType->name . ' - ' . $album->name
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Selecione o álbum para a mídia'),

                        Forms\Components\FileUpload::make('file')
                            ->label('Arquivo')
                            ->required()
                            ->image()
                            ->imageEditor()
                            ->maxSize(10240) // 10MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->directory('media-uploads')
                            ->visibility('public')
                            ->helperText('Formatos aceitos: JPG, PNG, GIF, WebP. Máximo 10MB.')
                            ->hiddenOn('edit'),
                    ]),

                Forms\Components\Section::make('Informações')
                    ->schema([
                        Forms\Components\TextInput::make('original_name')
                            ->label('Nome Original')
                            ->disabled(),

                        Forms\Components\TextInput::make('mime_type')
                            ->label('Tipo')
                            ->disabled(),

                        Forms\Components\TextInput::make('size')
                            ->label('Tamanho')
                            ->formatStateUsing(fn ($state) => $state ? round($state / 1024, 2) . ' KB' : '-')
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Preview')
                    ->disk('public')
                    ->width(60)
                    ->height(60),

                Tables\Columns\TextColumn::make('original_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('album.name')
                    ->label('Álbum')
                    ->sortable()
                    ->placeholder('Sem álbum'),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'image/') => 'success',
                        str_starts_with($state, 'video/') => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('size')
                    ->label('Tamanho')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $kb = $state / 1024;
                        if ($kb >= 1024) {
                            return round($kb / 1024, 2) . ' MB';
                        }
                        return round($kb, 2) . ' KB';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'pending' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('album_id')
                    ->label('Álbum')
                    ->options(function () {
                        $user = auth()->user();
                        $wedding = $user?->currentWedding;
                        
                        if (!$wedding) {
                            return [];
                        }

                        return Album::where('wedding_id', $wedding->id)
                            ->pluck('name', 'id');
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Concluído',
                        'processing' => 'Processando',
                        'pending' => 'Pendente',
                        'failed' => 'Falhou',
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
                $query->whereRaw('1 = 0');
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
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $wedding = $user->currentWedding;
        if (!$wedding) {
            return false;
        }

        $role = $user->roleIn($wedding);
        return in_array($role, ['couple', 'organizer']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
