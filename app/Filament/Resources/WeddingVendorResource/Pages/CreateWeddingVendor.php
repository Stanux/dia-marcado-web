<?php

namespace App\Filament\Resources\WeddingVendorResource\Pages;

use App\Filament\Resources\WeddingVendorResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateWeddingVendor extends CreateRecord
{
    protected static string $resource = WeddingVendorResource::class;

    public function getHeading(): string
    {
        return '';
    }

    #[On('topbar-wedding-vendor-create-another')]
    public function createAnotherFromTopbar(): void
    {
        $this->create(another: true);
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
