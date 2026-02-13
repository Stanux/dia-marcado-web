<?php

namespace App\Filament\Resources\GuestResource\Pages;

use App\Filament\Resources\GuestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuests extends ListRecords
{
    protected static string $resource = GuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Importar CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(GuestResource::getUrl('import')),
            Actions\CreateAction::make(),
        ];
    }
}
