<?php

namespace App\Http\Requests\Contractor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'owner_name'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone_number'     => ['sometimes', 'nullable', 'string', 'max:50'],
            'tax_id'           => ['sometimes', 'nullable', 'string', 'max:50'],
            'city'             => ['sometimes', 'nullable', 'string', 'max:100'],
            'state'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'zip_code'         => ['sometimes', 'nullable', 'string', 'max:20'],
            'bio'              => ['sometimes', 'nullable', 'string', 'max:2000'],
            'hourly_rate'      => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'years_experience' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'license_number'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'service_radius'   => ['sometimes', 'nullable', 'integer', 'min:1'],
            'business_hours'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'languages'        => ['sometimes', 'nullable', 'array'],
            'languages.*'      => ['string'],
            'service_areas'    => ['sometimes', 'nullable', 'array'],
            'service_areas.*'  => ['string'],
            'gallery_images'   => ['sometimes', 'nullable', 'array'],
            'gallery_images.*' => ['string'],
            'video_urls'       => ['sometimes', 'nullable', 'array'],
            'video_urls.*'     => ['string'],
            'website_url'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'linkedin_url'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook_url'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'instagram_url'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'twitter_url'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover_photo'      => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
