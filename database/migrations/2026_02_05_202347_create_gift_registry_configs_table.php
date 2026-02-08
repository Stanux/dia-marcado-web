<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_registry_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->string('section_title')->default('Lista de Presentes');
            $table->string('title_font_family', 100)->nullable();
            $table->integer('title_font_size')->nullable();
            $table->string('title_color', 7)->nullable(); // Hex color
            $table->string('title_style', 20)->default('normal'); // 'normal', 'bold', 'italic', 'bold_italic'
            $table->string('fee_modality', 20)->default('couple_pays'); // 'couple_pays' ou 'guest_pays'
            $table->timestamps();
            
            // Foreign key
            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');
        });
        
        // Add check constraints using raw SQL for PostgreSQL
        DB::statement("ALTER TABLE gift_registry_configs ADD CONSTRAINT chk_title_style CHECK (title_style IN ('normal', 'bold', 'italic', 'bold_italic'))");
        DB::statement("ALTER TABLE gift_registry_configs ADD CONSTRAINT chk_fee_modality_config CHECK (fee_modality IN ('couple_pays', 'guest_pays'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_registry_configs');
    }
};
