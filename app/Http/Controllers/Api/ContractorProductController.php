<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contractor\SaveProductRequest;
use App\Http\Resources\ContractorProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use App\Traits\HandlesUploads;
use App\Traits\ResolvesContractorPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractorProductController extends Controller
{
    use ApiResponseTrait, HandlesUploads, ResolvesContractorPlan;

    /**
     * Products: product cards, category tabs and plan usage (Node 3299-1047).
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $base = Product::where('business_id', $user->id);

        $query = (clone $base)->with('category');

        if ($request->filled('category_id') && $request->query('category_id') !== 'all') {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        $categoryCounts = (clone $base)->selectRaw('category_id, count(*) as total')->groupBy('category_id')->pluck('total', 'category_id');
        $tabs = Category::whereIn('id', $categoryCounts->keys())->orderBy('name')->get()
            ->map(fn (Category $category) => [
                'id'    => $category->id,
                'name'  => $category->name,
                'count' => (int) $categoryCounts[$category->id],
            ])->values();

        return $this->success([
            'usage'      => $this->planUsage($user, 'products', (clone $base)->count()),
            'categories' => $tabs,
            'items'      => ContractorProductResource::collection($query->latest()->get()),
        ], 'Products retrieved successfully.');
    }

    /**
     * Add Product (multipart when an image is attached; enforces the plan's product limit).
     */
    public function store(SaveProductRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $used = Product::where('business_id', $user->id)->count();
        $limit = $this->planLimit($user, 'products');

        if ($limit !== null && $used >= $limit) {
            return $this->forbidden($limit === 0
                ? 'Product listings are not included in your plan. Upgrade your membership to sell products.'
                : "Your plan allows up to {$limit} products. Upgrade your membership to add more.");
        }

        $validated = $request->validated();
        if ($request->hasFile('image')) {
            $validated['image'] = $this->storeUpload($request->file('image'), 'products');
        }

        $stock = $validated['stock_quantity'] ?? 10;

        $product = Product::create($validated + [
            'business_id'    => $user->id,
            'status'         => 'active',
            'stock_quantity' => $stock,
            'in_stock'       => $stock > 0,
            'is_elite_tier'  => (bool) $user->businessProfile?->is_elite,
        ]);

        return $this->success(new ContractorProductResource($product->load('category')), 'Product added successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->findOwned($id);

        return $product
            ? $this->success(new ContractorProductResource($product->load('category')), 'Product retrieved successfully.')
            : $this->notFound('Product not found.');
    }

    /**
     * Update a product. Send as POST with _method=PUT when uploading a new image.
     */
    public function update(SaveProductRequest $request, int $id): JsonResponse
    {
        $product = $this->findOwned($id);
        if (! $product) {
            return $this->notFound('Product not found.');
        }

        $validated = $request->validated();
        if ($request->hasFile('image')) {
            $this->deleteUpload($product->image);
            $validated['image'] = $this->storeUpload($request->file('image'), 'products');
        }

        if (array_key_exists('stock_quantity', $validated)) {
            $validated['in_stock'] = $validated['stock_quantity'] > 0;
        }

        $product->update($validated);

        return $this->success(new ContractorProductResource($product->fresh('category')), 'Product updated successfully.');
    }

    /**
     * Active / Off switch on each product card.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $product = $this->findOwned($id);
        if (! $product) {
            return $this->notFound('Product not found.');
        }

        $product->update(['status' => $product->status === 'active' ? 'off' : 'active']);

        return $this->success(
            new ContractorProductResource($product->load('category')),
            $product->status === 'active' ? 'Product is now active.' : 'Product turned off.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->findOwned($id);
        if (! $product) {
            return $this->notFound('Product not found.');
        }

        $this->deleteUpload($product->image);
        $product->delete();

        return $this->success(null, 'Product deleted successfully.');
    }

    private function findOwned(int $id): ?Product
    {
        return Product::where('business_id', auth('api')->id())->find($id);
    }
}
