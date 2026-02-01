<?php

namespace App\Filament\Resources\PlanLimitResource\Pages;

use App\Filament\Resources\PlanLimitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanLimit extends EditRecord
{
    protected static string $resource = PlanLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
