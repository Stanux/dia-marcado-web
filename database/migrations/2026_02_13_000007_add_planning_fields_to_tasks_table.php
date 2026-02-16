<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->uuid('wedding_plan_id')->nullable()->after('wedding_id');
            $table->uuid('task_category_id')->nullable()->after('description');
            $table->date('start_date')->nullable()->after('status');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->after('start_date');
            $table->decimal('estimated_value', 12, 2)->nullable()->after('priority');
            $table->decimal('actual_value', 12, 2)->nullable()->after('estimated_value');
            $table->date('executed_at')->nullable()->after('actual_value');
            $table->uuid('created_by')->nullable()->after('assigned_to');
            $table->uuid('updated_by')->nullable()->after('created_by');
            $table->softDeletes();

            $table->foreign('wedding_plan_id')->references('id')->on('wedding_plans')->nullOnDelete();
            $table->foreign('task_category_id')->references('id')->on('task_categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index('wedding_plan_id');
            $table->index('task_category_id');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['wedding_plan_id']);
            $table->dropForeign(['task_category_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            $table->dropIndex(['wedding_plan_id']);
            $table->dropIndex(['task_category_id']);
            $table->dropIndex(['priority']);

            $table->dropColumn([
                'wedding_plan_id',
                'task_category_id',
                'start_date',
                'priority',
                'estimated_value',
                'actual_value',
                'executed_at',
                'created_by',
                'updated_by',
                'deleted_at',
            ]);
        });
    }
};
