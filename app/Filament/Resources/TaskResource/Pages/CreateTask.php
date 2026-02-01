<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * Mutate form data before creating a record.
     * Automatically injects wedding_id if not set.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['wedding_id'])) {
            $user = auth()->user();
            $data['wedding_id'] = $user?->current_wedding_id ?? session('filament_wedding_id');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
