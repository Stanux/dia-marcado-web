<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add album-related fields to site_media table.
 * 
 * Adds support for:
 * - Album association (album_id)
 * - Upload status tracking (status: pending, processing, completed, failed)
 * - Batch upload tracking (batch_id)
 * - Error message storage for failed uploads (error_message)
 * 
 * @see Requirements 1.1, 1.4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_media', function (Blueprint $table) {
            // Album association - nullable to maintain backward compatibility
            // with existing media that may not be associated with an album
            $table->uuid('album_id')->nullable()->after('wedding_id');

            // Status field for tracking upload progress
            // Default to 'completed' for backward compatibility with existing records
            $table->string('status', 20)->default('completed')->after('variants');

            // Batch ID for grouping uploads in a batch operation
            $table->uuid('batch_id')->nullable()->after('status');

            // Error message for failed uploads
            $table->text('error_message')->nullable()->after('batch_id');

            // Foreign key to albums table
            $table->foreign('album_id')
                ->references('id')
                ->on('albums')
                ->onDelete('set null');

            // Indexes for performance
            $table->index('album_id');
            $table->index('status');
            $table->index('batch_id');
            $table->index(['wedding_id', 'status']);
            $table->index(['album_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('site_media', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['album_id']);

            // Drop indexes
            $table->dropIndex(['album_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['batch_id']);
            $table->dropIndex(['wedding_id', 'status']);
            $table->dropIndex(['album_id', 'status']);

            // Drop columns
            $table->dropColumn(['album_id', 'status', 'batch_id', 'error_message']);
        });
    }
};
