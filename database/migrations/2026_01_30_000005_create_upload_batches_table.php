<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('album_id')->nullable();
            $table->integer('total_files')->default(0);
            $table->integer('completed_files')->default(0);
            $table->integer('failed_files')->default(0);
            $table->string('status', 50)->default('pending');
            $table->timestamps();

            // Foreign key to weddings table (uuid)
            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');

            // Foreign key to albums table (uuid) - nullable
            $table->foreign('album_id')
                ->references('id')
                ->on('albums')
                ->onDelete('set null');

            // Indexes for performance
            $table->index('wedding_id');
            $table->index('album_id');
            $table->index('status');
            $table->index(['wedding_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_batches');
    }
};
