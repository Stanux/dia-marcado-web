<?php

namespace App\Filament\Resources\WeddingPlanResource\Pages;

use App\Filament\Resources\WeddingPlanResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateWeddingPlan extends CreateRecord
{
    protected static string $resource = WeddingPlanResource::class;

    public function getHeading(): string
    {
        return '';
    }

    #[On('topbar-wedding-plan-create-another')]
    public function createAnotherFromTopbar(): void
    {
        $this->create(another: true);
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
