<?php

namespace App\Http\Middleware;

use App\Models\Wedding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure wedding context is available for Inertia routes.
 * 
 * This middleware reads the wedding context from session (set by Filament)
 * and makes it available on the user model.
 */
class EnsureWeddingContextForInertia
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Prefer explicit context passed by editor/navigation, then fallback to session/current user.
        $weddingId = $request->header('X-Wedding-ID')
            ?? $request->query('wedding_id')
            ?? session('filament_wedding_id')
            ?? $user->current_wedding_id;

        if (!$weddingId) {
            // Try to get the first wedding the user has access to
            $firstWedding = $user->weddings()->first();
            
            if ($firstWedding) {
                $weddingId = $firstWedding->id;
                session(['filament_wedding_id' => $weddingId]);
            }
        }

        if ($weddingId) {
            // Verify wedding exists and user has access
            $wedding = Wedding::find($weddingId);
            
            if ($wedding && $user->hasAccessTo($wedding)) {
                $user->current_wedding_id = $weddingId;
            } else {
                session()->forget('filament_wedding_id');
                return redirect()->route('filament.admin.pages.dashboard');
            }
        } else {
            // No wedding context available
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return $next($request);
    }
}
