<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Register;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureWeddingContext;
use App\Http\Middleware\FilamentWeddingScope;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(Register::class)
            ->passwordReset()
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                'panels::topbar.start',
                fn () => view('filament.components.topbar')
            )
            ->renderHook(
                'panels::styles.after',
                fn () => new \Illuminate\Support\HtmlString('<style>.fi-page-header { padding-top: 0.25rem !important; padding-bottom: 0.5rem !important; } .gap-y-8 { gap: 0.5rem !important; } .fi-main .py-8, .fi-sidebar-nav { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; } .fi-sidebar-nav-groups { gap: 0.5rem !important; } .fi-page-header > div:first-child { align-items: flex-end !important; } .fi-page-header-actions { margin-top: 0 !important; }</style>')
            )
            ->colors([
                'primary' => Color::Rose,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureOnboardingComplete::class,
                FilamentWeddingScope::class,
            ]);
    }
}
