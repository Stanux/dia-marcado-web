<?php

namespace App\Filament\Resources;

use App\Filament\Pages\PlanningDashboard;
use App\Filament\Resources\WeddingPlanResource\Pages;
use App\Filament\Resources\WeddingPlanResource\RelationManagers\TasksRelationManager;
use App\Models\WeddingPlan;
use App\Services\Planning\WeddingPlanService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WeddingPlanResource extends WeddingScopedResource
{
    protected static ?string $model = WeddingPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Planejamentos';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $module = 'tasks';

    protected static ?string $modelLabel = 'Planejamento';

    protected static ?string $pluralModelLabel = 'Planejamentos';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do Planejamento')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('total_budget')
                            ->label('Orçamento Total')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('R$'),

                        Forms\Components\Placeholder::make('archived_at')
                            ->label('Arquivado em')
                            ->content(fn (?WeddingPlan $record) => $record?->archived_at?->format('d/m/Y H:i') ?? '—')
                            ->visible(fn (?WeddingPlan $record) => (bool) $record?->archived_at),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_budget')
                    ->label('Orçamento Total')
                    ->money('BRL', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('archived_at')
                    ->label('Arquivado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Ativo')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('dashboard')
                    ->label('Dashboard')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('gray')
                    ->url(fn (WeddingPlan $record): string => PlanningDashboard::getUrl(['plan' => $record->getKey()]))
                    ->visible(fn (): bool => PlanningDashboard::canAccess()),
                Tables\Actions\EditAction::make()
                    ->disabled(fn (WeddingPlan $record): bool => $record->isArchived() && !auth()->user()?->isAdmin()),
                Tables\Actions\Action::make('archive')
                    ->label('Arquivar')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (WeddingPlan $record): bool => !$record->isArchived())
                    ->requiresConfirmation()
                    ->action(function (WeddingPlan $record): void {
                        $record->update([
                            'archived_at' => now(),
                            'archived_by' => auth()->id(),
                        ]);
                    }),
                Tables\Actions\Action::make('unarchive')
                    ->label('Reativar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (WeddingPlan $record): bool => $record->isArchived() && auth()->user()?->isAdmin())
                    ->requiresConfirmation()
                    ->action(fn (WeddingPlan $record) => $record->update([
                        'archived_at' => null,
                        'archived_by' => null,
                    ])),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-square-2-stack')
                    ->action(function (WeddingPlan $record): void {
                        app(WeddingPlanService::class)->duplicate($record);
                    }),
                Tables\Actions\Action::make('export')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (WeddingPlan $record) => route('planning.export', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeddingPlans::route('/'),
            'create' => Pages\CreateWeddingPlan::route('/create'),
            'edit' => Pages\EditWeddingPlan::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        if (!parent::canEdit($record)) {
            return false;
        }

        if ($record->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return true;
    }
}
