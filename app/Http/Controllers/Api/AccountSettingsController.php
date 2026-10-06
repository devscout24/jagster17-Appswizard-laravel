<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Account settings shared by customers and contractors (name, email, phone, notifications).
 * Password changes go through POST /auth/password.
 */
class AccountSettingsController extends Controller
{
    use ApiResponseTrait;

    public function show(): JsonResponse
    {
        return $this->success($this->present(), 'Settings retrieved successfully.');
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:25'],
        ]);

        $user->update($validated);

        return $this->success($this->present(), 'Settings saved.');
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quotes' => ['sometimes', 'boolean'],
            'messages' => ['sometimes', 'boolean'],
            'payments' => ['sometimes', 'boolean'],
            'reviews' => ['sometimes', 'boolean'],
        ]);

        $user = auth('api')->user();
        $user->update(['notification_preferences' => array_merge($user->notificationPreferences(), $validated)]);

        return $this->success($this->present(), 'Notification preferences saved.');
    }

    private function present(): array
    {
        $user = auth('api')->user()->fresh();

        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'notifications' => $user->notificationPreferences(),
        ];
    }
}
