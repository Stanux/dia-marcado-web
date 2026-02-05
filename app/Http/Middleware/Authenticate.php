<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // If it's an Inertia request or expects JSON, don't redirect
        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return null;
        }

        // Redirect to Filament admin login
        return route('filament.admin.auth.login');
    }
}
