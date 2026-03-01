<?php

namespace App\Filament\Resources\GiftItemResource\Pages;

use App\Filament\Resources\GiftItemResource;
use Filament\Resources\Pages\ListRecords;

class ListGiftItems extends ListRecords
{
    protected static string $resource = GiftItemResource::class;

    public function getTitle(): string
    {
        return 'Presentes';
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
