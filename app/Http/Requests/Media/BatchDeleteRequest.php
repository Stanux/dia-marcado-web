<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for batch delete operation.
 */
class BatchDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'media_ids' => ['required', 'array', 'min:1', 'max:100'],
            'media_ids.*' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'media_ids.required' => 'A lista de IDs de mídia é obrigatória.',
            'media_ids.min' => 'Selecione pelo menos uma mídia para excluir.',
            'media_ids.max' => 'Não é possível excluir mais de 100 mídias por vez.',
        ];
    }
}
