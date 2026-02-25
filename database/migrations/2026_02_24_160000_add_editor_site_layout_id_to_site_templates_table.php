<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_templates', function (Blueprint $table) {
            $table->uuid('editor_site_layout_id')
                ->nullable()
                ->after('template_category_id');

            $table->foreign('editor_site_layout_id')
                ->references('id')
                ->on('site_layouts')
                ->nullOnDelete();

            $table->index('editor_site_layout_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_templates', function (Blueprint $table) {
            $table->dropForeign(['editor_site_layout_id']);
            $table->dropIndex(['editor_site_layout_id']);
            $table->dropColumn('editor_site_layout_id');
        });
    }
};
