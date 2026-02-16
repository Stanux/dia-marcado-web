<?php

namespace App\Services\Planning;

use App\Models\Task;
use App\Models\WeddingPlan;

class WeddingPlanService
{
    public function duplicate(WeddingPlan $plan, ?string $title = null): WeddingPlan
    {
        $newPlan = WeddingPlan::create([
            'wedding_id' => $plan->wedding_id,
            'title' => $title ?? $plan->title . ' (Cópia)',
            'total_budget' => $plan->total_budget,
        ]);

        $plan->tasks()->orderBy('created_at')->get()->each(function (Task $task) use ($newPlan) {
            Task::create([
                'wedding_id' => $task->wedding_id,
                'wedding_plan_id' => $newPlan->id,
                'title' => $task->title,
                'description' => $task->description,
                'task_category_id' => $task->task_category_id,
                'status' => 'pending',
                'start_date' => $task->start_date,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
                'estimated_value' => $task->estimated_value,
                'assigned_to' => $task->assigned_to,
            ]);
        });

        return $newPlan;
    }
}
