<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ChangePasswordRequest;
use App\Http\Requests\Account\UpdateAccountSettingsRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\HandlesUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    use ApiResponseTrait, HandlesUploads;

    /**
     * Default notification toggles per role (Customer Settings: email notifications;
     * Contractor Settings: new quote requests, messages, invoice payments).
     */
    private const DEFAULT_PREFERENCES = [
        'customer' => [
            'email_notifications' => true,
        ],
        'business' => [
            'new_quote_requests' => true,
            'messages'           => true,
            'invoice_payments'   => true,
        ],
    ];

    /**
     * Account settings for the Settings screens (customer & contractor).
     */
    public function show(): JsonResponse
    {
        return $this->success($this->settingsPayload(auth('api')->user()), 'Account settings retrieved successfully.');
    }

    /**
     * Update name, email, phone and notification preferences.
     */
    public function update(UpdateAccountSettingsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $validated = $request->validated();

        if (array_key_exists('notification_preferences', $validated)) {
            $allowed = array_keys($this->defaultPreferences($user));
            $validated['notification_preferences'] = array_merge(
                $this->preferences($user),
                array_intersect_key($validated['notification_preferences'], array_flip($allowed))
            );
        }

        $user->update($validated);

        // Keep the contractor's business contact details in step with the account.
        if ($user->hasRole('business') && $user->businessProfile) {
            $profileUpdates = array_filter([
                'owner_name'   => $validated['name'] ?? null,
                'phone_number' => $validated['phone'] ?? null,
            ], fn ($value) => $value !== null);

            if ($profileUpdates) {
                $user->businessProfile->update($profileUpdates);
            }
        }

        return $this->success($this->settingsPayload($user->fresh()), 'Settings saved successfully.');
    }

    /**
     * Change password (requires the current password).
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        auth('api')->user()->update(['password' => $request->validated('password')]);

        return $this->success(null, 'Password updated successfully.');
    }

    /**
     * Upload a profile photo.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        /** @var User $user */
        $user = auth('api')->user();

        $this->deleteUpload($user->avatar);
        $user->update(['avatar' => $this->storeUpload($request->file('avatar'), 'avatars')]);

        return $this->success([
            'avatar' => $this->uploadUrl($user->avatar),
        ], 'Profile photo updated successfully.');
    }

    private function settingsPayload(User $user): array
    {
        return [
            'name'                     => $user->name,
            'email'                    => $user->email,
            'phone'                    => $user->phone,
            'avatar'                   => $this->uploadUrl($user->avatar),
            'role'                     => $user->roles->pluck('name')->first(),
            'notification_preferences' => $this->preferences($user),
        ];
    }

    private function preferences(User $user): array
    {
        return array_merge($this->defaultPreferences($user), $user->notification_preferences ?? []);
    }

    private function defaultPreferences(User $user): array
    {
        return self::DEFAULT_PREFERENCES[$user->hasRole('business') ? 'business' : 'customer'];
    }
}
