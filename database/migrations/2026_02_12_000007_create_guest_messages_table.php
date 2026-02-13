<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('created_by')->nullable();
            $table->string('channel', 20)->default('email'); // email, whatsapp, sms
            $table->string('subject', 255)->nullable();
            $table->text('body')->nullable();
            $table->jsonb('payload')->nullable();
            $table->string('status', 20)->default('draft'); // draft, sending, sent, failed
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['wedding_id', 'channel']);
            $table->index(['wedding_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_messages');
    }
};
