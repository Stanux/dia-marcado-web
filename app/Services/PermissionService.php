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
        'sites' => 'Criação de Sites',
        'tasks' => 'Gestão de Tarefas',
        'guests' => 'Convidados',
        'finance' => 'Financeiro',
        'reports' => 'Relatórios',
        'app' => 'APP',
        'users' => 'Gestão de Usuários',
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
     * Check if a user can access a specific module
     */
    public function canAccess(User $user, string $module, ?Wedding $wedding = null): bool
    {
        // Validate module exists
        if (!array_key_exists($module, self::MODULES)) {
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
                return in_array($module, self::GUEST_MODULES);
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
                return in_array($module, self::GUEST_MODULES);
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
            return in_array($module, $permissions);
        }

        // Guest in wedding context - only app module
        if ($pivot->role === 'guest') {
            return in_array($module, self::GUEST_MODULES);
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
            return $pivot->permissions ?? [];
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
