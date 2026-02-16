<?php

namespace App\Filament\Resources\WeddingPlanResource\RelationManagers;

use App\Models\TaskCategory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tarefas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->rows(3)
                    ->maxLength(65535),

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
                        $weddingId = $this->getOwnerRecord()?->wedding_id;

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
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Data Limite')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
                    ->placeholder('Não atribuído'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function canCreate(): bool
    {
        $plan = $this->getOwnerRecord();

        if ($plan?->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return parent::canCreate();
    }

    public function canEdit(Model $record): bool
    {
        $plan = $this->getOwnerRecord();

        if ($plan?->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return parent::canEdit($record);
    }

    public function canDelete(Model $record): bool
    {
        $plan = $this->getOwnerRecord();

        if ($plan?->isArchived() && !auth()->user()?->isAdmin()) {
            return false;
        }

        return parent::canDelete($record);
    }
}
