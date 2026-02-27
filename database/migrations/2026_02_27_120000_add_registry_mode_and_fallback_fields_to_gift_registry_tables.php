<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_registry_configs', function (Blueprint $table) {
            $table->string('registry_mode', 20)->default('quantity');
        });

        Schema::table('gift_items', function (Blueprint $table) {
            $table->boolean('is_fallback_donation')->default(false);
            $table->integer('minimum_custom_amount')->nullable();
        });

        DB::statement("ALTER TABLE gift_registry_configs ADD CONSTRAINT chk_registry_mode_config CHECK (registry_mode IN ('quantity', 'quota'))");
        DB::statement('ALTER TABLE gift_items ADD CONSTRAINT chk_minimum_custom_amount_non_negative CHECK (minimum_custom_amount IS NULL OR minimum_custom_amount >= 0)');
        DB::statement('CREATE UNIQUE INDEX uq_gift_items_single_fallback_per_wedding ON gift_items (wedding_id) WHERE is_fallback_donation = true');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_gift_items_single_fallback_per_wedding');
        DB::statement('ALTER TABLE gift_items DROP CONSTRAINT IF EXISTS chk_minimum_custom_amount_non_negative');
        DB::statement('ALTER TABLE gift_registry_configs DROP CONSTRAINT IF EXISTS chk_registry_mode_config');

        Schema::table('gift_items', function (Blueprint $table) {
            $table->dropColumn([
                'is_fallback_donation',
                'minimum_custom_amount',
            ]);
        });

        Schema::table('gift_registry_configs', function (Blueprint $table) {
            $table->dropColumn('registry_mode');
        });
    }
};
