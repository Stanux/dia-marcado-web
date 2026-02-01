<?php

namespace App\Http\Middleware;

use App\Models\Wedding;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if user has permission to access a specific module.
 * 
 * If user loses permission while using a module, redirects to home (web) or returns JSON error (API).
 */
class CheckModulePermission
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Autenticação necessária.',
                ], 401);
            }
            return redirect()->route('filament.admin.auth.login');
        }

        // Admin has full access
        if ($user->isAdmin()) {
            return $next($request);
        }

        $weddingId = session('filament_wedding_id') ?? $user->current_wedding_id;

        if (!$weddingId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Bad Request',
                    'message' => 'Selecione um casamento primeiro.',
                ], 400);
            }
            return redirect()->route('filament.admin.pages.dashboard')
                ->with('error', 'Selecione um casamento primeiro.');
        }

        $wedding = Wedding::find($weddingId);

        if (!$wedding) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Casamento não encontrado.',
                ], 404);
            }
            return redirect()->route('filament.admin.pages.dashboard')
                ->with('error', 'Casamento não encontrado.');
        }

        // Check if user has permission for this module
        if (!$this->permissionService->canAccess($user, $module, $wedding)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Você não tem permissão para acessar este módulo.',
                ], 403);
            }
            return redirect()->route('filament.admin.pages.dashboard')
                ->with('error', 'Você não tem permissão para acessar este módulo.');
        }

        return $next($request);
    }
}
