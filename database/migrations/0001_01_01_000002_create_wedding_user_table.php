<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('wedding_id');
            $table->enum('role', ['couple', 'organizer', 'guest'])->default('guest');
            $table->jsonb('permissions')->default('[]');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');

            $table->unique(['user_id', 'wedding_id']);
            $table->index('wedding_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_user');
    }
};
