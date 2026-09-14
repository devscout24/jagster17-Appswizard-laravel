<?php

namespace App\Http\Requests\Contractor;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'      => ['required_without:project_id', 'nullable', 'integer', 'exists:users,id'],
            'project_id'       => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'invoice_number'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'amount'           => ['required', 'numeric', 'min:1'],
            'labor_amount'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'materials_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'platform_fee'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'issued_at'        => ['sometimes', 'nullable', 'date'],
            'due_at'           => ['required', 'date'],
            'notes'            => ['sometimes', 'nullable', 'string', 'max:1000'],
            'send_immediately' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter a valid invoice amount.',
            'due_at.required' => 'Please specify the invoice payment due date.',
        ];
    }
}
