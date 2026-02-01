<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for moving media to another album.
 * 
 * @Requirements: 2.5, 8.4
 */
class MoveMediaRequest extends FormRequest
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
            'album_id' => ['required', 'uuid', 'exists:albums,id'],
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
            'album_id.required' => 'O álbum de destino é obrigatório.',
            'album_id.uuid' => 'O ID do álbum deve ser um UUID válido.',
            'album_id.exists' => 'O álbum de destino não existe.',
        ];
    }
}
