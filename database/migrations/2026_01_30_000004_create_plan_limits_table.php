<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->string('plan_slug', 50)->unique();
            $table->integer('max_files');
            $table->bigInteger('max_storage_bytes');
            $table->timestamps();
        });

        // Insert plan limits for basic and premium plans
        DB::table('plan_limits')->insert([
            [
                'plan_slug' => 'basic',
                'max_files' => 100,
                'max_storage_bytes' => 524288000, // 500MB
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_slug' => 'premium',
                'max_files' => 1000,
                'max_storage_bytes' => 5368709120, // 5GB
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
