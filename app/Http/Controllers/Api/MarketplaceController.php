<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\MarketplaceFilterRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductDetailResource;
use App\Models\Category;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    use ApiResponseTrait;

    /**
     * Provide all data required for the Marketplace Landing screen.
     */
    public function landing(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->where('type', 'product')
            ->withCount('products')
            ->get();

        if ($categories->isEmpty()) {
            $categories = Category::withCount('products')->get();
        }

        $trendingProducts = Product::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('is_trending', true)
                  ->orWhere('is_elite_tier', true);
            })
            ->with(['business.businessProfile', 'category'])
            ->latest()
            ->take(4)
            ->get();

        if ($trendingProducts->isEmpty()) {
            $trendingProducts = Product::query()
                ->where('status', 'active')
                ->with(['business.businessProfile', 'category'])
                ->latest()
                ->take(4)
                ->get();
        }

        $payload = [
            'hero' => [
                'headline' => 'Shop products directly from trusted contractors',
                'highlight' => 'trusted contractors',
                'description' => 'Browse parts, equipment, and service bundles listed by verified ValorHub professionals.',
                'search_placeholder' => 'Search products, parts, or equipment',
            ],
            'categories' => CategoryResource::collection($categories),
            'trending_products' => ProductCardResource::collection($trendingProducts),
            'seller_cta' => [
                'headline' => 'Have products or parts to sell?',
                'subtitle' => 'List your inventory on the ValorHub Marketplace and reach thousands of homeowners.',
                'action_label' => 'List a product',
                'action_url' => '/dashboard/products/create',
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Marketplace', 'url' => null],
            ],
        ];

        return $this->success($payload, 'Marketplace landing data retrieved successfully');
    }

    /**
     * Provide filterable, paginated product catalog for Browse Products screen.
     */
    public function index(MarketplaceFilterRequest $request): JsonResponse
    {
        $search = $request->validated('search');
        $categoryId = $request->validated('category_id');
        $categoryIds = $request->validated('category_ids', []);
        if ($categoryId && !in_array($categoryId, $categoryIds)) {
            $categoryIds[] = (int) $categoryId;
        }

        $minPrice = $request->validated('min_price');
        $maxPrice = $request->validated('max_price');
        $inStockOnly = $request->validated('in_stock_only');
        $isElite = $request->validated('is_elite');
        $isVerified = $request->validated('is_id_verified');
        $isVeteran = $request->validated('is_veteran_owned');
        $sortBy = $request->validated('sort_by', 'popular');
        $perPage = (int) ($request->validated('per_page', 6));

        $query = Product::query()
            ->where('status', 'active')
            ->with(['business.businessProfile', 'category']);

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('business.businessProfile', function ($bq) use ($search) {
                      $bq->where('business_name', 'like', "%{$search}%");
                  });
            });
        }

        // Category filter
        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        // Price range
        if ($minPrice !== null) {
            $query->where('price', '>=', (float) $minPrice);
        }
        if ($maxPrice !== null) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        // In stock
        if ($inStockOnly) {
            $query->where('in_stock', true);
        }

        // Seller credentials
        if ($isElite !== null) {
            $query->whereHas('business.businessProfile', fn ($bq) => $bq->where('is_elite', (bool) $isElite));
        }
        if ($isVerified !== null) {
            $query->whereHas('business.businessProfile', fn ($bq) => $bq->where('is_id_verified', (bool) $isVerified));
        }
        if ($isVeteran !== null) {
            $query->whereHas('business.businessProfile', fn ($bq) => $bq->where('is_veteran_owned', (bool) $isVeteran));
        }

        // Sorting
        $this->applySorting($query, $sortBy);

        $paginator = $query->paginate($perPage);

        // Sidebar categories
        $productCategories = Category::where('type', 'product')->get(['id', 'name']);
        if ($productCategories->isEmpty()) {
            $productCategories = Category::all(['id', 'name']);
        }

        $payload = [
            'header' => [
                'title' => 'Browse Products',
                'subtitle' => 'Shop parts, equipment, and service bundles from verified ValorHub sellers.',
                'total_count_display' => number_format($paginator->total()).' products',
            ],
            'filter_options' => [
                'categories' => $productCategories,
                'price_range' => [
                    'min' => (float) (Product::min('price') ?? 0),
                    'max' => (float) (Product::max('price') ?? 500),
                ],
                'credentials' => [
                    ['key' => 'is_elite', 'label' => 'Elite Member'],
                    ['key' => 'is_id_verified', 'label' => 'Verified'],
                    ['key' => 'is_veteran_owned', 'label' => 'Veteran Owned'],
                ],
                'availability' => [
                    ['key' => 'in_stock_only', 'label' => 'In stock only'],
                ],
            ],
            'applied_filters' => [
                'search' => $search,
                'category_ids' => $categoryIds,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'in_stock_only' => $inStockOnly,
                'is_elite' => $isElite,
                'is_id_verified' => $isVerified,
                'is_veteran_owned' => $isVeteran,
                'sort_by' => $sortBy,
            ],
            'items' => ProductCardResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Marketplace', 'url' => '/marketplace'],
                ['label' => 'Browse Products', 'url' => null],
            ],
        ];

        return $this->success($payload, 'Products retrieved successfully');
    }

    /**
     * Provide single product details for Product Details screen.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with([
            'business.businessProfile',
            'business.reviews.customer',
            'category',
        ])->find($id);

        if (!$product) {
            return $this->notFound('Product not found');
        }

        return $this->success(new ProductDetailResource($product), 'Product details retrieved successfully');
    }

    /**
     * Private helper to apply catalog sorting.
     */
    private function applySorting($query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'rating_desc':
                $query->orderByDesc('rating');
                break;
            case 'newest':
                $query->latest();
                break;
            case 'popular':
            default:
                $query->orderByDesc('is_trending')
                      ->orderByDesc('is_elite_tier')
                      ->latest();
                break;
        }
    }
}
