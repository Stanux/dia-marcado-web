<?php

namespace App\Filament\Resources\WeddingVendorResource\Pages;

use App\Filament\Resources\WeddingVendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeddingVendors extends ListRecords
{
    protected static string $resource = WeddingVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
