<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for uploading a file to a batch.
 */
class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'], // 100MB max
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'O arquivo é obrigatório.',
            'file.file' => 'O upload deve ser um arquivo válido.',
            'file.max' => 'O arquivo não pode exceder 100MB.',
        ];
    }
}
