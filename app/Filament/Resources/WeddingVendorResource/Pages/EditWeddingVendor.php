<?php

namespace App\Filament\Resources\WeddingVendorResource\Pages;

use Filament\Actions;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\WeddingVendorResource;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditWeddingVendor extends EditRecord
{
    protected static string $resource = WeddingVendorResource::class;

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

    #[On('topbar-wedding-vendor-delete')]
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
