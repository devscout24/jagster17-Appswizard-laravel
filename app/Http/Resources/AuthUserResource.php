<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->roles->pluck('name')->first();
        $business = $this->businessProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'role' => $role,
            'notification_preferences' => $this->notificationPreferences(),
            'business' => $business ? [
                'id' => $business->id,
                'business_name' => $business->business_name,
                'slug' => $business->slug,
                'is_elite' => (bool) $business->is_elite,
                'onboarding_completed' => (bool) $business->onboarding_completed,
                'onboarding_step' => $business->onboarding_step,
                'plan' => $this->subscription?->plan?->slug,
            ] : null,
            'customer' => $this->customerProfile ? [
                'city' => $this->customerProfile->city,
                'state' => $this->customerProfile->state,
            ] : null,
        ];
    }
}
