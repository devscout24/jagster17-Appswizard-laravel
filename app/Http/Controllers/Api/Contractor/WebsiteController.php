<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebsiteController extends Controller
{
    use ApiResponseTrait;

    public function show(): JsonResponse
    {
        $profile = auth('api')->user()->businessProfile;

        return $this->success($this->present($profile), 'Website settings retrieved successfully.');
    }

    public function update(Request $request): JsonResponse
    {
        $profile = auth('api')->user()->businessProfile;

        // Accept pasted URLs such as https://www.example.com/
        $request->merge([
            'custom_domain' => $request->filled('custom_domain')
                ? strtolower(rtrim(preg_replace('#^https?://#i', '', trim($request->input('custom_domain'))), '/'))
                : null,
        ]);

        $validated = $request->validate([
            'custom_domain' => [
                'nullable', 'string', 'max:253',
                'regex:/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.[a-z0-9-]{1,63})*\.[a-z]{2,}$/',
                Rule::unique('business_profiles', 'custom_domain')->ignore($profile->id),
            ],
        ], ['custom_domain.regex' => 'Enter a valid domain, e.g. yourbusiness.com']);

        $profile->update(['custom_domain' => $validated['custom_domain']]);

        return $this->success($this->present($profile), 'Domain settings saved.');
    }

    private function present($profile): array
    {
        $slug = $profile->slug ?: Str::slug($profile->business_name);

        return [
            'is_live' => (bool) $profile->onboarding_completed,
            'custom_domain' => $profile->custom_domain,
            'subdomain' => "valorhub.com/{$slug}",
            'public_profile_url' => rtrim(config('app.frontend_url', config('app.url')), '/') . "/contractors/{$profile->user_id}",
        ];
    }
}
