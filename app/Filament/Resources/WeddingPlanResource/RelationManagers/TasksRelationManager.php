<?php

namespace App\Filament\Resources\WeddingPlanResource\RelationManagers;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tarefas';

    protected static string $view = 'filament.resources.wedding-plan-resource.relation-managers.tasks-relation-manager';

    public bool $isTimelineView = false;

    public function table(Table $table): Table
    {
        return $table
            ->columns(TaskResource::taskTableColumns(includePlanColumn: false))
            ->filters(TaskResource::taskTableFilters(
                resolveWeddingId: fn (): ?string => $this->getOwnerRecord()?->wedding_id,
            ))
            ->actions([
                Tables\Actions\Action::make('edit_full')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Model $record): string => TaskResource::getUrl('edit', [
                        'record' => $record,
                        'return_to_plan' => $this->getOwnerRecord()?->getKey(),
                    ]))
                    ->visible(fn (Model $record): bool => $this->canEdit($record)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public function showTimelineView(): void
    {
        $this->isTimelineView = true;
    }

    public function showTableView(): void
    {
        $this->isTimelineView = false;
    }

    public function getTimelineTasks(): Collection
    {
        return Task::query()
            ->where('wedding_plan_id', $this->getOwnerRecord()->getKey())
            ->with('category')
            ->orderBy('start_date')
            ->orderBy('due_date')
            ->get();
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
