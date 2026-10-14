<?php

namespace App\Http\Requests\Contractor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'category_id'  => [$required, 'integer', Rule::exists('categories', 'id')->where('type', 'service')],
            'name'         => [$required, 'string', 'max:255'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:2000'],
            'pricing_type' => [$required, Rule::in(['fixed', 'hourly', 'sqft', 'custom_quote'])],
            'price'        => ['nullable', 'numeric', 'min:0', 'required_if:pricing_type,fixed,hourly,sqft'],
            'unit'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'status'       => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required_if' => 'Please enter a price, or choose "Custom Quote" pricing.',
        ];
    }
}
