<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessOnboardingStep1Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id'    => $this->id,
                'name'  => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'role'  => $this->roles->pluck('name')->first() ?? 'business',
            ],
            'business_profile' => [
                'id'                   => $this->businessProfile?->id,
                'business_name'        => $this->businessProfile?->business_name,
                'owner_name'           => $this->businessProfile?->owner_name,
                'business_type'        => $this->businessProfile?->business_type,
                'phone_number'         => $this->businessProfile?->phone_number,
                'onboarding_step'      => $this->businessProfile?->onboarding_step ?? 2,
                'onboarding_completed' => (bool) $this->businessProfile?->onboarding_completed,
            ],
            'next_step' => 'choose_membership',
            'token' => isset($this->token) ? [
                'access_token' => $this->token,
                'token_type'   => 'bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
            ] : null,
        ];
    }
}
