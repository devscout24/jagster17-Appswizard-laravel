<?php

namespace App\Http\Requests\Contractor;

use Illuminate\Foundation\Http\FormRequest;

class SendQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_amount'       => ['required', 'numeric', 'min:1'],
            'labor_cost'         => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'materials_cost'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_cost'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'estimated_duration' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contractor_notes'   => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'quote_amount.required' => 'Please provide a valid total quote amount.',
            'quote_amount.min'      => 'Quote amount must be at least $1.00.',
        ];
    }
}
