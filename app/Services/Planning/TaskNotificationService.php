<?php

namespace App\Services\Planning;

use App\Models\Task;
use App\Models\TaskNotificationLog;
use App\Models\Wedding;
use App\Notifications\TaskDeadlineNotification;
use Illuminate\Support\Facades\Notification;

class TaskNotificationService
{
    public function handle(): void
    {
        Wedding::query()->each(function (Wedding $wedding): void {
            $this->processWedding($wedding);
        });
    }

    protected function processWedding(Wedding $wedding): void
    {
        $timezone = $wedding->getSetting('timezone', config('app.timezone'));
        $today = now($timezone)->startOfDay();
        $weekDate = $today->copy()->addDays(7)->toDateString();
        $dayDate = $today->copy()->addDays(1)->toDateString();
        $todayDate = $today->toDateString();

        $this->sendForDate($wedding, $weekDate, 'due_week');
        $this->sendForDate($wedding, $dayDate, 'due_day');
        $this->sendOverdue($wedding, $todayDate);
    }

    protected function sendForDate(Wedding $wedding, string $targetDate, string $event): void
    {
        $tasks = Task::where('wedding_id', $wedding->id)
            ->whereDate('due_date', $targetDate)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        foreach ($tasks as $task) {
            if (!$task->assigned_to) {
                continue;
            }

            $recipient = $task->assignedUser;

            if (!$recipient) {
                continue;
            }

            $log = TaskNotificationLog::firstOrCreate([
                'task_id' => $task->id,
                'event' => $event,
            ], [
                'wedding_id' => $wedding->id,
                'sent_at' => now(),
                'created_at' => now(),
            ]);

            if (!$log->wasRecentlyCreated) {
                continue;
            }

            Notification::send($recipient, new TaskDeadlineNotification($task, $event));
        }
    }

    protected function sendOverdue(Wedding $wedding, string $todayDate): void
    {
        $tasks = Task::where('wedding_id', $wedding->id)
            ->whereDate('due_date', '<', $todayDate)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        foreach ($tasks as $task) {
            $recipients = collect();

            if ($task->assignedUser) {
                $recipients->push($task->assignedUser);
            }

            $coupleMembers = $wedding->couple()->get();

            $recipients = $recipients->merge($coupleMembers)->unique('id');

            if ($recipients->isEmpty()) {
                continue;
            }

            $log = TaskNotificationLog::firstOrCreate([
                'task_id' => $task->id,
                'event' => 'overdue',
            ], [
                'wedding_id' => $wedding->id,
                'sent_at' => now(),
                'created_at' => now(),
            ]);

            if (!$log->wasRecentlyCreated) {
                continue;
            }

            Notification::send($recipients, new TaskDeadlineNotification($task, 'overdue'));
        }
    }
}
