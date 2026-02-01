<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for publishing a site.
 * 
 * Authorization uses the SiteLayoutPolicy 'publish' method.
 * No validation rules needed as validation is performed by the service.
 */
class PublishSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $site = $this->route('site');
        
        return $this->user()->can('publish', $site);
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
