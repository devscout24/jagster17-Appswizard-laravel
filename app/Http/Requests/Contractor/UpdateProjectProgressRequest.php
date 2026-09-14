<?php

namespace App\Http\Requests\Contractor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'status'           => ['sometimes', 'string', Rule::in(['in_progress', 'scheduled', 'pending_materials', 'completed', 'cancelled'])],
        ];
    }
}
