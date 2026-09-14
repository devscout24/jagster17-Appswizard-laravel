<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class BusinessOnboardingSocialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'website_url'   => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
            'linkedin_url'  => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
            'facebook_url'  => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
            'instagram_url' => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
            'twitter_url'   => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
        ];
    }
}
