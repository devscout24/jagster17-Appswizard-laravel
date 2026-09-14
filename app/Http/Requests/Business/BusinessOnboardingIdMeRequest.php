<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class BusinessOnboardingIdMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_token' => ['nullable', 'string'],
            'is_veteran'         => ['sometimes', 'boolean'],
            'military_branch'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'status'             => ['sometimes', 'string', 'in:verified,pending,skipped'],
        ];
    }
}
