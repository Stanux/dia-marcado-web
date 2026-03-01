<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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

    #[On('topbar-user-delete')]
    public function deleteFromTopbar(): void
    {
        abort_unless(static::getResource()::canDelete($this->getRecord()), 403);

        $this->getRecord()->delete();

        Notification::make()
            ->success()
            ->title('Usuário removido com sucesso.')
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
