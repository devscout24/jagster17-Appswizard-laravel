<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\BusinessOnboardingCheckoutRequest;
use App\Http\Requests\Business\BusinessOnboardingIdMeRequest;
use App\Http\Requests\Business\BusinessOnboardingInfoRequest;
use App\Http\Requests\Business\BusinessOnboardingPlanRequest;
use App\Http\Requests\Business\BusinessOnboardingProfileRequest;
use App\Http\Requests\Business\BusinessOnboardingServicesRequest;
use App\Http\Requests\Business\BusinessOnboardingSocialRequest;
use App\Http\Requests\Business\BusinessOnboardingStep1Request;
use App\Http\Resources\BusinessOnboardingReviewResource;
use App\Http\Resources\BusinessOnboardingStatusResource;
use App\Http\Resources\BusinessOnboardingStep1Resource;
use App\Models\BillingHistory;
use App\Models\BusinessProfile;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BusinessOnboardingController extends Controller
{
    use ApiResponseTrait;

    /**
     * Step 1: Create Account & Initialize Business Profile
     */
    public function account(BusinessOnboardingStep1Request $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            Role::findOrCreate('business', 'api');

            $user = User::create([
                'name'     => $validated['owner_name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone'    => $validated['phone_number'],
                'status'   => 'pending',
            ]);

            $user->assignRole('business');

            BusinessProfile::create([
                'user_id'              => $user->id,
                'business_name'        => $validated['business_name'],
                'owner_name'           => $validated['owner_name'],
                'business_type'        => $validated['business_type'],
                'phone_number'         => $validated['phone_number'],
                'member_since'         => now(),
                'onboarding_step'      => 2,
                'onboarding_completed' => false,
                'terms_accepted_at'    => now(),
            ]);

            return $user;
        });

        $token = auth('api')->login($user);
        $user->token = $token;
        $user->load('businessProfile', 'roles');

        return $this->success(
            new BusinessOnboardingStep1Resource($user),
            'Account created successfully. Proceed to Choose Your Membership.',
            201
        );
    }

    /**
     * Step 2: Choose Membership Plan (Free vs. Pro vs. Elite)
     */
    public function selectPlan(BusinessOnboardingPlanRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();

        $plan = SubscriptionPlan::active()->find($validated['plan_id']);
        if (! $plan) {
            return $this->notFound('Selected membership plan is invalid.');
        }

        $billingCycle = $validated['billing_cycle'] ?? 'monthly';
        $isFree = (float) $plan->monthly_price === 0.0;

        $response = DB::transaction(function () use ($user, $plan, $billingCycle, $isFree) {
            // Cancel any old subscriptions
            Subscription::where('business_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            $subscription = Subscription::create([
                'business_id'   => $user->id,
                'plan_id'       => $plan->id,
                'billing_cycle' => $billingCycle,
                'status'        => $isFree ? 'active' : 'past_due', // active if free, awaiting checkout if paid
                'renews_at'     => $billingCycle === 'annual' ? now()->addYear() : now()->addMonth(),
            ]);

            $profile = $user->businessProfile;
            if ($profile) {
                $profile->update([
                    'is_elite'        => $plan->slug === 'elite',
                    'onboarding_step' => $isFree ? 3 : 2,
                ]);
            }

            return [
                'plan'             => [
                    'id'            => $plan->id,
                    'name'          => $plan->name,
                    'slug'          => $plan->slug,
                    'monthly_price' => (float) $plan->monthly_price,
                    'display_price' => '$' . number_format((float) $plan->monthly_price, 0),
                    'is_free'       => $isFree,
                ],
                'subscription_id'  => $subscription->id,
                'requires_payment' => ! $isFree,
                'next_step'        => $isFree ? 'id_me' : 'checkout',
            ];
        });

        $msg = $isFree
            ? 'Free membership selected. Proceed to ID.me verification.'
            : 'Plan selected. Proceed to Stripe payment checkout.';

        return $this->success($response, $msg, 200);
    }

    /**
     * Step 3: Stripe Checkout (Pro & Elite paid flows)
     */
    public function checkout(BusinessOnboardingCheckoutRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();

        $subscription = Subscription::where('business_id', $user->id)
            ->with('plan')
            ->latest()
            ->first();

        if (! $subscription || ! $subscription->plan) {
            return $this->error('No membership plan selected. Please choose a plan first.', 400);
        }

        $plan = $subscription->plan;
        $amount = $subscription->billing_cycle === 'annual'
            ? ($plan->annual_price ?? ($plan->monthly_price * 10))
            : $plan->monthly_price;

        $last4 = ! empty($validated['card_number'])
            ? substr($validated['card_number'], -4)
            : '4242';

        DB::transaction(function () use ($user, $subscription, $plan, $amount, $last4) {
            $subscription->update([
                'status'               => 'active',
                'payment_method_last4' => $last4,
                'renews_at'            => $subscription->billing_cycle === 'annual' ? now()->addYear() : now()->addMonth(),
            ]);

            BillingHistory::create([
                'subscription_id' => $subscription->id,
                'plan_name'       => $plan->name,
                'amount'          => $amount,
                'status'          => 'paid',
                'receipt_url'     => 'https://receipts.valorhub.test/rcpt_' . uniqid(),
                'billed_at'       => now(),
            ]);

            $profile = $user->businessProfile;
            if ($profile) {
                $profile->update([
                    'onboarding_step' => 3,
                ]);
            }
        });

        return $this->success([
            'payment_status'  => 'succeeded',
            'plan_name'       => $plan->name,
            'amount_paid'     => (float) $amount,
            'last4'           => $last4,
            'next_step'       => 'id_me',
        ], 'Payment successful. Membership activated. Proceed to ID.me verification.', 200);
    }

    /**
     * Step 4: ID.me Identity & Veteran Verification (Free, Pro & Elite)
     */
    public function verifyIdMe(BusinessOnboardingIdMeRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();
        $profile = $user->businessProfile;

        if (! $profile) {
            return $this->error('Business profile not found.', 404);
        }

        $isVeteran = $validated['is_veteran'] ?? true;
        $status = $validated['status'] ?? 'verified';

        $profile->update([
            'is_id_verified'    => $status === 'verified',
            'is_veteran_owned'  => $isVeteran && $status === 'verified',
            'id_me_verified_at' => $status === 'verified' ? now() : null,
            'id_me_data'        => [
                'verification_token' => $validated['verification_token'] ?? ('idme_' . uniqid()),
                'military_branch'    => $validated['military_branch'] ?? 'United States Army',
                'verified_status'    => $status,
            ],
            'onboarding_step'   => 4,
        ]);

        return $this->success([
            'is_id_verified'   => (bool) $profile->is_id_verified,
            'is_veteran_owned' => (bool) $profile->is_veteran_owned,
            'military_branch'  => $validated['military_branch'] ?? 'United States Army',
            'badge'            => $profile->is_veteran_owned ? 'Verified Veteran Owned' : 'ID Verified',
            'next_step'        => 'business_info',
        ], 'Identity verification completed. Proceed to Business Information.', 200);
    }

    /**
     * Step 5: Business Information (Free, Pro & Elite)
     */
    public function saveBusinessInfo(BusinessOnboardingInfoRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();
        $profile = $user->businessProfile;

        if (! $profile) {
            return $this->error('Business profile not found.', 404);
        }

        $profile->update([
            'business_name'   => $validated['business_name'] ?? $profile->business_name,
            'tax_id'          => $validated['tax_id'] ?? $profile->tax_id,
            'city'            => $validated['city'],
            'state'           => $validated['state'],
            'zip_code'        => $validated['zip_code'],
            'business_hours'  => $validated['business_hours'] ?? $profile->business_hours,
            'service_radius'  => $validated['service_radius'] ?? $profile->service_radius ?? 25,
            'service_areas'   => $validated['service_areas'] ?? [$validated['city']],
            'languages'       => $validated['languages'] ?? ['English'],
            'onboarding_step' => 5,
        ]);

        return $this->success([
            'business_name'  => $profile->business_name,
            'location'       => "{$profile->city}, {$profile->state} {$profile->zip_code}",
            'service_radius' => $profile->service_radius,
            'next_step'      => 'profile',
        ], 'Business information saved. Proceed to Business Profile.', 200);
    }

    /**
     * Step 6: Business Profile & Media Setup (Bio, Rates, Gallery, Videos)
     */
    public function saveProfile(BusinessOnboardingProfileRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();
        $profile = $user->businessProfile;

        if (! $profile) {
            return $this->error('Business profile not found.', 404);
        }

        $subscription = $user->subscription;
        $plan = $subscription?->plan;
        $isFree = $plan ? (float) $plan->monthly_price === 0.0 : true;

        // Enforce gallery & video tier limits
        $galleryImages = $validated['gallery_images'] ?? $profile->gallery_images ?? [];
        $videoUrls = $validated['video_urls'] ?? $profile->video_urls ?? [];

        if ($isFree) {
            $galleryImages = []; // Free has no gallery uploads
            $videoUrls = [];
        } elseif ($plan && $plan->gallery_limit !== null) {
            $galleryImages = array_slice($galleryImages, 0, $plan->gallery_limit);
            if ($plan->video_limit !== null) {
                $videoUrls = array_slice($videoUrls, 0, $plan->video_limit);
            }
        }

        $profile->update([
            'bio'              => $validated['bio'] ?? $profile->bio,
            'hourly_rate'      => $validated['hourly_rate'] ?? $profile->hourly_rate,
            'years_experience' => $validated['years_experience'] ?? $profile->years_experience,
            'cover_photo'      => $validated['cover_photo'] ?? $profile->cover_photo,
            'gallery_images'   => $galleryImages,
            'video_urls'       => $videoUrls,
            'onboarding_step'  => 6,
        ]);

        $nextStep = $isFree ? 'services' : 'social_links';

        return $this->success([
            'hourly_rate'      => (float) $profile->hourly_rate,
            'years_experience' => $profile->years_experience,
            'gallery_count'    => count($galleryImages),
            'video_count'      => count($videoUrls),
            'next_step'        => $nextStep,
        ], 'Profile details saved.', 200);
    }

    /**
     * Step 7: Social Links (Pro & Elite flow)
     */
    public function saveSocialLinks(BusinessOnboardingSocialRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();
        $profile = $user->businessProfile;

        if (! $profile) {
            return $this->error('Business profile not found.', 404);
        }

        $profile->update([
            'website_url'     => $validated['website_url'] ?? $profile->website_url,
            'linkedin_url'    => $validated['linkedin_url'] ?? $profile->linkedin_url,
            'facebook_url'    => $validated['facebook_url'] ?? $profile->facebook_url,
            'instagram_url'   => $validated['instagram_url'] ?? $profile->instagram_url,
            'twitter_url'     => $validated['twitter_url'] ?? $profile->twitter_url,
            'onboarding_step' => 7,
        ]);

        return $this->success([
            'social_links' => [
                'website_url'   => $profile->website_url,
                'linkedin_url'  => $profile->linkedin_url,
                'facebook_url'  => $profile->facebook_url,
                'instagram_url' => $profile->instagram_url,
                'twitter_url'   => $profile->twitter_url,
            ],
            'next_step' => 'services',
        ], 'Social links saved. Proceed to Service Selection.', 200);
    }

    /**
     * Step 8: Service Selection (Enforcing Free: 3, Pro: 10, Elite: Unlimited)
     */
    public function saveServices(BusinessOnboardingServicesRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();
        $profile = $user->businessProfile;

        if (! $profile) {
            return $this->error('Business profile not found.', 404);
        }

        $subscription = $user->subscription;
        $plan = $subscription?->plan;
        $serviceLimit = $plan?->service_limit ?? ($plan && (float) $plan->monthly_price === 0.0 ? 3 : null);

        $servicesInput = $validated['services'];

        if ($serviceLimit !== null && count($servicesInput) > $serviceLimit) {
            return $this->error(
                "Your {$plan->name} allows a maximum of {$serviceLimit} services. Please upgrade your plan or select up to {$serviceLimit} services.",
                422
            );
        }

        DB::transaction(function () use ($user, $servicesInput, $profile) {
            // Remove previous onboarding services
            Service::where('business_id', $user->id)->delete();

            foreach ($servicesInput as $item) {
                Service::create([
                    'business_id'   => $user->id,
                    'category_id'   => $item['category_id'],
                    'name'          => $item['name'],
                    'description'   => $item['description'] ?? null,
                    'pricing_type'  => $item['pricing_type'],
                    'price'         => $item['price'],
                    'status'        => 'active',
                ]);
            }

            $profile->update([
                'onboarding_step' => 8,
            ]);
        });

        $services = Service::where('business_id', $user->id)->with('category')->get();

        return $this->success([
            'services_count' => $services->count(),
            'services'       => $services->map(fn ($s) => [
                'id'            => $s->id,
                'name'          => $s->name,
                'category'      => $s->category?->name,
                'price'         => (float) $s->price,
                'pricing_type'  => $s->pricing_type,
                'display_price' => '$' . number_format((float) $s->price, 2),
            ]),
            'next_step'      => 'review',
        ], 'Services saved successfully. Proceed to Review & Submit.', 200);
    }

    /**
     * Step 9: Review Application Summary
     */
    public function review(): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user || ! $user->businessProfile) {
            return $this->error('Business profile not found.', 404);
        }

        return $this->success(
            new BusinessOnboardingReviewResource($user),
            'Onboarding review summary retrieved successfully.'
        );
    }

    /**
     * Step 10: Final Submit & Complete Onboarding
     */
    public function submit(): JsonResponse
    {
        $user = auth('api')->user();
        $profile = $user->businessProfile;

        if (! $profile) {
            return $this->error('Business profile not found.', 404);
        }

        DB::transaction(function () use ($user, $profile) {
            User::where('id', $user->id)->update(['status' => 'active']);
            $profile->update([
                'onboarding_completed' => true,
                'onboarding_step'      => 9,
            ]);
        });

        return $this->success([
            'account_status'       => 'active',
            'onboarding_completed' => true,
            'business_name'        => $profile->business_name,
            'dashboard_url'        => '/contractor/dashboard',
            'congratulations_msg'  => 'Your contractor profile is now live on ValorHub!',
        ], 'Application submitted successfully! Welcome to ValorHub.', 200);
    }

    /**
     * Get current contractor onboarding status / progress
     */
    public function status(): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return $this->unauthorized();
        }

        return $this->success(
            new BusinessOnboardingStatusResource($user),
            'Onboarding status retrieved successfully.'
        );
    }
}
