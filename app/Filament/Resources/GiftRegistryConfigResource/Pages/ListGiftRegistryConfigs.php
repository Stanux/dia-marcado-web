<?php

namespace App\Filament\Resources\GiftRegistryConfigResource\Pages;

use App\Filament\Resources\GiftRegistryConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGiftRegistryConfigs extends ListRecords
{
    protected static string $resource = GiftRegistryConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
