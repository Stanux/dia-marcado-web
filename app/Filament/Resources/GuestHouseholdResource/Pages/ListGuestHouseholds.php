<?php

namespace App\Filament\Resources\GuestHouseholdResource\Pages;

use App\Filament\Resources\GuestHouseholdResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuestHouseholds extends ListRecords
{
    protected static string $resource = GuestHouseholdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
