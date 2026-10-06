<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CMS pages served to the website, e.g. Privacy Policy, Terms of Service, About.
 */
class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Page::create($this->validated($request));

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('title'))]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'content' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ]);
        $validated['is_published'] = $request->boolean('is_published');

        return $validated;
    }
}
