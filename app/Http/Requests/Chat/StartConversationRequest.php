<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_id'     => ['required', 'integer', 'exists:users,id'],
            'project_id'       => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'quote_request_id' => ['sometimes', 'nullable', 'integer', 'exists:quote_requests,id'],
            'message'          => ['sometimes', 'nullable', 'string', 'max:5000'],
            'attachment_url'   => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
