<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserRegistrationService
{
    /**
     * Register a new user as couple (Noivos).
     * Public registration always creates users with role "couple".
     *
     * @param array $data
     * @return User
     * @throws ValidationException
     */
    public function registerCouple(array $data): User
    {
        $this->validateRegistrationData($data, ['name', 'email', 'password']);
        $this->ensureEmailIsUnique($data['email']);

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'couple',
        ]);
    }

    /**
     * Register a new user as couple via social login.
     *
     * @param array $data
     * @return User
     * @throws ValidationException
     */
    public function registerCoupleFromSocial(array $data): User
    {
        $this->validateRegistrationData($data, ['name', 'email']);
        $this->ensureEmailIsUnique($data['email']);

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(64)),
            'role' => 'couple',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Validate that all required fields are present.
     *
     * @param array $data
     * @param array<int, string> $required
     * @throws ValidationException
     */
    private function validateRegistrationData(array $data, array $required): void
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
     * Ensure the email is not already in use.
     *
     * @param string $email
     * @throws ValidationException
     */
    private function ensureEmailIsUnique(string $email): void
    {
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Este email já está em uso.'],
            ]);
        }
    }
}
