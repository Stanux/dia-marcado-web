<?php

namespace App\Filament\Resources\GuestEventResource\Pages;

use App\Filament\Resources\GuestEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuestEvent extends CreateRecord
{
    protected static string $resource = GuestEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
