<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for restoring a site version.
 * 
 * Authorization uses the SiteLayoutPolicy 'update' method.
 * Validates that the version_id exists in site_versions table.
 */
class RestoreVersionRequest extends FormRequest
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
            'version_id' => [
                'required',
                'uuid',
                'exists:site_versions,id',
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
            'version_id.required' => 'O ID da versão é obrigatório.',
            'version_id.uuid' => 'O ID da versão deve ser um UUID válido.',
            'version_id.exists' => 'A versão especificada não existe.',
        ];
    }
}
