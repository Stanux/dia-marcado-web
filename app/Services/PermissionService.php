<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wedding;

class PermissionService
{
    /**
     * Available modules in the system
     */
    public const MODULES = [
        'event_data' => 'Dados do Evento',
        'gift_list' => 'Lista de presentes',
        'receipts' => 'Recebimentos',
        'site_editor' => 'Editor do Site',
        'events' => 'Eventos',
        'guests' => 'Convidados',
        'invites' => 'Convites',
        'plans' => 'Planejamentos',
        'vendors' => 'Fornecedores',
        'users' => 'Gestão de Usuários',
        'app' => 'APP',
    ];

    /**
     * Legacy module keys mapped to canonical keys.
     */
    public const MODULE_ALIASES = [
        'sites' => 'site_editor',
        'tasks' => 'plans',
        'finance' => 'receipts',
        'reports' => 'event_data',
    ];

    /**
     * User roles in the system
     */
    public const ROLES = [
        'admin' => 'Admin',
        'couple' => 'Noivo/Noiva',
        'organizer' => 'Organizador',
        'guest' => 'Convidado',
    ];

    /**
     * Modules accessible by guests
     */
    public const GUEST_MODULES = ['app'];

    /**
     * Normalize module key to canonical key.
     */
    public static function normalizeModule(string $module): ?string
    {
        $normalized = strtolower(trim($module));

        if (array_key_exists($normalized, self::MODULE_ALIASES)) {
            $normalized = self::MODULE_ALIASES[$normalized];
        }

        if (!array_key_exists($normalized, self::MODULES)) {
            return null;
        }

        return $normalized;
    }

    /**
     * Normalize and deduplicate permission list.
     *
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    public static function normalizePermissions(array $permissions): array
    {
        $normalized = [];

        foreach ($permissions as $permission) {
            if (!is_string($permission)) {
                continue;
            }

            $canonical = self::normalizeModule($permission);
            if (!$canonical) {
                continue;
            }

            $normalized[$canonical] = $canonical;
        }

        return array_values($normalized);
    }

    /**
     * Check if a user can access a specific module
     */
    public function canAccess(User $user, string $module, ?Wedding $wedding = null): bool
    {
        $module = self::normalizeModule($module) ?? '';

        // Validate module exists
        if ($module === '') {
            return false;
        }

        // Admin has full access (check global role)
        if ($user->role === 'admin') {
            return true;
        }

        // For all other users, wedding context is required
        if (!$wedding) {
            // Guest without wedding context can only access guest modules
            if ($user->role === 'guest') {
                return in_array($module, self::GUEST_MODULES, true);
            }
            return false;
        }

        // Get user's pivot data for this wedding
        $pivot = $user->weddings()
            ->where('wedding_id', $wedding->id)
            ->first()
            ?->pivot;

        if (!$pivot) {
            // User has no relationship with this wedding
            // Fall back to global role for guests
            if ($user->role === 'guest') {
                return in_array($module, self::GUEST_MODULES, true);
            }
            return false;
        }

        // Couple has full access to their wedding
        if ($pivot->role === 'couple') {
            return true;
        }

        // Organizer - check specific permissions
        if ($pivot->role === 'organizer') {
            $permissions = $pivot->permissions ?? [];
            // Handle case where permissions is stored as JSON string
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true) ?? [];
            }
            $normalizedPermissions = self::normalizePermissions(is_array($permissions) ? $permissions : []);
            return in_array($module, $normalizedPermissions, true);
        }

        // Guest in wedding context - only app module
        if ($pivot->role === 'guest') {
            return in_array($module, self::GUEST_MODULES, true);
        }

        return false;
    }

    /**
     * Get all accessible modules for a user in a wedding context
     */
    public function getAccessibleModules(User $user, ?Wedding $wedding = null): array
    {
        if ($user->role === 'admin') {
            return array_keys(self::MODULES);
        }

        if ($user->role === 'guest') {
            return self::GUEST_MODULES;
        }

        if (!$wedding) {
            return [];
        }

        $pivot = $user->weddings()
            ->where('wedding_id', $wedding->id)
            ->first()
            ?->pivot;

        if (!$pivot) {
            return [];
        }

        if ($pivot->role === 'couple') {
            return array_keys(self::MODULES);
        }

        if ($pivot->role === 'organizer') {
            $permissions = $pivot->permissions ?? [];
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true) ?? [];
            }

            return self::normalizePermissions(is_array($permissions) ? $permissions : []);
        }

        if ($pivot->role === 'guest') {
            return self::GUEST_MODULES;
        }

        return [];
    }

    /**
     * Check if user has access to a wedding
     */
    public function hasWeddingAccess(User $user, Wedding $wedding): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->weddings()
            ->where('wedding_id', $wedding->id)
            ->exists();
    }

    /**
     * Get user's role in a specific wedding
     */
    public function getWeddingRole(User $user, Wedding $wedding): ?string
    {
        if ($user->role === 'admin') {
            return 'admin';
        }

        $pivot = $user->weddings()
            ->where('wedding_id', $wedding->id)
            ->first()
            ?->pivot;

        return $pivot?->role;
    }
}
