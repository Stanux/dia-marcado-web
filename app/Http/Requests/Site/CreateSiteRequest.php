<?php

namespace App\Http\Requests\Site;

use App\Models\SiteLayout;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for creating a new site.
 * 
 * Authorization uses the SiteLayoutPolicy 'create' method.
 * No validation rules needed as site is created with defaults.
 */
class CreateSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', SiteLayout::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
