<?php

namespace App\Filament\Resources\TaskResource\Pages;

use Filament\Actions;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\WeddingPlanResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Facades\FilamentView;
use Livewire\Attributes\On;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    public ?string $returnToPlanId = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->returnToPlanId = request()->query('return_to_plan') ?? $this->getRecord()?->wedding_plan_id;
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    #[On('topbar-task-save')]
    public function saveFromTopbar(): void
    {
        $this->save();
    }

    #[On('topbar-task-return')]
    public function returnToPlanFromTopbar(): void
    {
        $redirectUrl = $this->getReturnUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
    }

    #[On('topbar-task-delete')]
    public function openDeleteModalFromTopbar(): void
    {
        abort_unless(static::getResource()::canDelete($this->getRecord()), 403);

        $this->mountAction('delete');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getReturnUrl($this->getRecord()?->wedding_plan_id);
    }

    protected function getReturnUrl(?string $planId = null): string
    {
        $planId ??= $this->returnToPlanId
            ?? request()->query('return_to_plan')
            ?? $this->getRecord()?->wedding_plan_id;

        if ($planId) {
            return WeddingPlanResource::getUrl('edit', ['record' => $planId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        parent::configureDeleteAction($action);

        $action->successRedirectUrl(fn (): string => $this->getReturnUrl());
    }

    protected function deleteAction(): DeleteAction
    {
        return Actions\DeleteAction::make();
    }
}
