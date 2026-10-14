<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\HandlesUploads;
use App\Traits\ResolvesContractorPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContractorBrandingController extends Controller
{
    use ApiResponseTrait, HandlesUploads, ResolvesContractorPlan;

    /**
     * Banner: homepage rotating banner preview (Node 3300-1049). Elite only.
     */
    public function banner(): JsonResponse
    {
        $user = auth('api')->user();
        $profile = $user->businessProfile;

        return $this->success($this->bannerPayload($user, $profile), 'Banner retrieved successfully.');
    }

    /**
     * Upload New Banner Image (multipart: banner_image, banner_headline). Recommended 1440 × 340px, up to 5MB.
     */
    public function updateBanner(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $this->planAllows($user, 'banner')) {
            return $this->forbidden('The homepage banner is an Elite plan feature. Upgrade your membership to use it.');
        }

        $validated = $request->validate([
            'banner_image'    => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'banner_headline' => ['sometimes', 'nullable', 'string', 'max:160'],
        ]);

        $profile = $user->businessProfile;
        if (! $profile) {
            return $this->notFound('Business profile not found.');
        }

        if ($request->hasFile('banner_image')) {
            $this->deleteUpload($profile->banner_image);
            $validated['banner_image'] = $this->storeUpload($request->file('banner_image'), 'banners');
        }

        $profile->update($validated);

        return $this->success($this->bannerPayload($user, $profile), 'Banner updated successfully.');
    }

    public function deleteBanner(): JsonResponse
    {
        $profile = auth('api')->user()->businessProfile;
        if (! $profile) {
            return $this->notFound('Business profile not found.');
        }

        $this->deleteUpload($profile->banner_image);
        $profile->update(['banner_image' => null]);

        return $this->success(null, 'Banner removed successfully.');
    }

    /**
     * Website: live status, ValorHub subdomain and custom domain (Node 3300-1275).
     */
    public function website(): JsonResponse
    {
        $user = auth('api')->user();
        $profile = $user->businessProfile;
        if (! $profile) {
            return $this->notFound('Business profile not found.');
        }

        $this->ensureSlug($profile);

        return $this->success($this->websitePayload($user, $profile), 'Website settings retrieved successfully.');
    }

    /**
     * Save Domain Settings. custom_domain is Elite only; changing the slug needs Pro or Elite.
     */
    public function updateWebsite(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $profile = $user->businessProfile;
        if (! $profile) {
            return $this->notFound('Business profile not found.');
        }

        $validated = $request->validate([
            'custom_domain' => [
                'sometimes', 'nullable', 'string', 'max:253',
                'regex:/^(?!-)([a-z0-9-]{1,63}(?<!-)\.)+[a-z]{2,63}$/i',
                Rule::unique('business_profiles', 'custom_domain')->ignore($profile->id),
            ],
            'slug' => [
                'sometimes', 'string', 'min:3', 'max:80', 'alpha_dash',
                Rule::unique('business_profiles', 'slug')->ignore($profile->id),
            ],
        ], [
            'custom_domain.regex' => 'Please enter a valid domain, e.g. yourbusiness.com (without http://).',
            'slug.unique'         => 'That ValorHub URL is already taken.',
        ]);

        if (! empty($validated['custom_domain']) && ! $this->planAllows($user, 'custom_domain')) {
            return $this->forbidden('Custom domains are an Elite plan feature. Upgrade your membership to use one.');
        }

        if (isset($validated['slug']) && ! $this->planAllows($user, 'custom_slug')) {
            return $this->forbidden('A custom business URL is available on the Pro and Elite plans.');
        }

        if (isset($validated['custom_domain'])) {
            $validated['custom_domain'] = strtolower($validated['custom_domain']);
        }
        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $profile->update($validated);
        $this->ensureSlug($profile);

        return $this->success($this->websitePayload($user, $profile), 'Domain settings saved successfully.');
    }

    /**
     * Business Profile documents & images (multipart: logo, cover_photo, license_document).
     */
    public function uploadProfileMedia(Request $request): JsonResponse
    {
        $request->validate([
            'logo'             => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'cover_photo'      => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'license_document' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $profile = auth('api')->user()->businessProfile;
        if (! $profile) {
            return $this->notFound('Business profile not found.');
        }

        $directories = ['logo' => 'logos', 'cover_photo' => 'covers', 'license_document' => 'documents'];
        $updates = [];

        foreach ($directories as $field => $directory) {
            if ($request->hasFile($field)) {
                $this->deleteUpload($profile->{$field});
                $updates[$field] = $this->storeUpload($request->file($field), $directory);
            }
        }

        if (! $updates) {
            return $this->error('Please attach a logo, cover_photo or license_document file.', 422);
        }

        // A new license document must be re-verified by an admin.
        if (isset($updates['license_document'])) {
            $updates['is_license_verified'] = false;
        }

        $profile->update($updates);

        return $this->success([
            'logo'                => $this->uploadUrl($profile->logo),
            'cover_photo'         => $this->uploadUrl($profile->cover_photo),
            'license_document'    => $this->uploadUrl($profile->license_document),
            'is_license_verified' => (bool) $profile->is_license_verified,
        ], 'Files uploaded successfully.');
    }

    /**
     * Remove one profile file: logo, cover_photo or license_document.
     */
    public function deleteProfileMedia(string $field): JsonResponse
    {
        if (! in_array($field, ['logo', 'cover_photo', 'license_document'], true)) {
            return $this->notFound('Unknown file type.');
        }

        $profile = auth('api')->user()->businessProfile;
        if (! $profile) {
            return $this->notFound('Business profile not found.');
        }

        $this->deleteUpload($profile->{$field});
        $profile->update([$field => null] + ($field === 'license_document' ? ['is_license_verified' => false] : []));

        return $this->success(null, 'File removed successfully.');
    }

    private function bannerPayload(User $user, ?BusinessProfile $profile): array
    {
        return [
            'is_available'    => $this->planAllows($user, 'banner'),
            'banner_image'    => $this->uploadUrl($profile?->banner_image),
            'banner_headline' => $profile?->banner_headline
                ?? ($profile ? "{$profile->business_name} — Trusted Veteran-Owned Business" : null),
            'business_name'   => $profile?->business_name,
            'is_live'         => (bool) ($profile?->banner_image && $this->planAllows($user, 'banner')),
            'recommended'     => ['width' => 1440, 'height' => 340, 'formats' => ['jpg', 'png'], 'max_size_mb' => 5],
        ];
    }

    private function websitePayload(User $user, BusinessProfile $profile): array
    {
        $frontend = rtrim(config('valorhub.frontend_url'), '/');

        return [
            'is_live'                => (bool) $profile->onboarding_completed,
            'slug'                   => $profile->slug,
            'subdomain_display'      => 'valorhub.com/' . $profile->slug,
            'public_url'             => $frontend . '/' . $profile->slug,
            'custom_domain'          => $profile->custom_domain,
            'can_use_custom_domain'  => $this->planAllows($user, 'custom_domain'),
            'can_customize_slug'     => $this->planAllows($user, 'custom_slug'),
            'visit_url'              => $profile->custom_domain ? 'https://' . $profile->custom_domain : $frontend . '/' . $profile->slug,
        ];
    }

    /**
     * Every contractor gets a ValorHub URL derived from the business name.
     */
    private function ensureSlug(BusinessProfile $profile): void
    {
        if ($profile->slug) {
            return;
        }

        $base = Str::slug($profile->business_name) ?: 'business-' . $profile->user_id;
        $slug = $base;
        $suffix = 2;

        while (BusinessProfile::where('slug', $slug)->where('id', '!=', $profile->id)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        $profile->update(['slug' => $slug]);
    }
}
