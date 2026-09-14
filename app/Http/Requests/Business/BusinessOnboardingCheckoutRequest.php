<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class BusinessOnboardingCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id'   => ['required_without:card_number', 'nullable', 'string'],
            'card_number'         => ['required_without:payment_method_id', 'nullable', 'string', 'min:12', 'max:19'],
            'exp_month'           => ['required_with:card_number', 'nullable', 'numeric', 'min:1', 'max:12'],
            'exp_year'            => ['required_with:card_number', 'nullable', 'numeric', 'min:2024'],
            'cvc'                 => ['required_with:card_number', 'nullable', 'string', 'min:3', 'max:4'],
            'cardholder_name'     => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
