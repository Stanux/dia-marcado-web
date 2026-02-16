<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemConfigResource\Pages;
use App\Models\SystemConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament Resource for managing System Configurations.
 * 
 * Only accessible by Admin users.
 * Manages site.* and guests.* configuration keys.
 * 
 * @Requirements: 21.1, 21.5
 */
class SystemConfigResource extends Resource
{
    protected static ?string $model = SystemConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?string $modelLabel = 'Configuração';

    protected static ?string $pluralModelLabel = 'Configurações do Sistema';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuração')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Chave')
                            ->required()
                            ->disabled()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(2)
                            ->disabled(),

                        Forms\Components\KeyValue::make('value')
                            ->label('Valor')
                            ->visible(fn ($record) => is_array($record?->value ?? null) && !is_numeric(array_key_first($record?->value ?? [])))
                            ->reorderable()
                            ->addActionLabel('Adicionar item'),

                        Forms\Components\TagsInput::make('value_array')
                            ->label('Valores')
                            ->visible(fn ($record) => is_array($record?->value ?? null) && is_numeric(array_key_first($record?->value ?? [])))
                            ->placeholder('Adicionar valor')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && is_array($record->value) && is_numeric(array_key_first($record->value))) {
                                    $component->state($record->value);
                                }
                            }),

                        Forms\Components\TextInput::make('value_simple')
                            ->label('Valor')
                            ->visible(fn ($record) => !is_array($record?->value ?? null))
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && !is_array($record->value)) {
                                    $component->state((string) $record->value);
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Chave')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Chave copiada!'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            if (count($state) > 3) {
                                return json_encode(array_slice($state, 0, 3)) . '...';
                            }
                            return json_encode($state);
                        }
                        return (string) $state;
                    })
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->wrap()
                    ->limit(100)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('site_configs')
                    ->label('Configurações de Site')
                    ->query(fn (Builder $query): Builder => $query->where('key', 'like', 'site.%'))
                    ->default(),
                Tables\Filters\Filter::make('planning_configs')
                    ->label('Configurações de Planejamento')
                    ->query(fn (Builder $query): Builder => $query->where('key', 'like', 'planning.%')),
                Tables\Filters\Filter::make('guest_configs')
                    ->label('Configurações de Convidados')
                    ->query(fn (Builder $query): Builder => $query->where('key', 'like', 'guests.%')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for system configs
            ])
            ->defaultSort('key', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query): Builder {
                return $query
                    ->where('key', 'like', 'site.%')
                    ->orWhere('key', 'like', 'planning.%')
                    ->orWhere('key', 'like', 'guests.%');
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemConfigs::route('/'),
            'edit' => Pages\EditSystemConfig::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return false; // Configs are created via seeder
    }

    public static function canDelete($record): bool
    {
        return false; // Configs should not be deleted
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
