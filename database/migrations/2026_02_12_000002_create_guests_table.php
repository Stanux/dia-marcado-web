<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('household_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('nickname', 100)->nullable();
            $table->string('role_in_household', 30)->nullable(); // head, spouse, child, plus_one, other
            $table->boolean('is_child')->default(false);
            $table->string('category', 50)->nullable();
            $table->string('side', 20)->nullable();
            $table->string('status', 20)->default('pending'); // pending, confirmed, declined, maybe
            $table->jsonb('tags')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('household_id')->references('id')->on('guest_households')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index('wedding_id');
            $table->index('household_id');
            $table->index(['wedding_id', 'status']);
            $table->index(['wedding_id', 'side']);
            $table->index(['wedding_id', 'category']);
            $table->index(['wedding_id', 'email']);
            $table->index(['wedding_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
