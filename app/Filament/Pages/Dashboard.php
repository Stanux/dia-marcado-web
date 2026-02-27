<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected static ?string $navigationGroup = 'CASAMENTO';

    protected static ?int $navigationSort = 1;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        $user = auth()->user();
        $name = $user?->name ?? 'Usuário';
        
        return new \Illuminate\Support\HtmlString("Bem Vindo, <strong>{$name}</strong>!");
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\EventDataNudgeWidget::class,
            \App\Filament\Widgets\SiteStatsWidget::class,
        ];
    }
}
