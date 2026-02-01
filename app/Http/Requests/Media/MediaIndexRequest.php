<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for listing media with filters.
 */
class MediaIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'album_id' => ['nullable', 'uuid'],
            'album_type' => ['nullable', 'string', 'in:pre_casamento,pos_casamento,uso_site'],
            'mime_type' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,processing,completed,failed'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
