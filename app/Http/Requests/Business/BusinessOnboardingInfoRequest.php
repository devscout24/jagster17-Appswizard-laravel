<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class BusinessOnboardingInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name'  => ['sometimes', 'string', 'max:255'],
            'tax_id'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'city'           => ['required', 'string', 'max:100'],
            'state'          => ['required', 'string', 'max:50'],
            'zip_code'       => ['required', 'string', 'max:20'],
            'business_hours' => ['sometimes', 'nullable', 'string', 'max:255'],
            'service_radius' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],
            'service_areas'  => ['sometimes', 'nullable', 'array'],
            'languages'      => ['sometimes', 'nullable', 'array'],
        ];
    }
}
