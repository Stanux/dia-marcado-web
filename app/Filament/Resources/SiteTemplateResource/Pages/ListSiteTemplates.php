<?php

namespace App\Filament\Resources\SiteTemplateResource\Pages;

use App\Filament\Resources\SiteTemplateResource;
use Filament\Resources\Pages\ListRecords;

class ListSiteTemplates extends ListRecords
{
    protected static string $resource = SiteTemplateResource::class;

    public function getTitle(): string
    {
        return 'Templates';
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
