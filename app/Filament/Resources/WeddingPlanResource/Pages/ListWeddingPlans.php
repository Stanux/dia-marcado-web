<?php

namespace App\Filament\Resources\WeddingPlanResource\Pages;

use App\Filament\Resources\WeddingPlanResource;
use Filament\Resources\Pages\ListRecords;

class ListWeddingPlans extends ListRecords
{
    protected static string $resource = WeddingPlanResource::class;

    public function getTitle(): string
    {
        return 'Planejamentos';
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
