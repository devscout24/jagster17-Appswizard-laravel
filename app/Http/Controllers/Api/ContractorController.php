<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contractor\ContractorFilterRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ContractorCardResource;
use App\Http\Resources\ContractorProfileResource;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class ContractorController extends Controller
{
    use ApiResponseTrait;

    /**
     * Provide all data required to render the Contractor List screen with filtering.
     */
    public function index(ContractorFilterRequest $request): JsonResponse
    {
        $serviceQuery = $request->validated('service');
        $zipCode = $request->validated('zip_code');
        $categoryId = $request->validated('category_id');
        $categoryIds = $request->validated('category_ids', []);
        if ($categoryId && !in_array($categoryId, $categoryIds)) {
            $categoryIds[] = (int) $categoryId;
        }

        $isVeteran = $request->validated('is_veteran_owned');
        $isElite = $request->validated('is_elite');
        $isVerified = $request->validated('is_id_verified');
        $isAvailableToday = $request->validated('is_available_today');
        $minRating = $request->validated('min_rating');
        $sortBy = $request->validated('sort_by', 'rating_desc');
        $perPage = (int) ($request->validated('per_page', 6));

        // Base contractor query
        $query = User::query()
            ->whereHas('businessProfile')
            ->with(['businessProfile', 'services.category'])
            ->where('status', 'active');

        // Filter: Keyword / Service Name / Business Name
        if ($serviceQuery) {
            $query->where(function ($q) use ($serviceQuery) {
                $q->where('name', 'like', "%{$serviceQuery}%")
                  ->orWhereHas('businessProfile', function ($bq) use ($serviceQuery) {
                      $bq->where('business_name', 'like', "%{$serviceQuery}%")
                        ->orWhere('bio', 'like', "%{$serviceQuery}%");
                  })
                  ->orWhereHas('services', function ($sq) use ($serviceQuery) {
                      $sq->where('name', 'like', "%{$serviceQuery}%")
                        ->orWhere('description', 'like', "%{$serviceQuery}%");
                  });
            });
        }

        // Filter: Zip code / Location
        if ($zipCode) {
            $query->whereHas('businessProfile', function ($bq) use ($zipCode) {
                $bq->where('city', 'like', "%{$zipCode}%")
                  ->orWhere('state', 'like', "%{$zipCode}%");
            });
        }

        // Filter: Categories
        if (!empty($categoryIds)) {
            $query->whereHas('services', function ($sq) use ($categoryIds) {
                $sq->whereIn('category_id', $categoryIds);
            });
        }

        // Filter: Badges & Credentials
        if ($isVeteran !== null) {
            $query->whereHas('businessProfile', fn ($bq) => $bq->where('is_veteran_owned', (bool) $isVeteran));
        }
        if ($isElite !== null) {
            $query->whereHas('businessProfile', fn ($bq) => $bq->where('is_elite', (bool) $isElite));
        }
        if ($isVerified !== null) {
            $query->whereHas('businessProfile', fn ($bq) => $bq->where('is_id_verified', (bool) $isVerified));
        }
        if ($isAvailableToday !== null) {
            $query->whereHas('businessProfile', fn ($bq) => $bq->where('is_available_today', (bool) $isAvailableToday));
        }

        // Filter: Min Rating
        if ($minRating !== null) {
            $query->whereHas('businessProfile', fn ($bq) => $bq->where('avg_rating', '>=', (float) $minRating));
        }

        // Sorting
        $this->applySorting($query, $sortBy);

        $paginator = $query->paginate($perPage);

        // Compute Hero context (e.g. active category details)
        $heroContext = $this->getHeroContext($categoryId);
        $sidebarCategories = Category::where('type', 'service')->get(['id', 'name']);
        $popularTags = $this->getPopularTags();

        $payload = [
            'hero' => $heroContext,
            'popular_tags' => $popularTags,
            'filter_options' => [
                'categories' => $sidebarCategories,
                'credentials' => [
                    ['key' => 'is_veteran_owned', 'label' => 'Veteran Owned'],
                    ['key' => 'is_elite', 'label' => 'Elite Member'],
                    ['key' => 'is_id_verified', 'label' => 'Verified'],
                    ['key' => 'is_available_today', 'label' => 'Available Today'],
                ],
                'ratings' => [
                    ['value' => null, 'label' => 'Any Rating'],
                    ['value' => 4.8, 'label' => '4.8 & above'],
                    ['value' => 4.5, 'label' => '4.5 & above'],
                    ['value' => 4.0, 'label' => '4.0 & above'],
                ],
            ],
            'applied_filters' => [
                'service' => $serviceQuery,
                'zip_code' => $zipCode,
                'category_ids' => $categoryIds,
                'is_veteran_owned' => $isVeteran,
                'is_elite' => $isElite,
                'is_id_verified' => $isVerified,
                'is_available_today' => $isAvailableToday,
                'min_rating' => $minRating,
                'sort_by' => $sortBy,
            ],
            'items' => ContractorCardResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'cta_banner' => [
                'headline' => 'Grow Your Business on ValorHub',
                'subtitle' => 'Join 50,000+ verified professionals. Access qualified leads, manage quotes, and build your reputation.',
                'primary_cta' => [
                    'label' => 'Join as a Professional',
                    'action' => 'register_business',
                ],
                'secondary_cta' => [
                    'label' => 'Learn More',
                    'action' => 'about_professionals',
                ],
            ],
        ];

        return $this->success($payload, 'Contractors retrieved successfully');
    }

    /**
     * Provide single contractor detailed profile.
     */
    public function show(int $id): JsonResponse
    {
        $contractor = User::whereHas('businessProfile')
            ->with([
                'businessProfile',
                'services.category',
                'products.category',
                'reviews.customer.customerProfile',
                'reviews.project',
            ])
            ->find($id);

        if (!$contractor) {
            return $this->notFound('Contractor not found');
        }

        return $this->success(new ContractorProfileResource($contractor), 'Contractor profile retrieved successfully');
    }

    /**
     * Private helper to build hero category metrics and titles.
     */
    private function getHeroContext(?int $categoryId): array
    {
        $category = null;
        if ($categoryId) {
            $category = Category::find($categoryId);
        }

        if (!$category) {
            $category = Category::where('type', 'service')->first();
        }

        $categoryTitle = $category?->name ?? 'Blue Collar Services';
        $categorySubtitle = $category?->description ?? 'Skilled trades & construction professionals';

        $totalServices = Service::count();
        $totalPros = BusinessProfile::count();
        $avgRating = BusinessProfile::avg('avg_rating');

        return [
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Category', 'url' => '/categories'],
                ['label' => 'Contractor List', 'url' => null],
            ],
            'active_category' => [
                'id' => $category?->id,
                'name' => $categoryTitle,
                'subtitle' => $categorySubtitle,
            ],
            'metrics' => [
                'services_count_display' => $totalServices > 0 ? $totalServices.'+' : '180+',
                'professionals_count_display' => $totalPros > 0 ? number_format($totalPros).'+' : '3,763+',
                'avg_rating_display' => $avgRating ? number_format($avgRating, 1).'★' : '4.8★',
            ],
        ];
    }

    /**
     * Private helper to apply sorting order.
     */
    private function applySorting($query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'reviews_desc':
                $query->join('business_profiles', 'users.id', '=', 'business_profiles.user_id')
                      ->orderByDesc('business_profiles.review_count')
                      ->select('users.*');
                break;
            case 'price_asc':
                $query->join('business_profiles', 'users.id', '=', 'business_profiles.user_id')
                      ->orderBy('business_profiles.hourly_rate', 'asc')
                      ->select('users.*');
                break;
            case 'price_desc':
                $query->join('business_profiles', 'users.id', '=', 'business_profiles.user_id')
                      ->orderByDesc('business_profiles.hourly_rate')
                      ->select('users.*');
                break;
            case 'newest':
                $query->latest('users.created_at');
                break;
            case 'rating_desc':
            default:
                $query->join('business_profiles', 'users.id', '=', 'business_profiles.user_id')
                      ->orderByDesc('business_profiles.avg_rating')
                      ->orderByDesc('business_profiles.review_count')
                      ->select('users.*');
                break;
        }
    }

    /**
     * Private helper to return popular search tags.
     */
    private function getPopularTags(): array
    {
        return [
            'Marketing',
            'Plumbing',
            'IT Support',
            'Electrician',
            'Home Cleaning',
            'Roofing',
            'Accounting',
            'Attorney',
            'Doctor',
            'UX/UI Design',
        ];
    }
}
