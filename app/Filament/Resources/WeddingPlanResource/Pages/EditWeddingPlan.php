<?php

namespace App\Filament\Resources\WeddingPlanResource\Pages;

use App\Filament\Resources\WeddingPlanResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditWeddingPlan extends EditRecord
{
    protected static string $resource = WeddingPlanResource::class;

    public function getHeading(): string
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    #[On('topbar-wedding-plan-archive')]
    public function archiveFromTopbar(): void
    {
        $record = $this->getRecord();

        if ($record->isArchived()) {
            return;
        }

        $record->update([
            'archived_at' => now(),
            'archived_by' => auth()->id(),
        ]);

        Notification::make()
            ->success()
            ->title('Planejamento arquivado com sucesso.')
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }

    #[On('topbar-wedding-plan-unarchive')]
    public function unarchiveFromTopbar(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $record = $this->getRecord();

        if (! $record->isArchived()) {
            return;
        }

        $record->update([
            'archived_at' => null,
            'archived_by' => null,
        ]);

        Notification::make()
            ->success()
            ->title('Planejamento reativado com sucesso.')
            ->send();

        $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
    }
}
