<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    /**
     * Display listing of FAQs.
     */
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $search = $request->query('search');

        $query = Faq::query();

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $query->orderBy('sort_order')->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.faqs.index', compact('faqs', 'category', 'search'));
    }

    /**
     * Show create FAQ form.
     */
    public function create(): View
    {
        return view('admin.faqs.create');
    }

    /**
     * Store new FAQ.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:customers,contractors,payments,general'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        Faq::create([
            'category' => $validated['category'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'],
            'is_published' => $request->boolean('is_published', true),
        ]);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ created successfully.');
    }

    /**
     * Show edit FAQ form.
     */
    public function edit(int $id): View
    {
        $faq = Faq::findOrFail($id);

        return view('admin.faqs.edit', compact('faq'));
    }

    /**
     * Update FAQ.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $faq = Faq::findOrFail($id);

        $validated = $request->validate([
            'category' => ['required', 'in:customers,contractors,payments,general'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $faq->update([
            'category' => $validated['category'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'],
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ updated successfully.');
    }

    /**
     * Delete FAQ.
     */
    public function destroy(int $id): RedirectResponse
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ removed successfully.');
    }
}
