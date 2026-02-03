<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for batch moving media to another album.
 * 
 * @Requirements: Fase 1 - Mover múltiplas fotos entre álbuns
 */
class BatchMoveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media_ids' => ['required', 'array', 'min:1'],
            'media_ids.*' => ['required', 'integer', 'exists:site_media,id'],
            'target_album_id' => ['required', 'integer', 'exists:albums,id'],
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
            'media_ids.required' => 'É necessário selecionar pelo menos uma mídia.',
            'media_ids.array' => 'O formato dos IDs de mídia é inválido.',
            'media_ids.min' => 'É necessário selecionar pelo menos uma mídia.',
            'media_ids.*.required' => 'Todos os IDs de mídia são obrigatórios.',
            'media_ids.*.integer' => 'Os IDs de mídia devem ser números inteiros.',
            'media_ids.*.exists' => 'Uma ou mais mídias selecionadas não existem.',
            'target_album_id.required' => 'O álbum de destino é obrigatório.',
            'target_album_id.integer' => 'O ID do álbum de destino deve ser um número inteiro.',
            'target_album_id.exists' => 'O álbum de destino não existe.',
        ];
    }
}
