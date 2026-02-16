<?php

namespace App\Filament\Resources\WeddingPlanResource\Pages;

use App\Filament\Resources\WeddingPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeddingPlans extends ListRecords
{
    protected static string $resource = WeddingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
