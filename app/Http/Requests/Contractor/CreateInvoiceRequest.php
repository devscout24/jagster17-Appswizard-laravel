<?php

namespace App\Http\Requests\Contractor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // PUT (edit draft) makes every field optional.
        $required = $this->isMethod('put') ? 'sometimes' : 'required';

        return [
            'customer_id'      => [$this->isMethod('put') ? 'sometimes' : 'required_without:project_id', 'nullable', 'integer', 'exists:users,id'],
            'project_id'       => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'invoice_number'   => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('invoices', 'invoice_number')->ignore($this->route('id'))],
            'items'            => ['sometimes', 'array', 'min:1'],
            'items.*.name'     => ['required', 'string', 'max:255'],
            'items.*.description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price'    => ['required', 'numeric', 'min:0'],
            'bill_to_address'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount'           => [$this->isMethod('put') ? 'sometimes' : 'required_without:items', 'numeric', 'min:1'],
            'labor_amount'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'materials_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'platform_fee'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'issued_at'        => ['sometimes', 'nullable', 'date'],
            'due_at'           => [$required, 'date'],
            'notes'            => ['sometimes', 'nullable', 'string', 'max:1000'],
            'send_immediately' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required_without' => 'Add at least one line item or enter an invoice amount.',
            'due_at.required' => 'Please specify the invoice payment due date.',
        ];
    }
}
