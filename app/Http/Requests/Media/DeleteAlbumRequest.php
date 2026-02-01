<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for deleting an album.
 * 
 * @Requirements: 2.6
 */
class DeleteAlbumRequest extends FormRequest
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
            'move_to_album_id' => ['nullable', 'uuid', 'exists:albums,id'],
            'force' => ['nullable', 'boolean'],
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
            'move_to_album_id.uuid' => 'O ID do álbum de destino deve ser um UUID válido.',
            'move_to_album_id.exists' => 'O álbum de destino não existe.',
        ];
    }
}
