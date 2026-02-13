<?php

namespace App\Filament\Resources\GuestHouseholdResource\Pages;

use App\Filament\Resources\GuestHouseholdResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuestHousehold extends CreateRecord
{
    protected static string $resource = GuestHouseholdResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
