<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_checkins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('guest_id');
            $table->uuid('event_id')->nullable();
            $table->uuid('operator_id')->nullable();
            $table->string('method', 20)->default('qr'); // qr, manual
            $table->string('device_id', 100)->nullable();
            $table->dateTime('checked_in_at');
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('guest_events')->nullOnDelete();
            $table->foreign('operator_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['event_id', 'checked_in_at']);
            $table->index(['guest_id', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_checkins');
    }
};
