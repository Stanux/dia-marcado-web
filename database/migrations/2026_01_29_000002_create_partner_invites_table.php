<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('partner_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inviter_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('name');
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->foreignUuid('existing_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('previous_wedding_id')->nullable()->constrained('weddings')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index(['token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_invites');
    }
};
