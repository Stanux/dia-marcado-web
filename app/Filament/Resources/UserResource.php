<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\PermissionManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament Resource for managing Users within a wedding context.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Gestão';

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Usuário')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        Forms\Components\Select::make('pivot_role')
                            ->label('Tipo')
                            ->options(function () {
                                $user = auth()->user();
                                $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
                                $wedding = $weddingId ? \App\Models\Wedding::find($weddingId) : null;

                                // Admin and Couple can create couple, organizer and guest
                                if ($user->isAdmin() || ($wedding && $user->isCoupleIn($wedding))) {
                                    return [
                                        'couple' => 'Noivo(a)',
                                        'organizer' => 'Organizador',
                                        'guest' => 'Convidado',
                                    ];
                                }

                                // Organizer with permission can only create guests
                                if ($wedding && $user->isOrganizerIn($wedding) && $user->hasPermissionIn($wedding, 'users')) {
                                    return [
                                        'guest' => 'Convidado',
                                    ];
                                }

                                return [];
                            })
                            ->required()
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Permissões')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Módulos com Acesso')
                            ->options(PermissionManagementService::AVAILABLE_MODULES)
                            ->columns(2)
                            ->visible(fn ($get) => $get('pivot_role') === 'organizer'),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'create'),
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

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('wedding_role')
                    ->label('Tipo')
                    ->badge()
                    ->getStateUsing(function (User $record): ?string {
                        $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
                        $wedding = $record->weddings->firstWhere('id', $weddingId);
                        return $wedding?->pivot?->role;
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'couple' => 'success',
                        'organizer' => 'info',
                        'guest' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'couple' => 'Noivos',
                        'organizer' => 'Organizador',
                        'guest' => 'Convidado',
                        default => $state ?? '-',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pivot_role')
                    ->label('Tipo')
                    ->options([
                        'organizer' => 'Organizador',
                        'guest' => 'Convidado',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['value'])) {
                            return $query->whereHas('weddings', function ($q) use ($data) {
                                $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
                                $q->where('wedding_id', $weddingId)
                                  ->where('wedding_user.role', $data['value']);
                            });
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manage_permissions')
                    ->label('Permissões')
                    ->icon('heroicon-o-key')
                    ->visible(function (User $record): bool {
                        $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
                        $wedding = $record->weddings->firstWhere('id', $weddingId);
                        return $wedding?->pivot?->role === 'organizer';
                    })
                    ->url(fn (User $record): string => route('filament.admin.resources.users.permissions', $record)),
                Tables\Actions\DeleteAction::make()
                    ->label('Remover')
                    ->modalHeading('Remover usuário do casamento')
                    ->modalDescription('O usuário será removido deste casamento, mas sua conta continuará existindo.'),
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
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        if (!$weddingId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $query = parent::getEloquentQuery()
            ->whereHas('weddings', function ($q) use ($weddingId) {
                $q->where('wedding_id', $weddingId);
            })
            ->with(['weddings' => function ($q) use ($weddingId) {
                $q->where('wedding_id', $weddingId);
            }]);

        // Organizer can only see guests
        if ($user && $user->isOrganizerIn($user->currentWedding)) {
            $query->whereHas('weddings', function ($q) use ($weddingId) {
                $q->where('wedding_id', $weddingId)
                  ->where('wedding_user.role', 'guest');
            });
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'permissions' => Pages\ManagePermissions::route('/{record}/permissions'),
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

        $weddingId = $user->current_wedding_id ?? session('filament_wedding_id');
        if (!$weddingId) {
            return false;
        }

        $wedding = \App\Models\Wedding::find($weddingId);
        if (!$wedding) {
            return false;
        }

        return $user->hasPermissionIn($wedding, 'users');
    }
}
