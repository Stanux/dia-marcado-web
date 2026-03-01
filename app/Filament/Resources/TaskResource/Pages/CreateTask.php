<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Filament\Resources\WeddingPlanResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Facades\FilamentView;
use Livewire\Attributes\On;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    public ?string $returnToPlanId = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $budgetsToCreate = [];

    public function mount(): void
    {
        parent::mount();

        $this->returnToPlanId = request()->query('return_to_plan') ?? request()->query('plan');
    }

    public function getHeading(): string
    {
        return '';
    }

    #[On('topbar-task-create-another')]
    public function createAnotherFromTopbar(): void
    {
        $this->create(another: true);
    }

    #[On('topbar-task-submit')]
    public function createFromTopbar(): void
    {
        $this->create();
    }

    #[On('topbar-task-return')]
    public function returnToPlanFromTopbar(): void
    {
        $redirectUrl = $this->getReturnUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
    }

    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * Mutate form data before creating a record.
     * Automatically injects wedding_id if not set.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->budgetsToCreate = collect($data['budgets'] ?? [])
            ->map(function (array $budget): array {
                return [
                    'wedding_vendor_id' => $budget['wedding_vendor_id'] ?? null,
                    'status' => $budget['status'] ?? 'negotiation',
                    'value' => $budget['value'] ?? null,
                    'valid_until' => $budget['valid_until'] ?? null,
                    'notes' => $budget['notes'] ?? null,
                ];
            })
            ->filter(fn (array $budget): bool => filled($budget['wedding_vendor_id']) && filled($budget['value']))
            ->values()
            ->all();

        unset($data['budgets']);

        if (! isset($data['wedding_id'])) {
            $user = auth()->user();
            $data['wedding_id'] = $user?->current_wedding_id ?? session('filament_wedding_id');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->record || empty($this->budgetsToCreate)) {
            return;
        }

        foreach ($this->budgetsToCreate as $budgetData) {
            $this->record->budgets()->create([
                'wedding_id' => $this->record->wedding_id,
                'wedding_vendor_id' => $budgetData['wedding_vendor_id'],
                'status' => $budgetData['status'] ?? 'negotiation',
                'value' => $budgetData['value'],
                'valid_until' => $budgetData['valid_until'] ?? null,
                'notes' => $budgetData['notes'] ?? null,
            ]);
        }

        $this->budgetsToCreate = [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getReturnUrl($this->record?->wedding_plan_id);
    }

    protected function getReturnUrl(?string $planId = null): string
    {
        $planId ??= $this->returnToPlanId
            ?? request()->query('return_to_plan')
            ?? request()->query('plan');

        if ($planId) {
            return WeddingPlanResource::getUrl('edit', ['record' => $planId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
