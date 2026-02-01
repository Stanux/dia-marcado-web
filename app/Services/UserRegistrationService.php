<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        $this->validateRegistrationData($data);
        $this->ensureEmailIsUnique($data['email']);

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'couple',
        ]);
    }

    /**
     * Validate that all required fields are present.
     *
     * @param array $data
     * @throws ValidationException
     */
    private function validateRegistrationData(array $data): void
    {
        $required = ['name', 'email', 'password'];
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
