<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_message_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->uuid('guest_id');
            $table->string('status', 20); // sent, delivered, clicked, failed
            $table->dateTime('occurred_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('guest_messages')->onDelete('cascade');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');

            $table->index(['guest_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_message_logs');
    }
};
