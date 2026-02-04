<?php

namespace App\Console\Commands;

use App\Models\SiteMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UpdateMediaDimensions extends Command
{
    protected $signature = 'media:update-dimensions';
    protected $description = 'Atualiza as dimensões de imagens existentes que não têm width/height';

    public function handle()
    {
        $this->info('Atualizando dimensões de imagens...');

        $media = SiteMedia::whereNull('width')
            ->orWhereNull('height')
            ->where('mime_type', 'like', 'image/%')
            ->get();

        $this->info("Encontradas {$media->count()} imagens sem dimensões.");

        $updated = 0;
        $failed = 0;

        foreach ($media as $item) {
            try {
                $path = Storage::disk($item->disk)->path($item->path);
                
                if (!file_exists($path)) {
                    $this->warn("Arquivo não encontrado: {$item->path}");
                    $failed++;
                    continue;
                }

                $imageInfo = @getimagesize($path);
                if ($imageInfo === false) {
                    $this->warn("Não foi possível obter dimensões: {$item->path}");
                    $failed++;
                    continue;
                }

                $item->update([
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ]);

                $updated++;
                $this->line("✓ {$item->original_name}: {$imageInfo[0]}x{$imageInfo[1]}");

            } catch (\Exception $e) {
                $this->error("Erro ao processar {$item->path}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("\nConcluído!");
        $this->info("Atualizadas: {$updated}");
        $this->info("Falhas: {$failed}");

        return 0;
    }
}
