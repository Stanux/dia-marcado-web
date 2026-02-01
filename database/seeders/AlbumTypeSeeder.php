<?php

namespace Database\Seeders;

use App\Models\AlbumType;
use Illuminate\Database\Seeder;

class AlbumTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'slug' => 'pre_casamento',
                'name' => 'Pré Casamento',
                'description' => 'Fotos e vídeos do período antes do casamento (ensaios, preparativos, etc.)'
            ],
            [
                'slug' => 'pos_casamento',
                'name' => 'Pós Casamento',
                'description' => 'Fotos e vídeos do casamento e lua de mel'
            ],
            [
                'slug' => 'uso_site',
                'name' => 'Uso do Site',
                'description' => 'Mídias para uso no site de casamento (banners, galerias, etc.)'
            ]
        ];

        foreach ($types as $type) {
            AlbumType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}