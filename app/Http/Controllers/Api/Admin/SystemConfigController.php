<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing system configuration values.
 * 
 * Only accessible by Admin users.
 * Handles site-related configuration keys.
 * 
 * @Requirements: 21.1, 21.5
 */
class SystemConfigController extends Controller
{
    /**
     * List all site-related configuration values.
     * 
     * GET /api/admin/config
     * 
     * @Requirements: 21.1
     */
    public function index(Request $request): JsonResponse
    {
        // Verify user is admin
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Apenas administradores podem acessar as configurações do sistema.',
            ], 403);
        }

        // Get all configs with 'site.' prefix
        $configs = SystemConfig::where('key', 'like', 'site.%')
            ->orderBy('key')
            ->get()
            ->map(fn (SystemConfig $config) => [
                'key' => $config->key,
                'value' => $config->value,
                'description' => $config->description,
                'updated_at' => $config->updated_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $configs,
        ]);
    }

    /**
     * Update a specific configuration value.
     * 
     * PUT /api/admin/config/{key}
     * 
     * @Requirements: 21.5
     */
    public function update(Request $request, string $key): JsonResponse
    {
        // Verify user is admin
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Apenas administradores podem alterar as configurações do sistema.',
            ], 403);
        }

        // Validate that key starts with 'site.'
        if (!str_starts_with($key, 'site.')) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Apenas configurações com prefixo "site." podem ser alteradas por esta API.',
            ], 400);
        }

        // Validate request
        $validated = $request->validate([
            'value' => 'required',
            'description' => 'nullable|string|max:500',
        ]);

        // Check if config exists
        $config = SystemConfig::find($key);

        if (!$config) {
            return response()->json([
                'error' => 'Not Found',
                'message' => "Configuração '{$key}' não encontrada.",
            ], 404);
        }

        // Update the config (this also clears the cache)
        SystemConfig::set($key, $validated['value']);

        // Update description if provided
        if (isset($validated['description'])) {
            $config->refresh();
            $config->description = $validated['description'];
            $config->save();
        }

        // Reload to get fresh data
        $config->refresh();

        return response()->json([
            'data' => [
                'key' => $config->key,
                'value' => $config->value,
                'description' => $config->description,
                'updated_at' => $config->updated_at?->toIso8601String(),
            ],
            'message' => 'Configuração atualizada com sucesso.',
        ]);
    }
}
