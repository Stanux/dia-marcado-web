<?php

use App\Http\Controllers\Api\Admin\SystemConfigController;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\DevPaymentController;
use App\Http\Controllers\Api\GiftController;
use App\Http\Controllers\Api\GuestCheckinController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\GuestEventController;
use App\Http\Controllers\Api\GuestHouseholdController;
use App\Http\Controllers\Api\GuestInviteController;
use App\Http\Controllers\Api\GuestMessageController;
use App\Http\Controllers\Api\GuestRsvpController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\QuotaController;
use App\Http\Controllers\Api\SiteLayoutController;
use App\Http\Controllers\Api\SiteMediaController;
use App\Http\Controllers\Api\SiteTemplateController;
use App\Http\Controllers\WebhookController;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Webhook routes (no authentication required - validated by signature)
Route::post('/webhooks/pagseguro', [WebhookController::class, 'handle'])->name('webhooks.pagseguro');

// Public gift registry routes (no authentication required)
Route::prefix('events/{eventId}/gifts')->group(function () {
    Route::get('/', [GiftController::class, 'index']);
    Route::get('/{giftId}', [GiftController::class, 'show']);
    Route::post('/{giftId}/purchase', [GiftController::class, 'purchase']);
});

if (app()->environment('local')) {
    Route::post('/dev/transactions/{internalId}/confirm', [DevPaymentController::class, 'confirm']);
}

