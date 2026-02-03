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
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::put('/albums/{id}', [AlbumController::class, 'update'])->name('albums.update');
    Route::delete('/albums/{id}', [AlbumController::class, 'destroy'])->name('albums.destroy');
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::post('/media/batch-move', [MediaController::class, 'batchMove'])->name('media.batch-move');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
    
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
        
        return Inertia::render('Sites/Editor', [
            'site' => $siteData,
            'weddingId' => $weddingId,
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
