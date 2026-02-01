<?php

namespace App\Filament\Resources\SystemConfigResource\Pages;

use App\Filament\Resources\SystemConfigResource;
use App\Models\SystemConfig;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditSystemConfig extends EditRecord
{
    protected static string $resource = SystemConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle different value types
        if (isset($data['value_array'])) {
            $data['value'] = $data['value_array'];
            unset($data['value_array']);
        } elseif (isset($data['value_simple'])) {
            // Try to convert to appropriate type
            $value = $data['value_simple'];
            if (is_numeric($value)) {
                $data['value'] = strpos($value, '.') !== false ? (float) $value : (int) $value;
            } elseif ($value === 'true' || $value === 'false') {
                $data['value'] = $value === 'true';
            } elseif ($value === 'null' || $value === '') {
                $data['value'] = null;
            } else {
                $data['value'] = $value;
            }
            unset($data['value_simple']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Clear cache for this config key
        Cache::forget('system_config:' . $this->record->key);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
