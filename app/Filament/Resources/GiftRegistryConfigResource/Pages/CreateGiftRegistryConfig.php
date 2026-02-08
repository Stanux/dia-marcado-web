<?php

namespace App\Filament\Resources\GiftRegistryConfigResource\Pages;

use App\Filament\Resources\GiftRegistryConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGiftRegistryConfig extends CreateRecord
{
    protected static string $resource = GiftRegistryConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        // Set wedding_id from current context
        if ($user && $user->currentWedding) {
            $data['wedding_id'] = $user->currentWedding->id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
