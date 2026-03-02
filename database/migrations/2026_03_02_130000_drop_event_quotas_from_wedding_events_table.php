<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wedding_events')) {
            Schema::table('wedding_events', function (Blueprint $table): void {
                if (Schema::hasColumn('wedding_events', 'adult_quota')) {
                    $table->dropColumn('adult_quota');
                }

                if (Schema::hasColumn('wedding_events', 'child_quota')) {
                    $table->dropColumn('child_quota');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wedding_events')) {
            Schema::table('wedding_events', function (Blueprint $table): void {
                if (! Schema::hasColumn('wedding_events', 'adult_quota')) {
                    $table->unsignedInteger('adult_quota')->nullable();
                }

                if (! Schema::hasColumn('wedding_events', 'child_quota')) {
                    $table->unsignedInteger('child_quota')->nullable();
                }
            });
        }
    }
};
