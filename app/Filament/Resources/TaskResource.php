<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers\TaskBudgetsRelationManager;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingPlan;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament Resource for managing Tasks.
 * 
 * Extends WeddingScopedResource to automatically:
 * - Filter tasks by the current wedding context
 * - Verify user has 'tasks' module permission
 * - Inject wedding_id when creating new tasks
 */
class TaskResource extends WeddingScopedResource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Plano de Tarefas';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $module = 'tasks';

    protected static ?string $modelLabel = 'Tarefa';

    protected static ?string $pluralModelLabel = 'Tarefas';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes da Tarefa')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->maxLength(65535),

                        Forms\Components\Select::make('wedding_plan_id')
                            ->label('Planejamento')
                            ->required()
                            ->options(function () {
                                $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');

                                if (!$weddingId) {
                                    return [];
                                }

                                $query = WeddingPlan::query()->where('wedding_id', $weddingId);

                                if (!auth()->user()?->isAdmin()) {
                                    $query->whereNull('archived_at');
                                }

                                return $query->orderBy('title')->pluck('title', 'id');
                            })
                            ->searchable(),

                        Forms\Components\Select::make('task_category_id')
                            ->label('Categoria')
                            ->options(TaskCategory::orderBy('sort')->pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pendente',
                                'in_progress' => 'Em Andamento',
                                'completed' => 'Concluída',
                                'cancelled' => 'Cancelada',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data de Início')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->beforeOrEqual('due_date'),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Data Limite')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->afterOrEqual('start_date'),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridade')
                            ->options([
                                'low' => 'Baixa',
                                'medium' => 'Média',
                                'high' => 'Alta',
                            ])
                            ->default('medium')
                            ->required(),

                        Forms\Components\Select::make('assigned_to')
                            ->label('Responsável')
                            ->options(function () {
                                $user = auth()->user();
                                $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

                                if (!$weddingId) {
                                    return [];
                                }

                                return User::whereHas('weddings', function (Builder $query) use ($weddingId) {
                                    $query->where('wedding_id', $weddingId)
                                        ->whereIn('wedding_user.role', ['couple', 'organizer']);
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('estimated_value')
                            ->label('Valor Estimado')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('R$'),

                        Forms\Components\TextInput::make('actual_value')
                            ->label('Valor Real')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('R$')
                            ->visible(fn (Get $get) => $get('status') === 'completed')
                            ->required(fn (Get $get) => $get('status') === 'completed'),

                        Forms\Components\DatePicker::make('executed_at')
                            ->label('Data de Execução')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->visible(fn (Get $get) => $get('status') === 'completed')
                            ->required(fn (Get $get) => $get('status') === 'completed'),
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

                Tables\Columns\TextColumn::make('plan.title')
                    ->label('Planejamento')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('effective_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'overdue' => 'danger',
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'overdue' => 'Atrasada',
                        'pending' => 'Pendente',
                        'in_progress' => 'Em Andamento',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Data Limite')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('Estimado')
                    ->money('BRL', true)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('actual_value')
                    ->label('Real')
                    ->money('BRL', true)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
                    ->placeholder('Não atribuído'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'overdue' => 'Atrasada',
                        'pending' => 'Pendente',
                        'in_progress' => 'Em Andamento',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (!$value) {
                            return $query;
                        }

                        if ($value !== 'overdue') {
                            return $query->where('status', $value);
                        }

                        $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');
                        $timezone = config('app.timezone');

                        if ($weddingId) {
                            $wedding = Wedding::find($weddingId);
                            $timezone = $wedding?->getSetting('timezone', $timezone) ?? $timezone;
                        }

                        $today = now($timezone)->toDateString();

                        return $query
                            ->whereDate('due_date', '<', $today)
                            ->whereNotIn('status', ['completed', 'cancelled']);
                    }),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Responsável')
                    ->options(function () {
                        $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');

                        if (!$weddingId) {
                            return [];
                        }

                        return User::whereHas('weddings', function (Builder $query) use ($weddingId) {
                            $query->where('wedding_id', $weddingId)
                                ->whereIn('wedding_user.role', ['couple', 'organizer']);
                        })->pluck('name', 'id');
                    }),

                Tables\Filters\SelectFilter::make('task_category_id')
                    ->label('Categoria')
                    ->options(TaskCategory::orderBy('sort')->pluck('name', 'id')),

                Tables\Filters\Filter::make('due_date_range')
                    ->label('Faixa de Data')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('De'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '>=', $date))
                            ->when($data['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('due_date', '<=', $date));
                    }),

                Tables\Filters\Filter::make('value_range')
                    ->label('Faixa de Valor')
                    ->form([
                        Forms\Components\TextInput::make('min')
                            ->label('Mínimo')
                            ->numeric(),
                        Forms\Components\TextInput::make('max')
                            ->label('Máximo')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min'] ?? null, fn (Builder $q, $value) => $q->where('estimated_value', '>=', $value))
                            ->when($data['max'] ?? null, fn (Builder $q, $value) => $q->where('estimated_value', '<=', $value));
                    }),
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
            ->defaultSort('due_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            TaskBudgetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['plan', 'category', 'assignedUser', 'wedding']);
    }

    public static function getSlug(): string
    {
        return 'plan-tasks';
    }

    public static function canEdit(Model $record): bool
    {
        if (!parent::canEdit($record)) {
            return false;
        }

        if ($record->plan && $record->plan->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return true;
    }

    public static function canDelete(Model $record): bool
    {
        if (!parent::canDelete($record)) {
            return false;
        }

        if ($record->plan && $record->plan->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return true;
    }
}
