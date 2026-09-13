<?php

namespace App\Http\Requests\QuoteRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'service_type' => ['nullable', 'string', 'max:100'],
            'contractor_id' => ['nullable', 'integer', 'exists:users,id'],
            'business_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_title' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:5', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
            'budget_range' => ['nullable', 'string', 'in:under_1000,1000_5000,5000_15000,15000_plus'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'timeline' => ['required', 'string', 'in:asap,within_1_week,within_1_month,flexible'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
        ];
    }
}
