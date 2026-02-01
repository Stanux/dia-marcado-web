<?php

namespace App\Filament\Pages;

use App\Models\SiteLayout;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;

/**
 * Filament Page that redirects to the Vue.js Site Editor.
 * 
 * This page serves as a bridge between Filament's routing system
 * and the Inertia-based Vue.js editor component.
 * 
 * @Requirements: 1.2, 3.1
 */
class SiteEditor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static string $view = 'filament.pages.site-editor';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'site-editor';

    public ?string $siteId = null;

    public function mount(?string $site = null): void
    {
        $user = auth()->user();
        $weddingId = $user?->current_wedding_id ?? session('filament_wedding_id');

        if (!$weddingId) {
            $this->redirect(route('filament.admin.pages.dashboard'));
            return;
        }

        // If site ID provided, redirect to edit route
        if ($site) {
            $siteLayout = SiteLayout::where('id', $site)
                ->where('wedding_id', $weddingId)
                ->first();

            if (!$siteLayout) {
                $this->redirect(route('filament.admin.resources.site-layouts.index'));
                return;
            }

            // Redirect to Inertia-based editor
            $this->redirect(route('sites.edit', ['site' => $site]));
            return;
        }

        // No site ID - redirect to create/templates page
        $this->redirect(route('sites.create'));
    }

    public function getTitle(): string|Htmlable
    {
        return 'Editor Visual';
    }

    public static function getNavigationLabel(): string
    {
        return 'Editor Visual';
    }
}
