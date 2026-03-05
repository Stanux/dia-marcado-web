<?php

namespace App\Filament\Resources\WeddingGuestResource\Pages;

use App\Filament\Resources\WeddingGuestResource;
use App\Filament\Resources\WeddingGuestResource\Pages\Concerns\ValidatesWeddingGuestDuplicates;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateWeddingGuest extends CreateRecord
{
    use ValidatesWeddingGuestDuplicates;

    protected static string $resource = WeddingGuestResource::class;

    public function getHeading(): string
    {
        return '';
    }

    #[On('topbar-wedding-guest-create-another')]
    public function createAnotherFromTopbar(): void
    {
        $this->create(another: true);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateGuestDuplicateInWedding($data);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
