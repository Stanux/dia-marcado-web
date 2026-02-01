<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for checking upload quota.
 * 
 * @Requirements: 4.3, 4.4
 */
class CheckUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'file_size' => ['required', 'integer', 'min:1'],
            'file_count' => ['nullable', 'integer', 'min:1'],
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
            'file_size.required' => 'O tamanho do arquivo é obrigatório.',
            'file_size.integer' => 'O tamanho do arquivo deve ser um número inteiro.',
            'file_size.min' => 'O tamanho do arquivo deve ser maior que zero.',
            'file_count.integer' => 'A quantidade de arquivos deve ser um número inteiro.',
            'file_count.min' => 'A quantidade de arquivos deve ser maior que zero.',
        ];
    }
}
