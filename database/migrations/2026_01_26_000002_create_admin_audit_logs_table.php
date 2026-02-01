<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_id');
            $table->uuid('wedding_id');
            $table->string('action');
            $table->jsonb('details')->default('{}');
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->index(['admin_id', 'performed_at']);
            $table->index(['wedding_id', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
