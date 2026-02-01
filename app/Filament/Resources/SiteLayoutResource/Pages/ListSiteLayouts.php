<?php

namespace App\Filament\Resources\SiteLayoutResource\Pages;

use App\Filament\Resources\SiteLayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteLayouts extends ListRecords
{
    protected static string $resource = SiteLayoutResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');
        $hasSite = $weddingId && \App\Models\SiteLayout::where('wedding_id', $weddingId)->exists();

        return [
            Actions\Action::make('create_site')
                ->label('Criar Site')
                ->icon('heroicon-o-plus')
                ->url(route('filament.admin.pages.site-editor'))
                ->visible(fn () => $weddingId && !$hasSite),
        ];
    }
}
