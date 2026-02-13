<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('created_by')->nullable();
            $table->string('name', 255);
            $table->string('slug', 100);
            $table->dateTime('event_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('rules')->nullable();
            $table->jsonb('questions')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['wedding_id', 'slug']);
            $table->index(['wedding_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_events');
    }
};
