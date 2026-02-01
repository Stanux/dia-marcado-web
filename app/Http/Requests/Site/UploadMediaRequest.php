<?php

namespace App\Http\Requests\Site;

use App\Models\SystemConfig;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for uploading media files to a site.
 * 
 * Authorization uses the SiteLayoutPolicy 'update' method.
 * Validates file size and mime types from SystemConfig.
 */
class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $site = $this->route('site');
        
        return $this->user()->can('update', $site);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get max file size from config (in bytes), convert to KB for Laravel validation
        $maxSizeBytes = SystemConfig::get('site.max_file_size', 10485760);
        $maxSizeKb = (int) ceil($maxSizeBytes / 1024);

        // Get allowed extensions from config
        $allowedExtensions = SystemConfig::get('site.allowed_extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'
        ]);
        $mimes = implode(',', $allowedExtensions);

        return [
            'file' => [
                'required',
                'file',
                "max:{$maxSizeKb}",
                "mimes:{$mimes}",
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxSizeBytes = SystemConfig::get('site.max_file_size', 10485760);
        $maxSizeMb = round($maxSizeBytes / 1048576, 1);

        $allowedExtensions = SystemConfig::get('site.allowed_extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'
        ]);

        return [
            'file.required' => 'O arquivo é obrigatório.',
            'file.file' => 'O upload deve ser um arquivo válido.',
            'file.max' => "O arquivo não pode exceder {$maxSizeMb}MB.",
            'file.mimes' => 'Tipo de arquivo não permitido. Use: ' . implode(', ', $allowedExtensions),
        ];
    }
}
