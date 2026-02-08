<?php

namespace App\Filament\Resources\GiftRegistryConfigResource\Pages;

use App\Filament\Resources\GiftRegistryConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGiftRegistryConfig extends EditRecord
{
    protected static string $resource = GiftRegistryConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
