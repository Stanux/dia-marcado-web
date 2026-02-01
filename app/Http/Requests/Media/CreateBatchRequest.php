<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for creating a batch upload.
 */
class CreateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_files' => ['required', 'integer', 'min:1', 'max:100'],
            'album_id' => ['nullable', 'uuid', 'exists:albums,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'total_files.required' => 'O número total de arquivos é obrigatório.',
            'total_files.min' => 'O batch deve conter pelo menos 1 arquivo.',
            'total_files.max' => 'O batch não pode conter mais de 100 arquivos.',
            'album_id.exists' => 'O álbum especificado não existe.',
        ];
    }
}
