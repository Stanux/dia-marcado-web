<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for creating an album.
 * 
 * @Requirements: 2.2
 */
class CreateAlbumRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type_slug' => ['required', 'string', 'exists:album_types,slug'],
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
            'name.required' => 'O nome do álbum é obrigatório.',
            'name.max' => 'O nome do álbum não pode ter mais de 255 caracteres.',
            'type_slug.required' => 'O tipo do álbum é obrigatório.',
            'type_slug.exists' => 'O tipo de álbum selecionado não existe.',
            'description.max' => 'A descrição não pode ter mais de 1000 caracteres.',
            'cover_media_id.uuid' => 'O ID da capa deve ser um UUID válido.',
            'cover_media_id.exists' => 'A mídia de capa não existe.',
        ];
    }
}
