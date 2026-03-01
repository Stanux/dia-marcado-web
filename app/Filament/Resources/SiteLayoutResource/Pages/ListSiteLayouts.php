<?php

namespace App\Filament\Resources\SiteLayoutResource\Pages;

use App\Filament\Resources\SiteLayoutResource;
use Filament\Resources\Pages\ListRecords;

class ListSiteLayouts extends ListRecords
{
    protected static string $resource = SiteLayoutResource::class;

    public function getTitle(): string
    {
        return 'Editor do Site';
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
