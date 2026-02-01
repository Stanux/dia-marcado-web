<?php

namespace App\Console\Commands;

use App\Models\AlbumType;
use Illuminate\Console\Command;

class EnsureAlbumTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'album-types:ensure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure album types exist in the database';

    /**
     * Execute the console command.
     */
    public function handle()
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

        $created = 0;
        $existing = 0;

        foreach ($types as $type) {
            $albumType = AlbumType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );

            if ($albumType->wasRecentlyCreated) {
                $created++;
                $this->info("Created album type: {$type['name']}");
            } else {
                $existing++;
                $this->line("Album type already exists: {$type['name']}");
            }
        }

        $this->info("Album types check completed. Created: {$created}, Existing: {$existing}");
        
        return Command::SUCCESS;
    }
}
