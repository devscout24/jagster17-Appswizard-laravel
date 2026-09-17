<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display listing of marketplace products.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $status = $request->query('status');
        $inStock = $request->query('in_stock');
        $isTrending = $request->query('is_trending');
        $isElite = $request->query('is_elite_tier');

        $query = Product::with(['business.businessProfile', 'category']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('business.businessProfile', function ($bq) use ($search) {
                        $bq->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($inStock !== null && $inStock !== '') {
            $query->where('in_stock', (bool) $inStock);
        }

        if ($isTrending !== null && $isTrending !== '') {
            $query->where('is_trending', (bool) $isTrending);
        }

        if ($isElite !== null && $isElite !== '') {
            $query->where('is_elite_tier', (bool) $isElite);
        }

        $products = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::where('type', 'product')->get();

        return view('admin.products.index', compact('products', 'categories', 'search', 'categoryId', 'status', 'inStock', 'isTrending', 'isElite'));
    }

    /**
     * Show create product form.
     */
    public function create(): View
    {
        $contractors = User::whereHas('roles', fn($q) => $q->where('name', 'business'))
            ->with('businessProfile')
            ->get();
        $categories = Category::where('type', 'product')->get();

        return view('admin.products.create', compact('contractors', 'categories'));
    }

    /**
     * Store new product.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_id' => ['required', 'exists:users,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'is_elite_tier' => ['nullable', 'boolean'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'shipping_info' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string'], // newline separated or json
            'status' => ['required', 'in:active,off'],
        ]);

        $featuresArray = [];
        if (!empty($validated['features'])) {
            $featuresArray = array_filter(array_map('trim', explode("\n", $validated['features'])));
        }

        Product::create([
            'business_id' => $validated['business_id'],
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? 'SKU-' . strtoupper(bin2hex(random_bytes(4))),
            'price' => $validated['price'],
            'unit' => $validated['unit'] ?? 'each',
            'stock_quantity' => $validated['stock_quantity'],
            'in_stock' => $request->boolean('in_stock', true),
            'is_trending' => $request->boolean('is_trending'),
            'is_elite_tier' => $request->boolean('is_elite_tier'),
            'warranty' => $validated['warranty'],
            'shipping_info' => $validated['shipping_info'],
            'description' => $validated['description'],
            'features' => $featuresArray,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Marketplace product listed successfully.');
    }

    /**
     * Show product details.
     */
    public function show(int $id): View
    {
        $product = Product::with(['business.businessProfile', 'category'])->findOrFail($id);

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show edit product form.
     */
    public function edit(int $id): View
    {
        $product = Product::with(['business.businessProfile', 'category'])->findOrFail($id);
        $contractors = User::whereHas('roles', fn($q) => $q->where('name', 'business'))
            ->with('businessProfile')
            ->get();
        $categories = Category::where('type', 'product')->get();

        return view('admin.products.edit', compact('product', 'contractors', 'categories'));
    }

    /**
     * Update product.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'business_id' => ['required', 'exists:users,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'is_elite_tier' => ['nullable', 'boolean'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'shipping_info' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string'],
            'status' => ['required', 'in:active,off'],
        ]);

        $featuresArray = [];
        if (!empty($validated['features'])) {
            $featuresArray = array_filter(array_map('trim', explode("\n", $validated['features'])));
        }

        $product->update([
            'business_id' => $validated['business_id'],
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? $product->sku,
            'price' => $validated['price'],
            'unit' => $validated['unit'],
            'stock_quantity' => $validated['stock_quantity'],
            'in_stock' => $request->boolean('in_stock'),
            'is_trending' => $request->boolean('is_trending'),
            'is_elite_tier' => $request->boolean('is_elite_tier'),
            'warranty' => $validated['warranty'],
            'shipping_info' => $validated['shipping_info'],
            'description' => $validated['description'],
            'features' => $featuresArray,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Delete product.
     */
    public function destroy(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product removed successfully.');
    }
}
