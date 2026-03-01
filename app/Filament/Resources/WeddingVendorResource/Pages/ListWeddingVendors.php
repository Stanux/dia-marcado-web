<?php

namespace App\Filament\Resources\WeddingVendorResource\Pages;

use App\Filament\Resources\WeddingVendorResource;
use Filament\Resources\Pages\ListRecords;

class ListWeddingVendors extends ListRecords
{
    protected static string $resource = WeddingVendorResource::class;

    public function getTitle(): string
    {
        return 'Fornecedores';
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
