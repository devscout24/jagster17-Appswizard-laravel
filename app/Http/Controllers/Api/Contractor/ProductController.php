<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $products = Product::with('category')
            ->where('business_id', $user->id)
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->latest()
            ->get();

        return $this->success([
            'items' => $products->map(fn (Product $product) => $this->present($product)),
            'usage' => [
                'used' => $products->count(),
                // Product listings are unlimited on Elite; other plans fall back to the service limit
                'limit' => $user->subscription?->plan?->slug === 'elite' ? null : $user->subscription?->plan?->service_limit,
            ],
        ], 'Products retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $limit = $user->subscription?->plan?->slug === 'elite' ? null : $user->subscription?->plan?->service_limit;

        if ($limit !== null && Product::where('business_id', $user->id)->count() >= $limit) {
            return $this->error("Your plan allows {$limit} product listings. Upgrade to add more.", 422);
        }

        $product = Product::create(array_merge($this->validated($request), [
            'business_id' => $user->id,
            'status' => 'active',
        ]));

        return $this->success($this->present($product->load('category')), 'Product added.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = $this->find($id);
        $product->update($this->validated($request, true));

        return $this->success($this->present($product->load('category')), 'Product updated.');
    }

    /**
     * Turn a listing on or off (the Active / Off switch).
     */
    public function toggle(int $id): JsonResponse
    {
        $product = $this->find($id);
        $product->update(['status' => $product->status === 'active' ? 'off' : 'active']);

        return $this->success($this->present($product->load('category')), $product->status === 'active' ? 'Product is now active.' : 'Product turned off.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->find($id)->delete();

        return $this->success(null, 'Product deleted.');
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $data = $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'category_id' => [$required, 'integer', Rule::exists('categories', 'id')->where('type', 'product')],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => [$required, 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'image' => ['nullable', 'image', 'max:5120'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'in_stock' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        return $data;
    }

    private function find(int $id): Product
    {
        return Product::where('business_id', auth('api')->id())->findOrFail($id);
    }

    private function present(Product $product): array
    {
        $price = number_format((float) $product->price, 2);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            'description' => $product->description,
            'price' => (float) $product->price,
            'unit' => $product->unit,
            'price_display' => '$' . $price . ($product->unit ? '/' . $product->unit : ''),
            'image_url' => $product->image ? (str_starts_with($product->image, 'http') ? $product->image : Storage::disk('public')->url($product->image)) : null,
            'external_url' => $product->external_url,
            'in_stock' => (bool) $product->in_stock,
            'is_active' => $product->status === 'active',
        ];
    }
}
