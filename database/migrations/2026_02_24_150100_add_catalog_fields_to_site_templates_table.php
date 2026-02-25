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
        Schema::table('site_templates', function (Blueprint $table) {
            $table->string('slug', 140)->nullable()->after('name');
            $table->foreignId('template_category_id')
                ->nullable()
                ->after('wedding_id')
                ->constrained('template_categories')
                ->nullOnDelete();
            $table->index('template_category_id');
        });

        $usedSlugs = [];

        DB::table('site_templates')
            ->select(['id', 'name'])
            ->orderBy('created_at')
            ->get()
            ->each(function (object $template) use (&$usedSlugs): void {
                $base = Str::slug((string) $template->name);
                if ($base === '') {
                    $base = 'template';
                }

                $slug = $base;
                $counter = 1;

                while (in_array($slug, $usedSlugs, true) || DB::table('site_templates')->where('slug', $slug)->exists()) {
                    $counter++;
                    $slug = $base . '-' . $counter;
                }

                DB::table('site_templates')
                    ->where('id', $template->id)
                    ->update(['slug' => $slug]);

                $usedSlugs[] = $slug;
            });

        Schema::table('site_templates', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('site_templates', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropConstrainedForeignId('template_category_id');
            $table->dropColumn('slug');
        });
    }
};
