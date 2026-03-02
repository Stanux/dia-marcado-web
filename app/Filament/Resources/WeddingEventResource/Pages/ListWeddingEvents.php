<?php

namespace App\Filament\Resources\WeddingEventResource\Pages;

use App\Filament\Resources\WeddingEventResource;
use Filament\Resources\Pages\ListRecords;

class ListWeddingEvents extends ListRecords
{
    protected static string $resource = WeddingEventResource::class;

    public function getTitle(): string
    {
        return 'Eventos';
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
