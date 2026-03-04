<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserManagementService
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Create a couple user linked to a wedding.
     *
     * @param User $creator
     * @param Wedding $wedding
     * @param array $data
     * @return User
     * @throws AccessDeniedHttpException
     * @throws ValidationException
     */
    public function createCouple(
        User $creator,
        Wedding $wedding,
        array $data
    ): User {
        $this->ensureCanCreateCouple($creator, $wedding);
        $this->validateUserData($data, ['name', 'email']);
        $this->ensureEmailIsUnique($data['email']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'] ?? Str::random(16)),
            'role' => 'couple',
            'created_by' => $creator->id,
            // Usuário criado via Gestão de Usuários já entra com contexto válido
            // para evitar redirecionamento indevido ao onboarding.
            'current_wedding_id' => $wedding->id,
            'onboarding_completed' => true,
        ]);

        $wedding->users()->attach($user->id, [
            'role' => 'couple',
            'permissions' => [],
        ]);

        return $user;
    }

    /**
     * Create an organizer user linked to a wedding.
     *
     * @param User $creator
     * @param Wedding $wedding
     * @param array $data
     * @param array $permissions
     * @return User
     * @throws AccessDeniedHttpException
     * @throws ValidationException
     */
    public function createOrganizer(
        User $creator,
        Wedding $wedding,
        array $data,
        array $permissions = []
    ): User {
        $this->ensureCanCreateOrganizer($creator, $wedding);
        $this->validateUserData($data, ['name', 'email']);

        $existingUser = $this->findUserByEmail($data['email']);

        if ($existingUser) {
            $this->attachExistingOrganizerToWedding(
                $existingUser,
                $wedding,
                $permissions
            );

            return $existingUser->refresh();
        }

        $this->ensureEmailIsUnique($data['email']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'] ?? Str::random(16)),
            'role' => 'organizer',
            'created_by' => $creator->id,
            'current_wedding_id' => $wedding->id,
            'onboarding_completed' => true,
        ]);

        $wedding->users()->attach($user->id, [
            'role' => 'organizer',
            'permissions' => $permissions,
        ]);

        return $user;
    }

    /**
     * Create a guest user linked to a wedding.
     *
     * @param User $creator
     * @param Wedding $wedding
     * @param array $data
     * @return User
     * @throws AccessDeniedHttpException
     * @throws ValidationException
     */
    public function createGuest(
        User $creator,
        Wedding $wedding,
        array $data
    ): User {
        $this->ensureCanCreateGuest($creator, $wedding);
        $this->validateUserData($data, ['name', 'email']);
        $this->ensureEmailIsUnique($data['email']);

        $password = $data['password'] ?? Str::random(16);
        $sendInvite = empty($data['password']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'role' => 'guest',
            'created_by' => $creator->id,
            'current_wedding_id' => $wedding->id,
            'onboarding_completed' => true,
        ]);

        $wedding->users()->attach($user->id, [
            'role' => 'guest',
            'permissions' => [],
        ]);

        if ($sendInvite) {
            // TODO: Dispatch invite email job
            // InviteGuestJob::dispatch($user, $wedding);
        }

        return $user;
    }

    /**
     * Create an admin user.
     *
     * @param User $creator
     * @param array $data
     * @return User
     * @throws AccessDeniedHttpException
     * @throws ValidationException
     */
    public function createAdmin(User $creator, array $data): User
    {
        $this->ensureCanCreateAdmin($creator);
        $this->validateUserData($data, ['name', 'email', 'password']);
        $this->ensureEmailIsUnique($data['email']);

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'created_by' => $creator->id,
        ]);
    }

    /**
     * Remove a user from a wedding (keeps the user record).
     *
     * @param User $user
     * @param Wedding $wedding
     * @return void
     */
    public function removeFromWedding(User $user, Wedding $wedding): void
    {
        $wedding->users()->detach($user->id);

        $user->refresh();

        if ($user->current_wedding_id !== $wedding->id) {
            return;
        }

        $nextWeddingId = $user->weddings()
            ->pluck('weddings.id')
            ->first();

        $user->forceFill([
            'current_wedding_id' => $nextWeddingId ?: null,
        ])->saveQuietly();
    }

    /**
     * Validate that required fields are present.
     *
     * @param array $data
     * @param array $required
     * @throws ValidationException
     */
    private function validateUserData(array $data, array $required): void
    {
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ["O campo {$field} é obrigatório."];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Ensure email is not already in use.
     *
     * @param string $email
     * @throws ValidationException
     */
    private function ensureEmailIsUnique(string $email): void
    {
        if ($this->findUserByEmail($email)) {
            throw ValidationException::withMessages([
                'email' => ['Este email já está em uso.'],
            ]);
        }
    }

    private function findUserByEmail(string $email): ?User
    {
        $normalizedEmail = Str::lower(trim($email));

        if ($normalizedEmail === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();
    }

    private function attachExistingOrganizerToWedding(
        User $existingUser,
        Wedding $wedding,
        array $permissions
    ): void {
        if ($existingUser->role !== 'organizer') {
            throw ValidationException::withMessages([
                'email' => ['Este e-mail já pertence a um usuário com outro tipo.'],
            ]);
        }

        $alreadyLinked = $existingUser->weddings()
            ->where('wedding_id', $wedding->id)
            ->exists();

        if ($alreadyLinked) {
            throw ValidationException::withMessages([
                'email' => ['Este organizador já está vinculado a este casamento.'],
            ]);
        }

        $existingUser->weddings()->attach($wedding->id, [
            'role' => 'organizer',
            'permissions' => $permissions,
        ]);

        $updates = [];

        if (!$existingUser->current_wedding_id) {
            $updates['current_wedding_id'] = $wedding->id;
        }

        if (!$existingUser->hasCompletedOnboarding()) {
            $updates['onboarding_completed'] = true;
        }

        if (!empty($updates)) {
            $existingUser->forceFill($updates)->save();
        }
    }

    /**
     * Ensure the creator can create an organizer.
     *
     * @param User $creator
     * @param Wedding $wedding
     * @throws AccessDeniedHttpException
     */
    private function ensureCanCreateOrganizer(User $creator, Wedding $wedding): void
    {
        if (!$creator->isAdmin() && !$creator->isCoupleIn($wedding)) {
            throw new AccessDeniedHttpException(
                'Apenas Noivos podem criar Organizadores.'
            );
        }
    }

    /**
     * Ensure the creator can create a couple.
     *
     * @param User $creator
     * @param Wedding $wedding
     * @throws AccessDeniedHttpException
     */
    private function ensureCanCreateCouple(User $creator, Wedding $wedding): void
    {
        if (!$creator->isAdmin() && !$creator->isCoupleIn($wedding)) {
            throw new AccessDeniedHttpException(
                'Apenas Noivos podem adicionar outros Noivos.'
            );
        }
    }

    /**
     * Ensure the creator can create a guest.
     *
     * @param User $creator
     * @param Wedding $wedding
     * @throws AccessDeniedHttpException
     */
    private function ensureCanCreateGuest(User $creator, Wedding $wedding): void
    {
        $canCreate = $creator->isAdmin()
            || $creator->isCoupleIn($wedding)
            || ($creator->isOrganizerIn($wedding)
                && $creator->hasPermissionIn($wedding, 'users'));

        if (!$canCreate) {
            throw new AccessDeniedHttpException(
                'Você não tem permissão para criar Convidados.'
            );
        }
    }

    /**
     * Ensure the creator can create an admin.
     *
     * @param User $creator
     * @throws AccessDeniedHttpException
     */
    private function ensureCanCreateAdmin(User $creator): void
    {
        if (!$creator->isAdmin()) {
            throw new AccessDeniedHttpException(
                'Apenas Administradores podem criar outros Administradores.'
            );
        }
    }
}
