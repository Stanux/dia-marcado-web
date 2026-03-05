<?php

namespace App\Filament\Resources\WeddingGuestResource\Pages;

use App\Filament\Resources\WeddingGuestResource;
use App\Filament\Resources\WeddingGuestResource\Pages\Concerns\ValidatesWeddingGuestDuplicates;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditWeddingGuest extends EditRecord
{
    use ValidatesWeddingGuestDuplicates;

    protected static string $resource = WeddingGuestResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateGuestDuplicateInWedding($data, (string) $this->record->getKey());

        return $data;
    }

    #[On('topbar-wedding-guest-delete')]
    public function openDeleteModalFromTopbar(): void
    {
        abort_unless(static::getResource()::canDelete($this->getRecord()), 403);

        $this->mountAction('delete');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        parent::configureDeleteAction($action);

        $action->successRedirectUrl(fn (): string => $this->getResource()::getUrl('index'));
    }

    protected function deleteAction(): DeleteAction
    {
        return Actions\DeleteAction::make();
    }
}
