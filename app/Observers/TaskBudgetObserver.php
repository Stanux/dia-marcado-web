<?php

namespace App\Observers;

use App\Models\TaskBudget;

class TaskBudgetObserver
{
    public function creating(TaskBudget $budget): void
    {
        if ($budget->task) {
            $budget->wedding_id = $budget->task->wedding_id;
        }
    }

    public function saved(TaskBudget $budget): void
    {
        if (!$budget->wasChanged('status')) {
            return;
        }

        if ($budget->status !== 'approved') {
            return;
        }

        $task = $budget->task;

        if (!$task) {
            return;
        }

        if ($task->estimated_value !== null) {
            return;
        }

        $task->estimated_value = $budget->value;
        $task->save();
    }
}
