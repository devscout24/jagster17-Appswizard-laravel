<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessOnboardingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'       => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => ['sometimes', 'string', Rule::in(['monthly', 'annual'])],
        ];
    }
}
