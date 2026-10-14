<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Traits\HandlesUploads;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    use HandlesUploads;

    /**
     * Authenticated user payload used by login / register / me.
     */
    public function toArray(Request $request): array
    {
        $role = $this->roles->pluck('name')->first();
        $profile = $this->businessProfile;
        $plan = $this->subscription?->plan;

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'avatar'            => $this->uploadUrl($this->avatar),
            'status'            => $this->status,
            'role'              => $role,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'customer'          => $role === 'customer' ? [
                'city'  => $this->customerProfile?->city,
                'state' => $this->customerProfile?->state,
            ] : null,
            'business'          => $role === 'business' ? [
                'business_name'        => $profile?->business_name,
                'is_elite'             => (bool) ($profile?->is_elite ?? false),
                'plan'                 => $plan?->name,
                'plan_slug'            => $plan?->slug,
                'onboarding_step'      => $profile?->onboarding_step,
                'onboarding_completed' => (bool) ($profile?->onboarding_completed ?? false),
            ] : null,
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
