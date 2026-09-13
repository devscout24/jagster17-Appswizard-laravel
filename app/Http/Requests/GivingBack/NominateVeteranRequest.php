<?php

namespace App\Http\Requests\GivingBack;

use Illuminate\Foundation\Http\FormRequest;

class NominateVeteranRequest extends FormRequest
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
            'nominator_name' => ['required', 'string', 'max:255'],
            'nominator_email' => ['required', 'email', 'max:255'],
            'nominator_phone' => ['nullable', 'string', 'max:50'],
            'nominee_name' => ['required', 'string', 'max:255'],
            'nominee_branch' => ['nullable', 'string', 'max:100'],
            'nominee_city' => ['nullable', 'string', 'max:100'],
            'nominee_state' => ['nullable', 'string', 'max:100'],
            'project_needed' => ['required', 'string', 'max:255'],
            'story_details' => ['required', 'string', 'max:5000'],
        ];
    }
}