Route::middleware(['auth:sanctum'])->group(function () {
    // User info (no wedding context required)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Wedding-scoped routes
    Route::middleware(['wedding.context'])->group(function () {
        // Wedding info
        Route::get('/wedding', function (Request $request) {
            return response()->json([
                'wedding_id' => $request->user()->current_wedding_id,
                'user' => $request->user(),
            ]);
        });

        // Tasks module
        Route::middleware(['permission:tasks'])->group(function () {
            Route::get('/tasks', function (Request $request) {
                return Task::all();
            });

            Route::post('/tasks', function (Request $request) {
                $validated = $request->validate([
                    'title' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'status' => 'nullable|in:pending,in_progress,completed,cancelled',
                    'due_date' => 'nullable|date',
                ]);

                return Task::create($validated);
            });
        });

        // Guests module
        Route::middleware(['permission:guests'])->prefix('guests')->group(function () {
            // Guests
            Route::get('/', [GuestController::class, 'index']);
            Route::post('/', [GuestController::class, 'store']);
            Route::get('/{guest}', [GuestController::class, 'show']);
            Route::put('/{guest}', [GuestController::class, 'update']);
            Route::delete('/{guest}', [GuestController::class, 'destroy']);

            // Households
            Route::get('/households', [GuestHouseholdController::class, 'index']);
            Route::post('/households', [GuestHouseholdController::class, 'store']);
            Route::get('/households/{household}', [GuestHouseholdController::class, 'show']);
            Route::put('/households/{household}', [GuestHouseholdController::class, 'update']);
            Route::delete('/households/{household}', [GuestHouseholdController::class, 'destroy']);

            // Events
            Route::get('/events', [GuestEventController::class, 'index']);
            Route::post('/events', [GuestEventController::class, 'store']);
            Route::get('/events/{event}', [GuestEventController::class, 'show']);
            Route::put('/events/{event}', [GuestEventController::class, 'update']);
            Route::delete('/events/{event}', [GuestEventController::class, 'destroy']);

            // Invites
            Route::post('/households/{household}/invites', [GuestInviteController::class, 'store']);
            Route::post('/invites/{invite}/reissue', [GuestInviteController::class, 'reissue']);

            // RSVP (authenticated)
            Route::post('/rsvp', [GuestRsvpController::class, 'store']);

            // Check-in
            Route::post('/checkins', [GuestCheckinController::class, 'store']);

            // Messages (drafts/templates)
            Route::get('/messages', [GuestMessageController::class, 'index']);
            Route::post('/messages', [GuestMessageController::class, 'store']);
        });

        // Finance module
        Route::middleware(['permission:finance'])->prefix('finance')->group(function () {
            Route::get('/', function () {
                return response()->json(['message' => 'Finance endpoint']);
            });
        });

        // Reports module
        Route::middleware(['permission:reports'])->prefix('reports')->group(function () {
            Route::get('/', function () {
                return response()->json(['message' => 'Reports endpoint']);
            });
        });

        // Sites module
        Route::middleware(['permission:sites'])->prefix('sites')->group(function () {
            Route::get('/', [SiteLayoutController::class, 'index']);
            Route::post('/', [SiteLayoutController::class, 'store']);
            Route::get('/{site}', [SiteLayoutController::class, 'show']);
            Route::put('/{site}/draft', [SiteLayoutController::class, 'updateDraft']);
            Route::put('/{site}/settings', [SiteLayoutController::class, 'updateSettings']);
            Route::post('/{site}/publish', [SiteLayoutController::class, 'publish']);
            Route::post('/{site}/rollback', [SiteLayoutController::class, 'rollback']);
            Route::get('/{site}/versions', [SiteLayoutController::class, 'versions']);
            Route::post('/{site}/restore', [SiteLayoutController::class, 'restore']);
            Route::get('/{site}/preview', [SiteLayoutController::class, 'preview']);
            Route::get('/{site}/qa', [SiteLayoutController::class, 'qa']);

            // Media routes (site-scoped)
            Route::get('/{site}/media', [SiteMediaController::class, 'index']);
            Route::post('/{site}/media', [SiteMediaController::class, 'store']);
            Route::get('/{site}/media/usage', [SiteMediaController::class, 'usage']);
            Route::delete('/{site}/media/{media}', [SiteMediaController::class, 'destroy']);

            // Template routes
            Route::get('/templates', [SiteTemplateController::class, 'index']);
            Route::post('/templates', [SiteTemplateController::class, 'store']);
            Route::get('/templates/{template}', [SiteTemplateController::class, 'show']);
            Route::post('/{site}/apply-template/{template}', [SiteTemplateController::class, 'apply']);
        });

        // Media module (wedding-scoped)
        Route::prefix('media')->group(function () {
            // Batch operations
            Route::post('/batch', [MediaController::class, 'createBatch']);
            Route::get('/batch/{batch}', [MediaController::class, 'batchStatus']);
            Route::post('/batch/{batch}/upload', [MediaController::class, 'uploadFile']);
            
            // Batch delete and move
            Route::post('/batch-delete', [MediaController::class, 'batchDestroy']);
            Route::post('/batch-move', [MediaController::class, 'batchMove']);
            
            // Individual media operations
            Route::get('/', [MediaController::class, 'index']);
            Route::get('/{media}', [MediaController::class, 'show']);
            Route::delete('/{media}', [MediaController::class, 'destroy']);
            Route::delete('/{media}/cancel', [MediaController::class, 'cancelUpload']);
            Route::post('/{media}/move', [MediaController::class, 'move']);
            Route::post('/{media}/crop', [MediaController::class, 'crop']);
        });

        // Albums module (wedding-scoped)
        Route::prefix('albums')->group(function () {
            Route::get('/types', [AlbumController::class, 'types']);
            Route::get('/', [AlbumController::class, 'index']);
            Route::post('/', [AlbumController::class, 'store']);
            Route::get('/{album}', [AlbumController::class, 'show']);
            Route::put('/{album}', [AlbumController::class, 'update']);
            Route::delete('/{album}', [AlbumController::class, 'destroy']);
            Route::get('/{album}/media', [AlbumController::class, 'media']);
        });

        // Quota module (wedding-scoped)
        Route::prefix('quota')->group(function () {
            Route::get('/', [QuotaController::class, 'show']);
            Route::post('/check', [QuotaController::class, 'checkUpload']);
        });
    });

    // APP module (for guests - gamification, RSVP, etc.)
    Route::middleware(['permission:app'])->prefix('app')->group(function () {
        Route::get('/notifications', function (Request $request) {
            return response()->json(['notifications' => []]);
        });

        Route::post('/rsvp', function (Request $request) {
            return response()->json(['message' => 'RSVP endpoint']);
        });

        Route::get('/gamification', function (Request $request) {
            return response()->json(['message' => 'Gamification endpoint']);
        });
    });

    // Admin routes (no wedding context required, admin only)
    // @Requirements: 21.1, 21.5
    Route::prefix('admin')->group(function () {
        Route::get('/config', [SystemConfigController::class, 'index']);
        Route::put('/config/{key}', [SystemConfigController::class, 'update']);
    });
});

// Public RSVP endpoint (open mode / token-based)
Route::post('/public/rsvp', [GuestRsvpController::class, 'publicStore']);
