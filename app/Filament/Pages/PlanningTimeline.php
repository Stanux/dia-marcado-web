<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\Wedding;
use App\Models\WeddingPlan;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use App\Services\PermissionService;

class PlanningTimeline extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Timeline do Planejamento';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $title = 'Timeline do Planejamento';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'planning-timeline';

    protected static string $view = 'filament.pages.planning-timeline';

    public ?string $planId = null;

    public function mount(): void
    {
        $plans = $this->getPlans();
        $requestedPlanId = request()->query('plan');

        if ($requestedPlanId && $plans->contains('id', $requestedPlanId)) {
            $this->planId = $requestedPlanId;

            return;
        }

        $this->planId = $this->planId ?: $plans->first()?->id;
    }

    public function getPlans(): Collection
    {
        $weddingId = auth()->user()?->current_wedding_id ?? session('filament_wedding_id');

        if (!$weddingId) {
            return collect();
        }

        return WeddingPlan::where('wedding_id', $weddingId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTimelineTasks(): Collection
    {
        if (!$this->planId) {
            return collect();
        }

        return Task::where('wedding_plan_id', $this->planId)
            ->orderBy('start_date')
            ->orderBy('due_date')
            ->get();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $weddingId = session('filament_wedding_id') ?? $user?->current_wedding_id;

        if (!$user || !$weddingId) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $wedding = Wedding::find($weddingId);

        return $wedding && app(PermissionService::class)->canAccess($user, 'tasks', $wedding);
    }
}
