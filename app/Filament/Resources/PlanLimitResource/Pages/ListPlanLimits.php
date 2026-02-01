<?php

namespace App\Filament\Resources\PlanLimitResource\Pages;

use App\Filament\Resources\PlanLimitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanLimits extends ListRecords
{
    protected static string $resource = PlanLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
