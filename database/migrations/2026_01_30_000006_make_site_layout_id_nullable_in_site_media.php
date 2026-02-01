<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to make site_layout_id nullable in site_media table.
 * 
 * This allows media to exist in albums without being tied to a specific site layout.
 * Media can now be organized in albums and later used in site layouts.
 * 
 * @see Requirements 2.2, 2.3 - Album organization
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_media', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['site_layout_id']);
        });

        // Make the column nullable
        Schema::table('site_media', function (Blueprint $table) {
            $table->uuid('site_layout_id')->nullable()->change();
        });

        Schema::table('site_media', function (Blueprint $table) {
            // Re-add the foreign key with cascade delete
            $table->foreign('site_layout_id')
                ->references('id')
                ->on('site_layouts')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Note: This down migration may fail if there are records with null site_layout_id
        Schema::table('site_media', function (Blueprint $table) {
            $table->dropForeign(['site_layout_id']);
        });

        Schema::table('site_media', function (Blueprint $table) {
            $table->uuid('site_layout_id')->nullable(false)->change();
        });

        Schema::table('site_media', function (Blueprint $table) {
            $table->foreign('site_layout_id')
                ->references('id')
                ->on('site_layouts')
                ->onDelete('cascade');
        });
    }
};
