<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for updating site draft content.
 * 
 * Authorization uses the SiteLayoutPolicy 'update' method.
 * Validates content structure and optional summary.
 */
class UpdateDraftRequest extends FormRequest
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
        return [
            'content' => ['required', 'array'],
            'content.sections' => ['required', 'array'],
            'summary' => ['nullable', 'string', 'max:500'],
            'create_version' => ['nullable', 'boolean'],
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
            'content.required' => 'O conteúdo do site é obrigatório.',
            'content.array' => 'O conteúdo deve ser um objeto válido.',
            'content.sections.required' => 'As seções do site são obrigatórias.',
            'content.sections.array' => 'As seções devem ser um objeto válido.',
            'summary.max' => 'O resumo não pode ter mais de 500 caracteres.',
        ];
    }
}
