<?php

namespace App\Http\Requests\Contractor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'category_id'    => [$required, 'integer', Rule::exists('categories', 'id')->where('type', 'product')],
            'name'           => [$required, 'string', 'max:255'],
            'description'    => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price'          => [$required, 'numeric', 'min:0'],
            'unit'           => ['sometimes', 'nullable', 'string', 'max:50'],
            'image'          => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'sku'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'warranty'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_info'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'external_url'   => ['sometimes', 'nullable', 'url', 'max:255'],
            'features'       => ['sometimes', 'nullable', 'array'],
            'features.*'     => ['string', 'max:255'],
            'status'         => ['sometimes', Rule::in(['active', 'off'])],
        ];
    }
}
