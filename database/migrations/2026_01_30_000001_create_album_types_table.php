<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('album_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // Insert the three fixed album types
        DB::table('album_types')->insert([
            [
                'slug' => 'pre_casamento',
                'name' => 'Pré Casamento',
                'description' => 'Fotos e vídeos do ensaio pré-casamento',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'pos_casamento',
                'name' => 'Pós Casamento',
                'description' => 'Fotos e vídeos do casamento e pós-casamento',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'uso_site',
                'name' => 'Uso do Site',
                'description' => 'Mídias para uso geral no site de casamento',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('album_types');
    }
};
