<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_template_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_category_id')
                ->constrained('template_categories')
                ->cascadeOnDelete();
            $table->string('plan_slug', 50);
            $table->timestamps();

            $table->unique(['template_category_id', 'plan_slug'], 'plan_template_category_unique');

            $table->foreign('plan_slug')
                ->references('plan_slug')
                ->on('plan_limits')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_template_category');
        Schema::dropIfExists('template_categories');
    }
};
