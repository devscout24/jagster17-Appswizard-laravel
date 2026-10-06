<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Elite-only homepage rotating banner. Uploads go to admin for approval.
 */
class BannerController extends Controller
{
    use ApiResponseTrait;

    public function show(): JsonResponse
    {
        $user = auth('api')->user();
        $banner = Banner::where('business_id', $user->id)->latest()->first();
        $live = Banner::where('business_id', $user->id)->approved()->latest('approved_at')->first();

        return $this->success([
            'is_eligible' => $this->isElite(),
            'current' => $live ? $this->present($live) : null,
            'latest_submission' => $banner ? $this->present($banner) : null,
            'default_headline' => ($user->businessProfile?->business_name ?? $user->name) . ' — Trusted Veteran-Owned Business',
            'requirements' => 'Recommended: 1440 × 340px, JPG or PNG, up to 5MB.',
        ], 'Banner retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->isElite()) {
            return $this->forbidden('The homepage banner is available on the Elite plan.');
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'headline' => ['nullable', 'string', 'max:120'],
        ]);

        $businessId = auth('api')->id();
        $banner = Banner::create([
            'business_id' => $businessId,
            'image_path' => $request->file('image')->store("banners/{$businessId}", 'public'),
            'headline' => $validated['headline'] ?? null,
            'status' => 'pending',
        ]);

        return $this->success($this->present($banner), 'Banner submitted for review. It goes live once approved.', 201);
    }

    private function isElite(): bool
    {
        $user = auth('api')->user();

        return $user->subscription?->plan?->slug === 'elite' || (bool) $user->businessProfile?->is_elite;
    }

    private function present(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'image_url' => $banner->image_url,
            'headline' => $banner->headline,
            'status' => $banner->status,
            'rejection_reason' => $banner->rejection_reason,
            'submitted_at' => $banner->created_at->toIso8601String(),
            'approved_at' => $banner->approved_at?->toIso8601String(),
        ];
    }
}
