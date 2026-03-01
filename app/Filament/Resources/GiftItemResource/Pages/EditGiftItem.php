<?php

namespace App\Filament\Resources\GiftItemResource\Pages;

use App\Filament\Resources\GiftItemResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditGiftItem extends EditRecord
{
    protected static string $resource = GiftItemResource::class;

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    #[On('topbar-gift-restore')]
    public function openRestoreModalFromTopbar(): void
    {
        $this->mountAction('restore');
    }

    #[On('topbar-gift-delete')]
    public function openDeleteModalFromTopbar(): void
    {
        abort_unless(static::getResource()::canDelete($this->getRecord()), 403);

        $this->mountAction('delete');
    }

    protected function restoreAction(): Actions\Action
    {
        return Actions\Action::make('restore')
            ->label('Restaurar')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Restaurar valores originais')
            ->modalDescription('Tem certeza que deseja restaurar este item aos valores originais? As alterações atuais serão perdidas.')
            ->action(function (): void {
                $this->record->restoreOriginal();
                $this->refreshFormData([
                    'name',
                    'description',
                    'price',
                    'quantity_available',
                ]);

                Notification::make()
                    ->title('Item restaurado')
                    ->success()
                    ->body('O item foi restaurado aos valores originais.')
                    ->send();
            });
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
