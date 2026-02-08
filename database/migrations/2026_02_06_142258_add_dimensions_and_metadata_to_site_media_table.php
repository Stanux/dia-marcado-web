<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_media', function (Blueprint $table) {
            $table->integer('width')->nullable()->after('mime_type');
            $table->integer('height')->nullable()->after('width');
            $table->string('alt', 255)->nullable()->after('height');
            $table->jsonb('metadata')->nullable()->after('variants');
        });
    }

    public function down(): void
    {
        Schema::table('site_media', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'alt', 'metadata']);
        });
    }
};
