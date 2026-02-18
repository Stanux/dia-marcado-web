<?php

namespace App\Notifications;

use App\Filament\Resources\WeddingPlanResource;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Task $task,
        protected string $event
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->event) {
            'due_week' => 'Tarefa vence em 1 semana',
            'due_day' => 'Tarefa vence amanhã',
            'overdue' => 'Tarefa atrasada',
            default => 'Atualização de tarefa',
        };

        $dueDate = $this->task->due_date?->format('d/m/Y');
        $taskUrl = WeddingPlanResource::getUrl('edit', ['record' => $this->task->wedding_plan_id]);

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Olá!')
            ->line('Tarefa: ' . $this->task->title)
            ->line('Data limite: ' . ($dueDate ?? '—'))
            ->action('Abrir planejamento', $taskUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'event' => $this->event,
            'due_date' => $this->task->due_date?->format('Y-m-d'),
        ];
    }
}
