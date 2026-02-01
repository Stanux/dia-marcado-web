<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_layouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->jsonb('draft_content')->default('{}');
            $table->jsonb('published_content')->nullable();
            $table->string('slug', 255)->unique();
            $table->string('custom_domain', 255)->nullable();
            $table->string('access_token', 255)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');

            $table->index('wedding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_layouts');
    }
};
