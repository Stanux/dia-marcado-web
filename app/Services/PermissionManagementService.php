<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wedding;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PermissionManagementService
{
    public const AVAILABLE_MODULES = [
        'event_data' => 'Dados do Evento',
        'gift_list' => 'Lista de presentes',
        'receipts' => 'Recebimentos',
        'site_editor' => 'Editor do Site',
        'events' => 'Eventos',
        'guests' => 'Convidados',
        'invites' => 'Convites',
        'plans' => 'Planejamentos',
        'vendors' => 'Fornecedores',
        'users' => 'Usuários',
        'app' => 'APP',
    ];

    /**
     * Update an organizer's permissions.
     *
     * @param User $editor
     * @param Wedding $wedding
     * @param User $organizer
     * @param array $permissions
     * @return void
     * @throws AccessDeniedHttpException
     * @throws \InvalidArgumentException
     */
    public function updateOrganizerPermissions(
        User $editor,
        Wedding $wedding,
        User $organizer,
        array $permissions
    ): void {
        $this->ensureCanManagePermissions($editor, $wedding);
        $this->ensureIsOrganizerInWedding($organizer, $wedding);
        $this->validatePermissions($permissions);

        $wedding->users()->updateExistingPivot($organizer->id, [
            'permissions' => $this->normalizeAvailablePermissions($permissions),
        ]);
    }

    /**
     * Get all organizers with their permissions for a wedding.
     *
     * @param User $viewer
     * @param Wedding $wedding
     * @return array
     * @throws AccessDeniedHttpException
     */
    public function getOrganizersWithPermissions(User $viewer, Wedding $wedding): array
    {
        $this->ensureCanManagePermissions($viewer, $wedding);

        return $wedding->organizers()
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $this->normalizeAvailablePermissions(
                    is_array($user->pivot->permissions ?? null) ? $user->pivot->permissions : []
                ),
            ])
            ->toArray();
    }

    /**
     * Get available modules list.
     *
     * @return array
     */
    public function getAvailableModules(): array
    {
        return self::AVAILABLE_MODULES;
    }

    /**
     * Ensure the user can manage permissions.
     *
     * @param User $user
     * @param Wedding $wedding
     * @throws AccessDeniedHttpException
     */
    private function ensureCanManagePermissions(User $user, Wedding $wedding): void
    {
        if (!$user->isAdmin() && !$user->isCoupleIn($wedding)) {
            throw new AccessDeniedHttpException(
                'Apenas Noivos e Administradores podem gerenciar permissões.'
            );
        }
    }

    /**
     * Ensure the user is an organizer in the wedding.
     *
     * @param User $user
     * @param Wedding $wedding
     * @throws AccessDeniedHttpException
     */
    private function ensureIsOrganizerInWedding(User $user, Wedding $wedding): void
    {
        if (!$user->isOrganizerIn($wedding)) {
            throw new AccessDeniedHttpException(
                'O usuário não é um Organizador deste casamento.'
            );
        }
    }

    /**
     * Validate that all permissions are valid modules.
     *
     * @param array $permissions
     * @throws \InvalidArgumentException
     */
    private function validatePermissions(array $permissions): void
    {
        $validModules = array_keys(self::AVAILABLE_MODULES);
        foreach ($permissions as $permission) {
            $normalized = is_string($permission) ? PermissionService::normalizeModule($permission) : null;
            if (!$normalized || !in_array($normalized, $validModules, true)) {
                throw new \InvalidArgumentException(
                    "Módulo inválido: {$permission}"
                );
            }
        }
    }

    /**
     * Normalize permissions and keep only modules that can be assigned from UI.
     *
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    private function normalizeAvailablePermissions(array $permissions): array
    {
        $validModules = array_keys(self::AVAILABLE_MODULES);
        $normalized = PermissionService::normalizePermissions($permissions);

        return array_values(array_filter(
            $normalized,
            fn (string $permission): bool => in_array($permission, $validModules, true)
        ));
    }
}
