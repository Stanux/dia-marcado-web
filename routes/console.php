<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\Planning\TaskNotificationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('planning:send-task-notifications', function () {
    app(TaskNotificationService::class)->handle();
})->purpose('Send planning task deadline notifications');

Schedule::command('planning:send-task-notifications')->dailyAt('08:00');
