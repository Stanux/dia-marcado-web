<?php

namespace App\Filament\Resources\AlbumResource\Pages;

use App\Filament\Resources\AlbumResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlbum extends CreateRecord
{
    protected static string $resource = AlbumResource::class;

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
