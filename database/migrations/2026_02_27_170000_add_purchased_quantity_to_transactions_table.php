<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->unsignedInteger('purchased_quantity')
                ->default(1)
                ->after('gift_item_id');
        });

        DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_transactions_purchased_quantity_positive CHECK (purchased_quantity > 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS chk_transactions_purchased_quantity_positive');

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn('purchased_quantity');
        });
    }
};
