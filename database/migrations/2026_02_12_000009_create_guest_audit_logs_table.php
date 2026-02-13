<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('actor_id')->nullable();
            $table->string('action', 100);
            $table->jsonb('context')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['wedding_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_audit_logs');
    }
};
