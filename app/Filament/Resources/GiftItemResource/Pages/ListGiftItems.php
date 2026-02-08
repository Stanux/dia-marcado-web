<?php

namespace App\Filament\Resources\GiftItemResource\Pages;

use App\Filament\Resources\GiftItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGiftItems extends ListRecords
{
    protected static string $resource = GiftItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
