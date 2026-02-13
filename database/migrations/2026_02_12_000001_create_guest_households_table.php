<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_households', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('created_by')->nullable();
            $table->string('name', 255);
            $table->string('code', 20)->nullable();
            $table->string('side', 20)->nullable(); // bride, groom, both, other
            $table->string('category', 50)->nullable();
            $table->integer('priority')->default(0);
            $table->integer('quota_adults')->nullable();
            $table->integer('quota_children')->nullable();
            $table->boolean('plus_one_allowed')->default(false);
            $table->jsonb('tags')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index('wedding_id');
            $table->index('code');
            $table->index(['wedding_id', 'side']);
            $table->index(['wedding_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_households');
    }
};
