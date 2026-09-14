<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessOnboardingStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'business_name'   => ['required', 'string', 'max:255'],
            'owner_name'      => ['required', 'string', 'max:255'],
            'phone_number'    => ['required', 'string', 'max:25'],
            'business_type'   => ['required', 'string', Rule::in(['LLC', 'Sole Proprietor', 'Corporation', 'Partnership'])],
            'terms_accepted'  => ['required', 'accepted'],
        ];
    }
}
