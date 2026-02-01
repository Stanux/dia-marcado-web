<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for authenticating access to a password-protected site.
 * 
 * Authorization is always true (public endpoint).
 * Validates that a password is provided.
 */
class AuthenticateSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * This is a public endpoint, so always returns true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'A senha é obrigatória.',
            'password.string' => 'A senha deve ser um texto válido.',
        ];
    }
}
