<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CategoryIndexRequest;
use App\Http\Resources\CategoryResource;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Project;
use App\Models\Review;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    /**
     * Provide all data required to render the Category Directory screen.
     */
    public function index(CategoryIndexRequest $request): JsonResponse
    {
        $type = $request->validated('type', 'service');
        $search = $request->validated('search');

        $categories = Category::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('services')
            ->get();

        $metrics = $this->getPlatformMetrics();
        $popularTags = $this->getPopularTags();

        $payload = [
            'hero' => [
                'headline' => 'Explore Professional Services',
                'subtitle' => 'Browse hundreds of verified services provided by trusted professionals across the United States.',
            ],
            'popular_tags' => $popularTags,
            'metrics' => $metrics,
            'categories' => CategoryResource::collection($categories),
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

        return $this->success($payload, 'Categories directory retrieved successfully');
    }

    /**
     * Provide single category details with service counts.
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::withCount('services')->find($id);

        if (!$category) {
            return $this->notFound('Category not found');
        }

        return $this->success(new CategoryResource($category), 'Category details retrieved successfully');
    }

    /**
     * Private helper to fetch platform trust/metrics bar.
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
            'verified_pros_display' => $verifiedPros > 0 ? number_format($verifiedPros) . '+' : '50,000+',
            'jobs_completed_display' => $totalProjects > 0 ? number_format($totalProjects) . '+' : '1.2M+',
            'cities_served_display' => $citiesCount > 0 ? number_format($citiesCount) . '+' : '850+',
            'customer_satisfaction_rate' => $satisfactionRate,
        ];
    }

    /**
     * Private helper to fetch popular search tags shown under the search bar.
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
