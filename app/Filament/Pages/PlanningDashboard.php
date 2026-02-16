<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\Wedding;
use App\Models\WeddingPlan;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use App\Services\PermissionService;

class PlanningDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Dashboard Planejamento';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?string $title = 'Dashboard do Planejamento';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'planning-dashboard';

    protected static string $view = 'filament.pages.planning-dashboard';

    public ?string $planId = null;

    public function mount(): void
    {
        $this->planId = $this->planId ?: $this->getPlans()->first()?->id;
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

    public function getSummary(): array
    {
        if (!$this->planId) {
            return [
                'total' => 0,
                'completed' => 0,
                'progress' => 0,
                'plan_budget' => 0,
                'estimated' => 0,
                'actual' => 0,
                'difference' => 0,
                'estimated_vs_plan' => 0,
                'actual_vs_plan' => 0,
                'top_categories' => collect(),
                'impact_total' => 0,
                'category_simulator' => collect(),
                'overdue' => collect(),
                'upcoming' => collect(),
            ];
        }

        $plan = WeddingPlan::find($this->planId);

        if (!$plan) {
            return [
                'total' => 0,
                'completed' => 0,
                'progress' => 0,
                'plan_budget' => 0,
                'estimated' => 0,
                'actual' => 0,
                'difference' => 0,
                'estimated_vs_plan' => 0,
                'actual_vs_plan' => 0,
                'top_categories' => collect(),
                'impact_total' => 0,
                'category_simulator' => collect(),
                'overdue' => collect(),
                'upcoming' => collect(),
            ];
        }

        $wedding = Wedding::find($plan->wedding_id);
        $timezone = $wedding?->getSetting('timezone', config('app.timezone')) ?? config('app.timezone');
        $today = now($timezone)->startOfDay();
        $upcomingEnd = $today->copy()->addDays(7)->endOfDay();

        $tasksQuery = Task::where('wedding_plan_id', $plan->id);

        $total = (int) (clone $tasksQuery)->count();
        $completed = (int) (clone $tasksQuery)->where('status', 'completed')->count();
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

        $estimated = (float) (clone $tasksQuery)->sum('estimated_value');
        $actual = (float) (clone $tasksQuery)->sum('actual_value');
        $difference = $actual - $estimated;
        $planBudget = (float) ($plan->total_budget ?? 0);
        $estimatedVsPlan = $estimated - $planBudget;
        $actualVsPlan = $actual - $planBudget;

        $categorySimulator = Task::query()
            ->leftJoin('task_categories', 'tasks.task_category_id', '=', 'task_categories.id')
            ->where('tasks.wedding_plan_id', $plan->id)
            ->selectRaw("
                coalesce(task_categories.name, 'Sem categoria') as name,
                sum(case when tasks.actual_value is not null then tasks.actual_value else 0 end) as actual_total,
                sum(case when tasks.actual_value is null then coalesce(tasks.estimated_value, 0) else 0 end) as estimated_only_total
            ")
            ->groupByRaw("coalesce(task_categories.name, 'Sem categoria')")
            ->get()
            ->map(function ($row) {
                $actual = (float) ($row->actual_total ?? 0);
                $estimated = (float) ($row->estimated_only_total ?? 0);

                return [
                    'name' => $row->name,
                    'actual' => $actual,
                    'estimated' => $estimated,
                    'total' => $actual + $estimated,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $topCategories = $categorySimulator->take(3)->values();
        $impactTotal = (float) $categorySimulator->sum('total');

        $overdue = (clone $tasksQuery)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', '<', $today->toDateString())
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $upcoming = (clone $tasksQuery)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereBetween('due_date', [$today->toDateString(), $upcomingEnd->toDateString()])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return [
            'total' => $total,
            'completed' => $completed,
            'progress' => $progress,
            'plan_budget' => $planBudget,
            'estimated' => $estimated,
            'actual' => $actual,
            'difference' => $difference,
            'estimated_vs_plan' => $estimatedVsPlan,
            'actual_vs_plan' => $actualVsPlan,
            'top_categories' => $topCategories,
            'impact_total' => $impactTotal,
            'category_simulator' => $categorySimulator,
            'overdue' => $overdue,
            'upcoming' => $upcoming,
        ];
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
