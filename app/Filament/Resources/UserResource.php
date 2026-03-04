<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Wedding;
use App\Services\PermissionManagementService;
use App\Services\PermissionService;
use App\Services\UserManagementService;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Filament Resource for managing Users within a wedding context.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?string $navigationGroup = null;

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    protected static ?int $navigationSort = 12;

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
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                if (static::shouldLinkExistingOrganizer($get('email'), $get('pivot_role'))) {
                                    $set('password', null);
                                }
                            })
                            ->rule(function (Get $get, ?User $record, string $operation) {
                                return function (string $attribute, mixed $value, Closure $fail) use ($get, $record, $operation): void {
                                    $existingUser = static::findUserByEmail(is_string($value) ? $value : null);

                                    if (! $existingUser) {
                                        return;
                                    }

                                    if ($operation === 'edit' && $record && $existingUser->is($record)) {
                                        return;
                                    }

                                    if (
                                        $operation === 'create'
                                        && static::shouldLinkExistingOrganizer(
                                            is_string($value) ? $value : null,
                                            $get('pivot_role')
                                        )
                                    ) {
                                        return;
                                    }

                                    $fail('Este email já está em uso.');
                                };
                            }),

                        Forms\Components\Select::make('pivot_role')
                            ->label('Tipo')
                            ->options(fn (): array => static::getPivotRoleOptionsForCurrentUser())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $set('permissions', static::getDefaultPermissionsForRole($state));
                            }),

                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->disabled(function (Get $get, string $operation): bool {
                                return $operation === 'create'
                                    && static::shouldLinkExistingOrganizer($get('email'), $get('pivot_role'));
                            })
                            ->dehydrated(function ($state, Get $get, string $operation): bool {
                                if ($operation !== 'create') {
                                    return filled($state);
                                }

                                return ! static::shouldLinkExistingOrganizer(
                                    $get('email'),
                                    $get('pivot_role')
                                ) && filled($state);
                            })
                            ->required(function (Get $get, string $operation): bool {
                                if ($operation !== 'create') {
                                    return false;
                                }

                                return ! static::shouldLinkExistingOrganizer(
                                    $get('email'),
                                    $get('pivot_role')
                                );
                            })
                            ->helperText(function (Get $get, string $operation): ?string {
                                if (
                                    $operation === 'create'
                                    && static::shouldLinkExistingOrganizer(
                                        $get('email'),
                                        $get('pivot_role')
                                    )
                                ) {
                                    return 'Conta já existe na plataforma, portanto não é necessário definir uma senha. O organizador será vinculado a este casamento.';
                                }

                                return null;
                            })
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Permissões')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Módulos com Acesso')
                            ->options(PermissionManagementService::AVAILABLE_MODULES)
                            ->columns(2)
                            ->disabled(fn (Get $get): bool => $get('pivot_role') === 'guest')
                            ->disableOptionWhen(
                                fn (string $value, Get $get): bool => $get('pivot_role') === 'guest'
                                    || ($get('pivot_role') === 'couple' && $value === 'users')
                            ),
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
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar este usuário'),
                Tables\Actions\Action::make('manage_permissions')
                    ->label('Permissões')
                    ->icon('heroicon-o-key')
                    ->iconButton()
                    ->tooltip('Gerenciar permissões do usuário')
                    ->visible(function (User $record): bool {
                        $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
                        $wedding = $record->weddings->firstWhere('id', $weddingId);
                        return $wedding?->pivot?->role === 'organizer';
                    })
                    ->url(fn (User $record): string => route('filament.admin.resources.users.permissions', $record)),
                Tables\Actions\Action::make('detach_from_wedding')
                    ->label('Remover')
                    ->icon('heroicon-o-trash')
                    ->iconButton()
                    ->tooltip('Remover usuário deste casamento')
                    ->requiresConfirmation()
                    ->modalHeading('Remover usuário do casamento')
                    ->modalDescription('O usuário será apenas desvinculado deste casamento. A conta continuará existindo no sistema.')
                    ->action(function (User $record): void {
                        abort_unless(static::canDelete($record), 403);

                        static::detachUserFromCurrentWedding($record);

                        Notification::make()
                            ->success()
                            ->title('Usuário removido deste casamento.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('detach_selected_from_wedding')
                        ->label('Remover selecionados do casamento')
                        ->icon('heroicon-o-user-minus')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $detachedCount = 0;

                            foreach ($records as $record) {
                                if (! $record instanceof User) {
                                    continue;
                                }

                                abort_unless(static::canDelete($record), 403);
                                static::detachUserFromCurrentWedding($record);
                                $detachedCount++;
                            }

                            Notification::make()
                                ->success()
                                ->title("{$detachedCount} usuário(s) removido(s) deste casamento.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
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
        $currentWedding = Wedding::find($weddingId);

        if ($user && $currentWedding && $user->isOrganizerIn($currentWedding)) {
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

    public static function getPivotRoleOptionsForCurrentUser(): array
    {
        $user = auth()->user();
        $wedding = static::getCurrentWeddingFromContext();

        if (!$user) {
            return [];
        }

        // Admin and Couple can create/manage couple, organizer and guest
        if ($user->isAdmin() || ($wedding && $user->isCoupleIn($wedding))) {
            return [
                'couple' => 'Noivo(a)',
                'organizer' => 'Organizador',
                'guest' => 'Convidado',
            ];
        }

        // Organizer with permission can only create/manage guests
        if ($wedding && $user->isOrganizerIn($wedding) && $user->hasPermissionIn($wedding, 'users')) {
            return [
                'guest' => 'Convidado',
            ];
        }

        return [];
    }

    public static function getDefaultPermissionsForRole(?string $role): array
    {
        return match ($role) {
            'couple' => array_keys(PermissionManagementService::AVAILABLE_MODULES),
            'guest' => ['app'],
            default => [],
        };
    }

    public static function sanitizePermissionsForRole(?string $role, array $permissions): array
    {
        $validModules = array_keys(PermissionManagementService::AVAILABLE_MODULES);
        $normalizedPermissions = PermissionService::normalizePermissions($permissions);
        $filteredPermissions = array_values(array_filter(
            $normalizedPermissions,
            fn ($permission): bool => in_array($permission, $validModules, true)
        ));

        return match ($role) {
            'couple' => static::getDefaultPermissionsForRole('couple'),
            'guest' => static::getDefaultPermissionsForRole('guest'),
            default => $filteredPermissions,
        };
    }

    public static function getCurrentWeddingFromContext(): ?Wedding
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        if (!$weddingId) {
            return null;
        }

        return Wedding::find($weddingId);
    }

    public static function detachUserFromCurrentWedding(User $record): void
    {
        $wedding = static::getCurrentWeddingFromContext();

        if (! $wedding instanceof Wedding) {
            abort(422, 'Contexto de casamento não encontrado para remover usuário.');
        }

        app(UserManagementService::class)->removeFromWedding($record, $wedding);
    }

    public static function shouldLinkExistingOrganizer(?string $email, ?string $pivotRole): bool
    {
        if ($pivotRole !== 'organizer') {
            return false;
        }

        $existingUser = static::findUserByEmail($email);

        return $existingUser?->role === 'organizer';
    }

    private static function findUserByEmail(?string $email): ?User
    {
        $normalizedEmail = Str::lower(trim((string) $email));

        if ($normalizedEmail === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();
    }
}
