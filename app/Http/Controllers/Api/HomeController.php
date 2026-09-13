<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Home\HomeSearchRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\HomeSearchResultResource;
use App\Http\Resources\TestimonialResource;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Project;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use ApiResponseTrait;

    /**
     * Provide all data required to render the ValorHub Home Screen.
     */
    public function index(Request $request): JsonResponse
    {
        $heroSpotlight = $this->getHeroSpotlight();
        $metrics = $this->getPlatformMetrics();
        $categories = $this->getServiceCategories();
        $testimonials = $this->getTestimonials();
        $valuePropositions = $this->getValuePropositions();
        $howItWorks = $this->getHowItWorksSteps();

        $payload = [
            'hero_spotlight' => $heroSpotlight,
            'metrics' => $metrics,
            'categories' => CategoryResource::collection($categories),
            'testimonials' => TestimonialResource::collection($testimonials),
            'value_propositions' => $valuePropositions,
            'how_it_works' => $howItWorks,
        ];

        return $this->success($payload, 'Home screen data retrieved successfully');
    }

    /**
     * Search services and contractors from the Home Screen search bar.
     */
    public function search(HomeSearchRequest $request): JsonResponse
    {
        $serviceQuery = $request->validated('service');
        $zipCode = $request->validated('zip_code');
        $categoryId = $request->validated('category_id');
        $perPage = (int) ($request->validated('per_page') ?? 10);

        $services = Service::query()
            ->where('status', 'active')
            ->with(['business.businessProfile', 'category'])
            ->when($serviceQuery, function ($query, $serviceQuery) {
                $query->where(function ($q) use ($serviceQuery) {
                    $q->where('name', 'like', "%{$serviceQuery}%")
                      ->orWhere('description', 'like', "%{$serviceQuery}%")
                      ->orWhereHas('business.businessProfile', function ($bq) use ($serviceQuery) {
                          $bq->where('business_name', 'like', "%{$serviceQuery}%");
                      });
                });
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($zipCode, function ($query, $zipCode) {
                $query->whereHas('business.businessProfile', function ($bq) use ($zipCode) {
                    $bq->where('city', 'like', "%{$zipCode}%")
                      ->orWhere('state', 'like', "%{$zipCode}%");
                });
            })
            ->latest()
            ->paginate($perPage);

        return $this->success([
            'items' => HomeSearchResultResource::collection($services->items()),
            'pagination' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ], 'Search results retrieved successfully');
    }

    /**
     * Private helper to fetch the featured / sponsored contractor for the hero banner.
     */
    private function getHeroSpotlight(): ?array
    {
        $featuredProfile = BusinessProfile::query()
            ->where('is_elite', true)
            ->with('user')
            ->orderByDesc('avg_rating')
            ->orderByDesc('review_count')
            ->first();

        if (!$featuredProfile) {
            $featuredProfile = BusinessProfile::query()->with('user')->first();
        }

        if (!$featuredProfile) {
            return null;
        }

        $user = $featuredProfile->user;
        $location = array_filter([$featuredProfile->city, $featuredProfile->state]);

        return [
            'id' => $user->id,
            'business_name' => $featuredProfile->business_name,
            'specialty' => $featuredProfile->bio ? \Illuminate\Support\Str::limit($featuredProfile->bio, 80) : 'Elite Home Service Specialist',
            'location' => !empty($location) ? implode(', ', $location) : 'Nationwide',
            'avg_rating' => (float) $featuredProfile->avg_rating,
            'review_count' => (int) $featuredProfile->review_count,
            'is_elite' => (bool) $featuredProfile->is_elite,
            'is_veteran_owned' => (bool) $featuredProfile->is_veteran_owned,
            'is_id_verified' => (bool) $featuredProfile->is_id_verified,
            'avatar' => $user->avatar,
            'member_since' => $featuredProfile->member_since?->format('Y') ?? null,
            'badge_label' => 'SPONSORED | ELITE PARTNER',
        ];
    }

    /**
     * Private helper to calculate aggregate platform metrics.
     */
    private function getPlatformMetrics(): array
    {
        $verifiedPros = BusinessProfile::where('is_id_verified', true)->count();
        $totalProjects = Project::where('status', 'completed')->count();
        $citiesCount = BusinessProfile::whereNotNull('city')->distinct('city')->count('city');
        
        $satisfactionRate = '98.7%';
        $avgRating = Review::avg('rating');
        if ($avgRating) {
            $satisfactionRate = number_format(($avgRating / 5) * 100, 1) . '%';
        }

        return [
            'verified_pros_count' => $verifiedPros > 0 ? $verifiedPros : 50000,
            'verified_pros_display' => $verifiedPros > 0 ? number_format($verifiedPros) . '+' : '50,000+',
            'jobs_completed_count' => $totalProjects > 0 ? $totalProjects : 1200000,
            'jobs_completed_display' => $totalProjects > 0 ? number_format($totalProjects) . '+' : '1.2M+',
            'cities_served_count' => $citiesCount > 0 ? $citiesCount : 850,
            'cities_served_display' => $citiesCount > 0 ? number_format($citiesCount) . '+' : '850+',
            'customer_satisfaction_rate' => $satisfactionRate,
        ];
    }

    /**
     * Private helper to fetch service categories with services count.
     */
    private function getServiceCategories()
    {
        return Category::query()
            ->where('type', 'service')
            ->withCount('services')
            ->get();
    }

    /**
     * Private helper to fetch top customer testimonials.
     */
    private function getTestimonials()
    {
        return Review::query()
            ->where('rating', '>=', 4)
            ->whereNotNull('comment')
            ->with(['customer.customerProfile', 'project'])
            ->latest()
            ->take(6)
            ->get();
    }

    /**
     * Private helper to provide the 6 trust pillars.
     */
    private function getValuePropositions(): array
    {
        return [
            [
                'key' => 'id_verification',
                'title' => 'ID.me Verification',
                'description' => 'Every pro passes government-grade identity and credential checks.',
            ],
            [
                'key' => 'verified_reviews',
                'title' => 'Real Customer Reviews',
                'description' => 'Only customers with completed projects can leave verified reviews.',
            ],
            [
                'key' => 'transparent_pricing',
                'title' => 'Clear, Upfront Quotes',
                'description' => 'No surprise fees or hidden charges. Review clear estimates upfront.',
            ],
            [
                'key' => 'direct_chat',
                'title' => 'Secure In-App Messaging',
                'description' => 'Chat directly with pros, share project details, and track conversations safely.',
            ],
            [
                'key' => 'elite_contractors',
                'title' => 'Elite Badge Program',
                'description' => 'Top performers and veteran-owned businesses highlighted for excellence.',
            ],
            [
                'key' => 'guaranteed_satisfaction',
                'title' => 'Service Guarantee',
                'description' => 'Milestone payments and invoice protection ensure your satisfaction.',
            ],
        ];
    }

    /**
     * Private helper to provide the 4 workflow steps.
     */
    private function getHowItWorksSteps(): array
    {
        return [
            [
                'step' => '01',
                'title' => 'Search a Service',
                'description' => 'Find verified local professionals tailored to your home project.',
            ],
            [
                'step' => '02',
                'title' => 'Request a Quote',
                'description' => 'Describe your project, timeline, and budget in minutes.',
            ],
            [
                'step' => '03',
                'title' => 'Compare & Hire',
                'description' => 'Review quotes, chat with contractors, and choose the best match.',
            ],
            [
                'step' => '04',
                'title' => 'Pay Securely & Review',
                'description' => 'Approve completed work, pay digital invoices, and share your feedback.',
            ],
        ];
    }
}
