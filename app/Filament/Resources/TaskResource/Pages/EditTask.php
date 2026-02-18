<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Filament\Resources\WeddingPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        $planId = request()->query('return_to_plan') ?? $this->getRecord()?->wedding_plan_id;

        if ($planId) {
            return WeddingPlanResource::getUrl('edit', ['record' => $planId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
