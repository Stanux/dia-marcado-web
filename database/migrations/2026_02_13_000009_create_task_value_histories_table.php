<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_value_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('task_id');
            $table->uuid('changed_by')->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->decimal('actual_value', 12, 2)->nullable();
            $table->string('source')->default('manual');
            $table->jsonb('meta')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['wedding_id', 'task_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_value_histories');
    }
};
