<?php

namespace App\Http\Requests\Contractor;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'customer_id'      => ['required', 'integer', 'exists:users,id'],
            'quote_request_id' => ['sometimes', 'nullable', 'integer', 'exists:quote_requests,id'],
            'total_amount'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'start_date'       => ['sometimes', 'nullable', 'date'],
            'due_date'         => ['sometimes', 'nullable', 'date'],
            'description'      => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
