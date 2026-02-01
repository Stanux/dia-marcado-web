<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id')->nullable();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->jsonb('content');
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');

            $table->index('wedding_id');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_templates');
    }
};
