<?php

namespace App\Filament\Resources\WeddingGuestResource\Pages;

use App\Filament\Resources\WeddingGuestResource;
use Filament\Resources\Pages\ListRecords;

class ListWeddingGuests extends ListRecords
{
    protected static string $resource = WeddingGuestResource::class;

    public function getTitle(): string
    {
        return 'Convidados';
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
