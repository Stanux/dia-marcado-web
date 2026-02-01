<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanLimitResource\Pages;
use App\Models\PlanLimit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament Resource for managing Plan Limits.
 * 
 * Only accessible by Admin users.
 * Manages storage and file limits per subscription plan.
 * 
 * @Requirements: 4.1, 4.2
 */
class PlanLimitResource extends Resource
{
    protected static ?string $model = PlanLimit::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Mídia';

    protected static ?string $modelLabel = 'Limite de Plano';

    protected static ?string $pluralModelLabel = 'Limites de Plano';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Plano')
                    ->schema([
                        Forms\Components\TextInput::make('plan_slug')
                            ->label('Identificador do Plano')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->helperText('Ex: basic, premium, enterprise')
                            ->regex('/^[a-z0-9_-]+$/')
                            ->validationMessages([
                                'regex' => 'Use apenas letras minúsculas, números, hífens e underscores.',
                            ]),

                        Forms\Components\TextInput::make('max_files')
                            ->label('Máximo de Arquivos')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100000)
                            ->default(100)
                            ->helperText('Número máximo de arquivos permitidos'),

                        Forms\Components\TextInput::make('max_storage_mb')
                            ->label('Armazenamento Máximo (MB)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(102400) // 100GB
                            ->default(500)
                            ->helperText('Espaço máximo em megabytes')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state(round($record->max_storage_bytes / 1024 / 1024));
                                }
                            })
                            ->dehydrateStateUsing(fn ($state) => null), // Don't save this field directly
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan_slug')
                    ->label('Plano')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'gray',
                        'premium' => 'success',
                        'enterprise' => 'warning',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('max_files')
                    ->label('Máx. Arquivos')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_storage_bytes')
                    ->label('Armazenamento')
                    ->formatStateUsing(function ($state) {
                        $mb = $state / 1024 / 1024;
                        if ($mb >= 1024) {
                            return round($mb / 1024, 1) . ' GB';
                        }
                        return round($mb) . ' MB';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('plan_slug', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlanLimits::route('/'),
            'create' => Pages\CreatePlanLimit::route('/create'),
            'edit' => Pages\EditPlanLimit::route('/{record}/edit'),
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

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
