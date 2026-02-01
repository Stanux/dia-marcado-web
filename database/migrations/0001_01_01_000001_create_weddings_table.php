<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weddings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->date('wedding_date')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('wedding_date');
            $table->index('is_active');
        });

        // Add foreign key to users table after weddings is created
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('current_wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_wedding_id']);
        });

        Schema::dropIfExists('weddings');
    }
};
