<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('due_date')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');

            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('wedding_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
