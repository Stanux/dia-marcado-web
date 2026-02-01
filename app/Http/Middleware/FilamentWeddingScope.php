<?php

namespace App\Http\Middleware;

use App\Models\Wedding;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for FilamentPHP that ensures wedding context and permissions.
 * 
 * This middleware:
 * - Sets the current wedding context for non-admin users
 * - Verifies the user has access to the selected wedding
 * - Works with WeddingScope to filter data automatically
 */
class FilamentWeddingScope
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Admin users have full access without wedding context requirement
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Allow access to create-first-wedding page without wedding context
        if ($request->routeIs('filament.admin.pages.criar-casamento')) {
            return $next($request);
        }

        // Allow access to onboarding page without wedding context
        if ($request->routeIs('filament.admin.pages.onboarding')) {
            return $next($request);
        }

        // Get wedding ID from session, query param, or user's current wedding
        $weddingId = session('filament_wedding_id')
            ?? $request->query('wedding')
            ?? $user->current_wedding_id;

        // If no wedding context, try to set the first available wedding
        if (!$weddingId) {
            $firstWedding = $user->weddings()->first();
            
            if ($firstWedding) {
                $weddingId = $firstWedding->id;
                session(['filament_wedding_id' => $weddingId]);
            }
        }

        // Validate wedding exists and user has access
        if ($weddingId) {
            $wedding = Wedding::find($weddingId);

            if (!$wedding) {
                session()->forget('filament_wedding_id');
                return $this->handleNoWeddingAccess($request, $user);
            }

            // Verify user has access to this wedding
            if (!$this->permissionService->hasWeddingAccess($user, $wedding)) {
                session()->forget('filament_wedding_id');
                return $this->handleNoWeddingAccess($request, $user);
            }

            // Set current wedding context on user for WeddingScope
            $user->current_wedding_id = $weddingId;
            session(['filament_wedding_id' => $weddingId]);
        } else {
            return $this->handleNoWeddingAccess($request, $user);
        }

        return $next($request);
    }

    /**
     * Handle case when user has no wedding access.
     */
    protected function handleNoWeddingAccess(Request $request, $user): Response
    {
        // Check if user has any weddings
        $firstWedding = $user->weddings()->first();

        if ($firstWedding) {
            $user->current_wedding_id = $firstWedding->id;
            session(['filament_wedding_id' => $firstWedding->id]);
            return redirect()->back();
        }

        // User has no weddings
        // If user hasn't completed onboarding, redirect to onboarding
        if (!$user->hasCompletedOnboarding()) {
            return redirect()->route('filament.admin.pages.onboarding');
        }

        // User completed onboarding but has no weddings - redirect to create first wedding page
        // Only for couple users who can create weddings
        if ($user->role === 'couple') {
            return redirect()->route('filament.admin.pages.criar-casamento');
        }

        // Non-couple users without weddings cannot proceed
        abort(403, 'Você não tem acesso a nenhum casamento. Entre em contato com um administrador.');
    }
}
