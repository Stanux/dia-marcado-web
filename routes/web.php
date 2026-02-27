<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\Auth\FilamentLoginController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Planning\PlanExportController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\TemplateEditorController;
use App\Models\PartnerInvite;
use App\Models\SiteLayout;
use App\Models\SiteTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'time' => now()->toIso8601String(),
        'app' => config('app.name'),
    ]);
})->name('health');

Route::get('/ready', function () {
    $checks = [
        'db' => 'ok',
        'redis' => 'ok',
    ];
    $status = 200;

    try {
        DB::connection()->select('select 1');
    } catch (\Throwable $e) {
        $checks['db'] = 'error';
        $status = 503;
    }

    try {
        Redis::connection()->ping();
    } catch (\Throwable $e) {
        $checks['redis'] = 'error';
        $status = 503;
    }

    return response()->json([
        'status' => $status === 200 ? 'ok' : 'degraded',
        'checks' => $checks,
        'time' => now()->toIso8601String(),
    ], $status);
})->name('ready');

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

// Google OAuth routes
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');

// Fallback POST route for Filament login when JavaScript is disabled
Route::post('/admin/login', [FilamentLoginController::class, 'store'])
    ->middleware(['web', 'throttle:filament-login'])
    ->name('filament.admin.auth.login.post');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::middleware(['auth'])->prefix('admin/site-templates')->group(function () {
    Route::get('/{template}/editor', [TemplateEditorController::class, 'edit'])
        ->name('site-templates.editor');
});

// Site Editor routes (Inertia-based)
Route::middleware(['auth', 'wedding.inertia'])->prefix('admin')->group(function () {
    // Media routes used by Site Editor
    Route::get('/albums/list', [AlbumController::class, 'index'])->name('albums.list');
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::put('/albums/{id}', [AlbumController::class, 'update'])->name('albums.update');
    Route::delete('/albums/{id}', [AlbumController::class, 'destroy'])->name('albums.destroy');
    Route::get('/albums/{id}/media', [AlbumController::class, 'media'])->name('albums.media');
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::post('/media/batch-move', [MediaController::class, 'batchMove'])->name('media.batch-move');
    Route::patch('/media/{id}/rename', [MediaController::class, 'rename'])->name('media.rename');
    Route::post('/media/{id}/crop', [MediaController::class, 'crop'])->name('media.crop');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
    
    // Site API routes
    Route::put('/sites/{site}/draft', [\App\Http\Controllers\Api\SiteLayoutController::class, 'updateDraft'])->name('sites.update-draft');
    Route::put('/sites/{site}/settings', [\App\Http\Controllers\Api\SiteLayoutController::class, 'updateSettings'])->name('sites.update-settings');
    Route::get('/sites/{site}/preview', [\App\Http\Controllers\Api\SiteLayoutController::class, 'preview'])->name('sites.preview');
    Route::get('/sites/{site}/qa', [\App\Http\Controllers\Api\SiteLayoutController::class, 'qa'])->name('sites.qa');
    Route::get('/sites/{site}/versions', [\App\Http\Controllers\Api\SiteLayoutController::class, 'versions'])->name('sites.versions');
    Route::post('/sites/{site}/restore', [\App\Http\Controllers\Api\SiteLayoutController::class, 'restore'])->name('sites.restore');
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
    Route::get('/sites/{site}/edit', function (\Illuminate\Http\Request $request, string $site) {
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
        
        // Load gift registry config and guest events for the wedding
        $wedding->load(['giftRegistryConfig', 'guestEvents', 'couple']);
        $weddingData = $wedding->toArray();
        $weddingData['wedding_date'] = $wedding->wedding_date?->format('Y-m-d');
        $weddingData['has_wedding_date'] = $wedding->wedding_date !== null;

        // Build default logo initials from onboarding/couple data
        $coupleNames = $wedding->couple
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->values();

        // If partner is pending (invited during onboarding), include invite name as second initial source
        if ($coupleNames->count() < 2) {
            $pendingInvite = PartnerInvite::query()
                ->where('wedding_id', $wedding->id)
                ->where('status', 'pending')
                ->latest('created_at')
                ->first();

            if ($pendingInvite && is_string($pendingInvite->name) && trim($pendingInvite->name) !== '') {
                $coupleNames->push($pendingInvite->name);
            }
        }

        $logoInitials = $coupleNames
            ->take(2)
            ->map(function (string $fullName): string {
                $name = trim($fullName);

                if ($name === '') {
                    return '';
                }

                $parts = preg_split('/\s+/u', $name) ?: [];
                $firstName = $parts[0] ?? $name;

                return mb_strtoupper(mb_substr($firstName, 0, 1, 'UTF-8'), 'UTF-8');
            })
            ->pad(2, '')
            ->values()
            ->all();

        $templateContext = null;
        $templateId = $request->query('template');
        if (is_string($templateId) && trim($templateId) !== '') {
            $templateRecord = SiteTemplate::query()
                ->whereKey($templateId)
                ->first();

            if ($templateRecord && $templateRecord->editor_site_layout_id === $siteLayout->id) {
                $templateContext = [
                    'id' => $templateRecord->id,
                    'name' => $templateRecord->name,
                    'slug' => $templateRecord->slug,
                    'is_public' => (bool) $templateRecord->is_public,
                    'category' => $templateRecord->category?->name,
                ];
            }
        }
        
        return Inertia::render('Sites/Editor', [
            'site' => $siteData,
            'weddingId' => $weddingId,
            'wedding' => $weddingData,
            'logoInitials' => $logoInitials,
            'templateContext' => $templateContext,
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

// Planning export (Filament context)
Route::middleware(['auth', \App\Http\Middleware\FilamentWeddingScope::class])
    ->get('/admin/planejamentos/{plan}/export', PlanExportController::class)
    ->name('planning.export');

require __DIR__.'/auth.php';

// Public site routes (must be at the end to avoid conflicts with other routes)
Route::get('/site/template/{slug}', [PublicSiteController::class, 'showTemplate'])
    ->name('public.site.template.preview');

Route::get('/site/{slug}', [PublicSiteController::class, 'show'])
    ->name('public.site.show');

Route::post('/site/{slug}/auth', [PublicSiteController::class, 'authenticate'])
    ->name('public.site.authenticate');

Route::get('/site/{slug}/calendar', [PublicSiteController::class, 'calendar'])
    ->name('public.site.calendar');
