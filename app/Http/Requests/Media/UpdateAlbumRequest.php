<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for updating an album.
 * 
 * @Requirements: 2.2
 */
class UpdateAlbumRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cover_media_id' => ['nullable', 'uuid', 'exists:site_media,id'],
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
            'name.max' => 'O nome do álbum não pode ter mais de 255 caracteres.',
            'description.max' => 'A descrição não pode ter mais de 1000 caracteres.',
            'cover_media_id.uuid' => 'O ID da capa deve ser um UUID válido.',
            'cover_media_id.exists' => 'A mídia de capa não existe.',
        ];
    }
}
