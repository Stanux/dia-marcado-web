<?php

namespace App\Filament\Resources\GiftItemResource\Pages;

use App\Filament\Resources\GiftItemResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateGiftItem extends CreateRecord
{
    protected static string $resource = GiftItemResource::class;

    public function getHeading(): string
    {
        return '';
    }

    #[On('topbar-gift-create-another')]
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
        $user = auth()->user();
        
        // Set wedding_id from current context
        if ($user && $user->currentWedding) {
            $data['wedding_id'] = $user->currentWedding->id;
        }

        // Set original values to match current values on creation
        $data['original_name'] = $data['name'];
        $data['original_description'] = $data['description'] ?? null;
        $data['original_price'] = $data['price'];
        $data['original_quantity'] = $data['quantity_available'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
