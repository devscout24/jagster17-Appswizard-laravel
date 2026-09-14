<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class BusinessOnboardingProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bio'              => ['sometimes', 'nullable', 'string', 'max:2000'],
            'hourly_rate'      => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'years_experience' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'cover_photo'      => ['sometimes', 'nullable', 'string', 'max:500'],
            'gallery_images'   => ['sometimes', 'nullable', 'array'],
            'gallery_images.*' => ['string'],
            'video_urls'       => ['sometimes', 'nullable', 'array'],
            'video_urls.*'     => ['string', 'url'],
        ];
    }
}
