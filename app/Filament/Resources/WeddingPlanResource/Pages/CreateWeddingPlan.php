<?php

namespace App\Filament\Resources\WeddingPlanResource\Pages;

use App\Filament\Resources\WeddingPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWeddingPlan extends CreateRecord
{
    protected static string $resource = WeddingPlanResource::class;

    public function getHeading(): string
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return WeddingPlanResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
