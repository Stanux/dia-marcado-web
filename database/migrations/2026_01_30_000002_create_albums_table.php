<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->unsignedBigInteger('album_type_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->uuid('cover_media_id')->nullable();
            $table->timestamps();

            // Foreign key to weddings table (uuid)
            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');

            // Foreign key to album_types table (int/bigint)
            $table->foreign('album_type_id')
                ->references('id')
                ->on('album_types')
                ->onDelete('restrict');

            // Foreign key to site_media table (uuid, nullable for cover)
            $table->foreign('cover_media_id')
                ->references('id')
                ->on('site_media')
                ->onDelete('set null');

            // Indexes for performance
            $table->index('wedding_id');
            $table->index('album_type_id');
            $table->index('cover_media_id');
            $table->index(['wedding_id', 'album_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
