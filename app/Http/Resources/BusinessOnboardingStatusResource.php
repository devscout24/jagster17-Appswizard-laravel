<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessOnboardingStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->businessProfile;
        $subscription = $this->subscription;
        $plan = $subscription?->plan;
        $isFree = $plan ? (float) $plan->monthly_price === 0.0 : true;

        return [
            'user_id'              => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,
            'status'               => $this->status,
            'onboarding_step'      => $profile?->onboarding_step ?? 1,
            'onboarding_completed' => (bool) $profile?->onboarding_completed,
            'membership'           => [
                'plan_id'       => $plan?->id,
                'plan_name'     => $plan?->name,
                'slug'          => $plan?->slug ?? 'free',
                'is_free'       => $isFree,
                'is_elite'      => (bool) $profile?->is_elite,
                'status'        => $subscription?->status ?? 'pending',
                'billing_cycle' => $subscription?->billing_cycle ?? 'monthly',
            ],
            'verification' => [
                'is_id_verified'    => (bool) $profile?->is_id_verified,
                'is_veteran_owned'  => (bool) $profile?->is_veteran_owned,
                'id_me_verified_at' => $profile?->id_me_verified_at?->toIso8601String(),
            ],
            'business_info' => [
                'business_name'  => $profile?->business_name,
                'owner_name'     => $profile?->owner_name,
                'business_type'  => $profile?->business_type,
                'phone_number'   => $profile?->phone_number,
                'tax_id'         => $profile?->tax_id,
                'city'           => $profile?->city,
                'state'          => $profile?->state,
                'zip_code'       => $profile?->zip_code,
                'service_radius' => $profile?->service_radius,
            ],
            'services_count' => $this->services()->count(),
        ];
    }
}
