<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:50'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}
