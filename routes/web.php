<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\Auth\FilamentLoginController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MediaScreenController;
use App\Http\Controllers\PublicSiteController;
use App\Models\SiteLayout;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

// Partner invite routes (public)
Route::prefix('convite')->group(function () {
    Route::get('/{token}', [InviteController::class, 'show'])->name('convite.show');
    Route::post('/{token}/aceitar-novo', [InviteController::class, 'acceptNewUser'])->name('convite.accept.new');
    Route::post('/{token}/aceitar', [InviteController::class, 'acceptExistingUser'])
        ->middleware('auth')
        ->name('convite.accept.existing');
    Route::post('/{token}/recusar', [InviteController::class, 'decline'])->name('convite.decline');
});

// Simple login page (fallback when JavaScript doesn't work)
Route::get('/admin/simple-login', function () {
    return view('auth.filament-login');
})->name('filament.admin.auth.simple-login');

// Alias for login route (used by Laravel's authentication system)
Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

// Fallback POST route for Filament login when JavaScript is disabled
Route::post('/admin/login', [FilamentLoginController::class, 'store'])
    ->middleware(['web'])
    ->name('filament.admin.auth.login.post');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

// Site Editor routes (Inertia-based)
Route::middleware(['auth', 'wedding.inertia'])->prefix('admin')->group(function () {
    // Media Screen routes
    Route::get('/midias', [MediaScreenController::class, 'index'])->name('midias.index');
    Route::get('/albums', [AlbumController::class, 'index'])->name('albums.index');
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::put('/albums/{id}', [AlbumController::class, 'update'])->name('albums.update');
    Route::delete('/albums/{id}', [AlbumController::class, 'destroy'])->name('albums.destroy');
    Route::get('/albums/{id}/media', [AlbumController::class, 'media'])->name('albums.media');
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::post('/media/batch-move', [MediaController::class, 'batchMove'])->name('media.batch-move');
    Route::post('/media/{id}/crop', [MediaController::class, 'crop'])->name('media.crop');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
    
    // Site API routes
    Route::put('/sites/{site}/draft', [\App\Http\Controllers\Api\SiteLayoutController::class, 'updateDraft'])->name('sites.update-draft');
    Route::put('/sites/{site}/settings', [\App\Http\Controllers\Api\SiteLayoutController::class, 'updateSettings'])->name('sites.update-settings');
    Route::get('/sites/{site}/qa', [\App\Http\Controllers\Api\SiteLayoutController::class, 'qa'])->name('sites.qa');
    Route::post('/sites/{site}/publish', [\App\Http\Controllers\Api\SiteLayoutController::class, 'publish'])->name('sites.publish');
    Route::post('/sites/{site}/rollback', [\App\Http\Controllers\Api\SiteLayoutController::class, 'rollback'])->name('sites.rollback');
    
    // Create new site - creates site and redirects to editor
    Route::get('/sites/create', function () {
        $user = auth()->user();
        $weddingId = $user->current_wedding_id;
        
        // Check if wedding already has a site
        $existingSite = SiteLayout::where('wedding_id', $weddingId)->first();
        if ($existingSite) {
            return redirect()->route('sites.edit', ['site' => $existingSite->id]);
        }
        
        // Create new site using the SiteBuilderService
        $wedding = \App\Models\Wedding::findOrFail($weddingId);
        $siteBuilder = app(\App\Contracts\Site\SiteBuilderServiceInterface::class);
        
        try {
            $site = $siteBuilder->create($wedding);
            return redirect()->route('sites.edit', ['site' => $site->id]);
        } catch (\Exception $e) {
            return redirect()->route('filament.admin.resources.site-layouts.index')
                ->with('error', 'Erro ao criar site: ' . $e->getMessage());
        }
    })->name('sites.create');
    
    // Edit existing site
    Route::get('/sites/{site}/edit', function (string $site) {
        $user = auth()->user();
        $weddingId = $user->current_wedding_id;
        
        $siteLayout = SiteLayout::with(['wedding', 'versions' => function ($query) {
            $query->latest()->limit(10);
        }])
            ->where('id', $site)
            ->where('wedding_id', $weddingId)
            ->first();
        
        if (!$siteLayout) {
            return redirect()->route('filament.admin.resources.site-layouts.index');
        }
        
        // Convert to array to ensure all data is properly serialized
        $siteData = $siteLayout->toArray();
        $siteData['draft_content'] = $siteLayout->draft_content;
        $siteData['published_content'] = $siteLayout->published_content;
        
        // Load wedding data for placeholders
        $wedding = $siteLayout->wedding;
        
        // Load gift registry config for the wedding
        $wedding->load('giftRegistryConfig');
        
        return Inertia::render('Sites/Editor', [
            'site' => $siteData,
            'weddingId' => $weddingId,
            'wedding' => $wedding,
        ]);
    })->name('sites.edit');
    
    // Templates page (for applying templates to existing site)
    Route::get('/sites/{site}/templates', function (string $site) {
        $user = auth()->user();
        $weddingId = $user->current_wedding_id;
        
        $siteLayout = SiteLayout::where('id', $site)
            ->where('wedding_id', $weddingId)
            ->first();
        
        if (!$siteLayout) {
            return redirect()->route('filament.admin.resources.site-layouts.index');
        }
        
        return Inertia::render('Sites/Templates', [
            'site' => $siteLayout,
            'weddingId' => $weddingId,
        ]);
    })->name('sites.templates');
});

require __DIR__.'/auth.php';

// Public site routes (must be at the end to avoid conflicts with other routes)
Route::get('/site/{slug}', [PublicSiteController::class, 'show'])
    ->name('public.site.show');

Route::post('/site/{slug}/auth', [PublicSiteController::class, 'authenticate'])
    ->name('public.site.authenticate');

Route::get('/site/{slug}/calendar', [PublicSiteController::class, 'calendar'])
    ->name('public.site.calendar');

// Manual route registration for broken Filament resource
Route::middleware(['web', \Filament\Http\Middleware\Authenticate::class])->group(function () {
    Route::get('/admin/albums', \App\Filament\Resources\AlbumResource\Pages\ListAlbums::class)
        ->name('filament.admin.resources.albums.index');
});
