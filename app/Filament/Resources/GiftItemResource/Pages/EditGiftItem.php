<?php

namespace App\Filament\Resources\GiftItemResource\Pages;

use App\Filament\Resources\GiftItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGiftItem extends EditRecord
{
    protected static string $resource = GiftItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('restore')
                ->label('Restaurar Original')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Restaurar valores originais')
                ->modalDescription('Tem certeza que deseja restaurar este item aos valores originais? As alterações atuais serão perdidas.')
                ->action(function () {
                    $this->record->restoreOriginal();
                    $this->refreshFormData([
                        'name',
                        'description',
                        'price',
                        'quantity_available',
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Item restaurado')
                        ->success()
                        ->body('O item foi restaurado aos valores originais.')
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
