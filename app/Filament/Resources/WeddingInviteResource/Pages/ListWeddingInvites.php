<?php

namespace App\Filament\Resources\WeddingInviteResource\Pages;

use App\Filament\Resources\WeddingInviteResource;
use Filament\Resources\Pages\ListRecords;

class ListWeddingInvites extends ListRecords
{
    protected static string $resource = WeddingInviteResource::class;

    public function getTitle(): string
    {
        return 'Convites';
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
