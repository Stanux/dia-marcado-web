<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('site_layout_id');
            $table->uuid('wedding_id');
            $table->string('original_name', 255);
            $table->string('path', 500);
            $table->string('disk', 50)->default('local');
            $table->bigInteger('size');
            $table->string('mime_type', 100);
            $table->jsonb('variants')->default('{}');
            $table->timestamps();

            $table->foreign('site_layout_id')
                ->references('id')
                ->on('site_layouts')
                ->onDelete('cascade');

            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');

            $table->index('site_layout_id');
            $table->index('wedding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_media');
    }
};
