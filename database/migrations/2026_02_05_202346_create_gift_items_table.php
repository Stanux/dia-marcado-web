<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->integer('price'); // Em centavos
            $table->integer('quantity_available')->default(1);
            $table->integer('quantity_sold')->default(0);
            $table->boolean('is_enabled')->default(true);
            
            // Campos para restauração
            $table->string('original_name');
            $table->text('original_description')->nullable();
            $table->integer('original_price');
            $table->integer('original_quantity');
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['wedding_id', 'is_enabled']);
            $table->index('price');
        });
        
        // Add check constraints using raw SQL for PostgreSQL
        DB::statement('ALTER TABLE gift_items ADD CONSTRAINT chk_price_minimum CHECK (price >= 100)');
        DB::statement('ALTER TABLE gift_items ADD CONSTRAINT chk_quantity_positive CHECK (quantity_available >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_items');
    }
};
