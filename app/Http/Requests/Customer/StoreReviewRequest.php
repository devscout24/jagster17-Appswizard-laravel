<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer'],
            'rating'     => ['required', 'integer', 'min:1', 'max:5'],
            'comment'    => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
