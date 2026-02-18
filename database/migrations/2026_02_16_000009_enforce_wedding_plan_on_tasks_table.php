<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $weddingIds = DB::table('tasks')
            ->whereNull('wedding_plan_id')
            ->distinct()
            ->pluck('wedding_id');

        foreach ($weddingIds as $weddingId) {
            $planId = DB::table('wedding_plans')
                ->where('wedding_id', $weddingId)
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN archived_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('created_at')
                ->value('id');

            if (!$planId) {
                $planId = (string) Str::uuid();

                DB::table('wedding_plans')->insert([
                    'id' => $planId,
                    'wedding_id' => $weddingId,
                    'title' => 'Planejamento padrão',
                    'total_budget' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('tasks')
                ->where('wedding_id', $weddingId)
                ->whereNull('wedding_plan_id')
                ->update([
                    'wedding_plan_id' => $planId,
                    'updated_at' => $now,
                ]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['wedding_plan_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->uuid('wedding_plan_id')->nullable(false)->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('wedding_plan_id')->references('id')->on('wedding_plans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['wedding_plan_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->uuid('wedding_plan_id')->nullable()->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('wedding_plan_id')->references('id')->on('wedding_plans')->nullOnDelete();
        });
    }
};
