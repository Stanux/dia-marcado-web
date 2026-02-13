<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_rsvps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('guest_id');
            $table->uuid('event_id');
            $table->uuid('updated_by')->nullable();
            $table->string('status', 20)->default('no_response'); // confirmed, declined, maybe, no_response
            $table->jsonb('responses')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('guest_events')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['guest_id', 'event_id']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_rsvps');
    }
};
