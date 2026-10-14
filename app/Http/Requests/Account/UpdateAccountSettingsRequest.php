<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                                        => ['sometimes', 'required', 'string', 'max:255'],
            'email'                                       => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth('api')->id())],
            'phone'                                       => ['sometimes', 'nullable', 'string', 'max:50'],
            'notification_preferences'                    => ['sometimes', 'array'],
            'notification_preferences.email_notifications' => ['sometimes', 'boolean'],
            'notification_preferences.new_quote_requests' => ['sometimes', 'boolean'],
            'notification_preferences.messages'           => ['sometimes', 'boolean'],
            'notification_preferences.invoice_payments'   => ['sometimes', 'boolean'],
        ];
    }
}
