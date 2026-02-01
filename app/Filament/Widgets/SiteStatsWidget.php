<?php

namespace App\Filament\Widgets;

use App\Models\SiteLayout;
use App\Models\SiteMedia;
use App\Models\SystemConfig;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget displaying site statistics.
 * 
 * Shows:
 * - Total sites created
 * - Published sites
 * - Average storage usage
 * 
 * @Requirements: 16.8
 */
class SiteStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Admin sees global stats
        if ($user && $user->isAdmin()) {
            return $this->getAdminStats();
        }

        // Regular users see their wedding stats
        return $this->getWeddingStats();
    }

    protected function getAdminStats(): array
    {
        $totalSites = SiteLayout::withoutGlobalScopes()->count();
        $publishedSites = SiteLayout::withoutGlobalScopes()->where('is_published', true)->count();
        $draftSites = $totalSites - $publishedSites;

        // Calculate average storage
        $totalStorage = SiteMedia::sum('size');
        $weddingsWithMedia = SiteMedia::distinct('wedding_id')->count('wedding_id');
        $avgStorage = $weddingsWithMedia > 0 ? $totalStorage / $weddingsWithMedia : 0;

        return [
            Stat::make('Total de Sites', $totalSites)
                ->description('Sites criados na plataforma')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Sites Publicados', $publishedSites)
                ->description($draftSites . ' em rascunho')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Uso Médio de Storage', $this->formatBytes($avgStorage))
                ->description('Por casamento')
                ->descriptionIcon('heroicon-m-server')
                ->color('info'),
        ];
    }

    protected function getWeddingStats(): array
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        if (!$weddingId) {
            return [];
        }

        $site = SiteLayout::where('wedding_id', $weddingId)->first();
        $storageUsed = SiteMedia::where('wedding_id', $weddingId)->sum('size');
        $maxStorage = SystemConfig::get('site.max_storage_per_wedding', 524288000);
        $storagePercent = $maxStorage > 0 ? round(($storageUsed / $maxStorage) * 100, 1) : 0;

        $stats = [];

        if ($site) {
            $stats[] = Stat::make('Status do Site', $site->is_published ? 'Publicado' : 'Rascunho')
                ->description($site->is_published ? 'Visível para convidados' : 'Aguardando publicação')
                ->descriptionIcon($site->is_published ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($site->is_published ? 'success' : 'warning');

            $versionsCount = $site->versions()->count();
            $stats[] = Stat::make('Versões Salvas', $versionsCount)
                ->description('Histórico de alterações')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('info');
        } else {
            $stats[] = Stat::make('Site', 'Não criado')
                ->description('Crie seu site de casamento')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('gray');
        }

        $stats[] = Stat::make('Storage Usado', $this->formatBytes($storageUsed))
            ->description($storagePercent . '% de ' . $this->formatBytes($maxStorage))
            ->descriptionIcon('heroicon-m-server')
            ->color($storagePercent > 80 ? 'danger' : ($storagePercent > 50 ? 'warning' : 'success'));

        return $stats;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Admin always sees
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has sites permission
        $weddingId = $user->current_wedding_id ?? session('filament_wedding_id');
        if (!$weddingId) {
            return false;
        }

        $wedding = \App\Models\Wedding::find($weddingId);
        if (!$wedding) {
            return false;
        }

        return $user->hasPermissionIn($wedding, 'sites');
    }
}
