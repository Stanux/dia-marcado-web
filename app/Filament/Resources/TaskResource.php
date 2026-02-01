<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

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

    protected static ?string $navigationGroup = 'Gestão';

    protected static ?string $module = 'tasks';

    protected static ?string $modelLabel = 'Tarefa';

    protected static ?string $pluralModelLabel = 'Tarefas';

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

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Data de Vencimento'),

                        Forms\Components\Select::make('assigned_to')
                            ->label('Responsável')
                            ->options(function () {
                                $user = auth()->user();
                                $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

                                if (!$weddingId) {
                                    return [];
                                }

                                return User::whereHas('weddings', function ($query) use ($weddingId) {
                                    $query->where('wedding_id', $weddingId);
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
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

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'in_progress' => 'Em Andamento',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

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
                        'pending' => 'Pendente',
                        'in_progress' => 'Em Andamento',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
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
            ->defaultSort('due_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
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
}
