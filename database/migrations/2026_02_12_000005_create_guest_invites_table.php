<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('household_id');
            $table->uuid('guest_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->string('token', 64)->unique();
            $table->string('channel', 20)->default('email'); // email, whatsapp, sms
            $table->string('status', 20)->default('sent'); // sent, delivered, opened, expired, revoked
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('household_id')->references('id')->on('guest_households')->onDelete('cascade');
            $table->foreign('guest_id')->references('id')->on('guests')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['household_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_invites');
    }
};
