<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('site_layout_id');
            $table->uuid('user_id')->nullable();
            $table->jsonb('content');
            $table->string('summary', 500);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->foreign('site_layout_id')
                ->references('id')
                ->on('site_layouts')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('site_layout_id');
            $table->index(['site_layout_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_versions');
    }
};
