<?php

namespace Database\Seeders;

use App\Models\TemplateCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds default template categories and plan access rules.
 */
class TemplateCategorySeeder extends Seeder
{
    public function run(): void
    {
        $essentials = TemplateCategory::updateOrCreate(
            ['slug' => 'essenciais'],
            [
                'name' => 'Essenciais',
                'description' => 'Templates disponíveis para todos os planos.',
            ]
        );

        $premium = TemplateCategory::updateOrCreate(
            ['slug' => 'premium'],
            [
                'name' => 'Premium',
                'description' => 'Templates exclusivos para planos superiores.',
            ]
        );

        DB::table('plan_template_category')
            ->whereIn('template_category_id', [$essentials->id, $premium->id])
            ->delete();

        DB::table('plan_template_category')->insert([
            [
                'template_category_id' => $essentials->id,
                'plan_slug' => 'basic',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'template_category_id' => $essentials->id,
                'plan_slug' => 'premium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'template_category_id' => $premium->id,
                'plan_slug' => 'premium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
