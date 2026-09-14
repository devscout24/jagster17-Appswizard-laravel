<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class BusinessOnboardingServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'services'                 => ['required', 'array', 'min:1'],
            'services.*.category_id'   => ['required', 'integer', 'exists:categories,id'],
            'services.*.name'          => ['required', 'string', 'max:255'],
            'services.*.description'   => ['sometimes', 'nullable', 'string', 'max:1000'],
            'services.*.pricing_type'  => ['required', 'string', 'in:fixed,hourly,custom'],
            'services.*.price'         => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'services.required' => 'Please select at least one service to offer.',
            'services.min'      => 'Please select at least one service to offer.',
        ];
    }
}
