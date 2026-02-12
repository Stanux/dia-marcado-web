<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for updating site settings (slug, domain, password).
 * 
 * Authorization uses the SiteLayoutPolicy 'update' method.
 * Validates slug uniqueness, domain format, and password constraints.
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $site = $this->route('site');
        
        return $this->user()->can('update', $site);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siteId = $this->route('site')?->id;

        return [
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('site_layouts', 'slug')->ignore($siteId),
            ],
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(https?:\/\/)?([a-z0-9-]+\.)+[a-z]{2,}$/i',
            ],
            'access_token' => [
                'nullable',
                'string',
                'min:4',
                'max:50',
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
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'slug.unique' => 'Este slug já está em uso. Escolha outro.',
            'slug.max' => 'O slug não pode ter mais de 100 caracteres.',
            'custom_domain.regex' => 'O domínio customizado deve ser um domínio válido.',
            'custom_domain.max' => 'O domínio customizado não pode ter mais de 255 caracteres.',
            'access_token.min' => 'A senha deve ter pelo menos 4 caracteres.',
            'access_token.max' => 'A senha não pode ter mais de 50 caracteres.',
        ];
    }
}
