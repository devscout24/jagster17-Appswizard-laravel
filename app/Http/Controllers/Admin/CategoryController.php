<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display categories list.
     */
    public function index(Request $request): View
    {
        $type = $request->query('type');
        $search = $request->query('search');

        $query = Category::withCount(['services', 'products']);

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        $categories = $query->latest()->paginate(15)->withQueryString();

        return view('admin.categories.index', compact('categories', 'type', 'search'));
    }

    /**
     * Show create category form.
     */
    public function create(): View
    {
        return view('admin.categories.create');
    }

    /**
     * Store new category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'type' => ['required', 'in:service,product'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show edit category form.
     */
    public function edit(int $id): View
    {
        $category = Category::findOrFail($id);

        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update category.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'type' => ['required', 'in:service,product'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete category.
     */
    public function destroy(int $id): RedirectResponse
    {
        $category = Category::withCount(['services', 'products'])->findOrFail($id);

        if ($category->services_count > 0 || $category->products_count > 0) {
            return back()->with('error', 'Cannot delete category that is currently linked to services or products.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
