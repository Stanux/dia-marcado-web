<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('internal_id', 100)->unique();
            $table->string('pagseguro_transaction_id', 100)->unique()->nullable();
            $table->uuid('wedding_id');
            $table->uuid('gift_item_id');
            $table->integer('original_unit_price'); // Em centavos
            $table->decimal('fee_percentage', 5, 2);
            $table->string('fee_modality', 20); // 'couple_pays' ou 'guest_pays'
            $table->integer('fee_amount'); // Em centavos
            $table->integer('gross_amount'); // Em centavos
            $table->integer('net_amount_couple'); // Em centavos
            $table->integer('platform_amount'); // Em centavos
            $table->string('payment_method', 20); // 'credit_card' ou 'pix'
            $table->string('status', 20)->default('pending'); // 'pending', 'confirmed', 'failed', 'refunded'
            $table->text('error_message')->nullable();
            $table->jsonb('pagseguro_response')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('wedding_id')
                ->references('id')
                ->on('weddings')
                ->onDelete('cascade');
            
            $table->foreign('gift_item_id')
                ->references('id')
                ->on('gift_items')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['wedding_id', 'status']);
            $table->index('pagseguro_transaction_id');
            $table->index('created_at');
            $table->index('payment_method');
        });
        
        // Add check constraints using raw SQL for PostgreSQL
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_fee_modality CHECK (fee_modality IN ('couple_pays', 'guest_pays'))");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_payment_method CHECK (payment_method IN ('credit_card', 'pix'))");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_status CHECK (status IN ('pending', 'confirmed', 'failed', 'refunded'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
