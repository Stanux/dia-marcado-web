<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('actor_id')->nullable();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->string('action');
            $table->jsonb('changes')->default('{}');
            $table->timestamp('created_at');

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['wedding_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_audit_logs');
    }
};
