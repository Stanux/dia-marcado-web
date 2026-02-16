<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskValueHistory;
use Illuminate\Validation\ValidationException;

class TaskObserver
{
    public function saving(Task $task): void
    {
        if ($task->actual_value !== null && $task->actual_value < 0) {
            throw ValidationException::withMessages([
                'actual_value' => 'O valor real não pode ser negativo.',
            ]);
        }

        if ($task->status === 'completed' && $task->actual_value === null) {
            throw ValidationException::withMessages([
                'actual_value' => 'Informe o valor real ao concluir a tarefa.',
            ]);
        }

        if ($task->status === 'completed' && $task->executed_at === null) {
            throw ValidationException::withMessages([
                'executed_at' => 'Informe a data de execução ao concluir a tarefa.',
            ]);
        }
    }

    public function created(Task $task): void
    {
        $this->recordValueHistory($task, 'created');
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged(['estimated_value', 'actual_value'])) {
            $this->recordValueHistory($task, 'manual');
        }
    }

    public function deleting(Task $task): void
    {
        $task->budgets()->delete();
    }

    protected function recordValueHistory(Task $task, string $source): void
    {
        if ($task->estimated_value === null && $task->actual_value === null) {
            return;
        }

        TaskValueHistory::create([
            'wedding_id' => $task->wedding_id,
            'task_id' => $task->id,
            'changed_by' => auth()->id(),
            'estimated_value' => $task->estimated_value,
            'actual_value' => $task->actual_value,
            'source' => $source,
            'changed_at' => now(),
        ]);
    }
}
