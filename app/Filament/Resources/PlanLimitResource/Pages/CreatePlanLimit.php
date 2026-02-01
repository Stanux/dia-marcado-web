<?php

namespace App\Filament\Resources\PlanLimitResource\Pages;

use App\Filament\Resources\PlanLimitResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlanLimit extends CreateRecord
{
    protected static string $resource = PlanLimitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert MB to bytes
        if (isset($data['max_storage_mb'])) {
            $data['max_storage_bytes'] = (int) $data['max_storage_mb'] * 1024 * 1024;
            unset($data['max_storage_mb']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
