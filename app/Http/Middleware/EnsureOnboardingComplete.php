<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure users have completed the onboarding process.
 * 
 * Redirects users who haven't completed onboarding to the onboarding page.
 * Admin users are exempt from this check.
 */
class EnsureOnboardingComplete
{
    /**
     * Routes that are allowed without completing onboarding.
     */
    protected array $allowedRoutes = [
        'filament.admin.pages.onboarding',
        'filament.admin.auth.logout',
        'livewire.update',
    ];

    /**
     * Route prefixes that are allowed without completing onboarding.
     */
    protected array $allowedPrefixes = [
        'livewire',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No user, let other middleware handle authentication
        if (!$user) {
            return $next($request);
        }

        // Admin users are exempt from onboarding
        if ($user->isAdmin()) {
            return $next($request);
        }

        // User has completed onboarding, allow access
        if ($user->hasCompletedOnboarding()) {
            // If user tries to access onboarding page after completing, redirect to dashboard
            if ($this->isOnboardingRoute($request)) {
                return redirect()->route('filament.admin.pages.dashboard');
            }
            return $next($request);
        }

        // User hasn't completed onboarding
        // Allow access to onboarding-related routes
        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        // Redirect to onboarding page
        return redirect()->route('filament.admin.pages.onboarding');
    }

    /**
     * Check if the current route is the onboarding page.
     */
    protected function isOnboardingRoute(Request $request): bool
    {
        return $request->routeIs('filament.admin.pages.onboarding');
    }

    /**
     * Check if the current route is allowed without completing onboarding.
     */
    protected function isAllowedRoute(Request $request): bool
    {
        // Check exact route names
        foreach ($this->allowedRoutes as $route) {
            if ($request->routeIs($route)) {
                return true;
            }
        }

        // Check route prefixes
        $currentRoute = $request->route()?->getName() ?? '';
        foreach ($this->allowedPrefixes as $prefix) {
            if (str_starts_with($currentRoute, $prefix)) {
                return true;
            }
        }

        // Check if it's a Livewire request (for form interactions)
        if ($request->hasHeader('X-Livewire')) {
            return true;
        }

        return false;
    }
}
