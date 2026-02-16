<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('task_id');
            $table->string('event');
            $table->timestamp('sent_at');
            $table->timestamp('created_at');

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');

            $table->unique(['task_id', 'event']);
            $table->index(['wedding_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_notification_logs');
    }
};
