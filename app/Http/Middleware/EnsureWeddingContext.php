<?php

namespace App\Http\Middleware;

use App\Models\Wedding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWeddingContext
{
    /**
     * Handle an incoming request.
     * 
     * Validates that the request has a valid wedding context for non-admin users.
     * Sets the current_wedding_id on the user for use in WeddingScope.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token not provided',
            ], 401);
        }

        // Admin users don't require wedding context
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Get wedding ID from header, route, or user's current wedding
        $weddingId = $request->header('X-Wedding-ID')
            ?? $request->route('wedding')
            ?? $user->current_wedding_id;

        if (!$weddingId) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Wedding context required',
            ], 401);
        }

        // Validate wedding exists
        $wedding = Wedding::find($weddingId);

        if (!$wedding) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid wedding',
            ], 401);
        }

        // Verify user has access to this wedding
        $hasAccess = $user->weddings()
            ->where('wedding_id', $weddingId)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Access to this wedding is not allowed',
            ], 403);
        }

        // Set current wedding context on user
        $user->current_wedding_id = $weddingId;

        return $next($request);
    }
}
