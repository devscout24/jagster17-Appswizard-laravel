<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * Admin-managed content used across the public site: settings (contact details, social links),
 * CMS pages (privacy, terms, about) and approved Elite homepage banners.
 */
class SiteContentController extends Controller
{
    use ApiResponseTrait;

    public function settings(): JsonResponse
    {
        $values = SiteSetting::allValues();
        // Internal platform values are not public
        unset($values['platform_fee_percent']);

        return $this->success($values, 'Site settings retrieved successfully.');
    }

    public function page(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->first();

        if (! $page) {
            return $this->notFound('Page not found');
        }

        return $this->success([
            'slug' => $page->slug,
            'title' => $page->title,
            'content' => $page->content,
            'meta_description' => $page->meta_description,
            'updated_at' => $page->updated_at->format('M j, Y'),
        ], 'Page retrieved successfully.');
    }

    public function banners(): JsonResponse
    {
        $banners = Banner::approved()
            ->with('business.businessProfile')
            ->latest('approved_at')
            ->take(10)
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'image_url' => $banner->image_url,
                'headline' => $banner->headline
                    ?? ($banner->business?->businessProfile?->business_name ?? $banner->business?->name),
                'business_id' => $banner->business_id,
                'business_name' => $banner->business?->businessProfile?->business_name,
            ]);

        return $this->success($banners, 'Homepage banners retrieved successfully.');
    }
}
