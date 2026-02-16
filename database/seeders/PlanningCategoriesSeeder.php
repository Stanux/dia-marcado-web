<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanningCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $taskCategories = [
            'Local e Estrutura',
            'Buffet e Bebidas',
            'Fotografia e Vídeo',
            'Música e Entretenimento',
            'Decoração e Ambientação',
            'Trajes e Beleza',
            'Convites e Papelaria',
            'Documentação e Legal',
            'Lua de Mel',
            'Lista de Presentes',
            'Planejamento Financeiro',
            'Experiência do Convidado',
            'Hospedagem e Logística',
            'Operação do Dia do Evento',
            'Pós-Evento',
        ];

        foreach ($taskCategories as $index => $name) {
            $slug = Str::slug($name);

            if (DB::table('task_categories')->where('slug', $slug)->exists()) {
                DB::table('task_categories')
                    ->where('slug', $slug)
                    ->update([
                        'name' => $name,
                        'sort' => $index + 1,
                        'updated_at' => now(),
                    ]);
                continue;
            }

            DB::table('task_categories')->insert([
                'id' => (string) Str::uuid(),
                'name' => $name,
                'slug' => $slug,
                'sort' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $vendorCategories = [
            'Espaço / Local de Evento',
            'Buffet / Catering',
            'Bebidas / Bar',
            'Fotografia',
            'Filmagem',
            'Música (DJ / Banda / Orquestra)',
            'Entretenimento',
            'Decoração',
            'Florista',
            'Iluminação e Sonorização',
            'Mobiliário e Estrutura',
            'Trajes',
            'Beleza (Cabelo e Maquiagem)',
            'Papelaria e Design',
            'Gráfica',
            'Cerimonial / Assessoria',
            'Celebrante',
            'Cartório',
            'Agência de Viagem',
            'Transporte',
            'Hospedagem',
            'Seguro',
            'Segurança',
            'Limpeza',
            'Tecnologia e Plataforma',
            'Serviços Financeiros',
        ];

        foreach ($vendorCategories as $index => $name) {
            $slug = Str::slug($name);

            if (DB::table('vendor_categories')->where('slug', $slug)->exists()) {
                DB::table('vendor_categories')
                    ->where('slug', $slug)
                    ->update([
                        'name' => $name,
                        'sort' => $index + 1,
                        'updated_at' => now(),
                    ]);
                continue;
            }

            DB::table('vendor_categories')->insert([
                'id' => (string) Str::uuid(),
                'name' => $name,
                'slug' => $slug,
                'sort' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
