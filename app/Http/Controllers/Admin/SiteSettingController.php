<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'groups' => SiteSetting::FIELDS,
            'values' => SiteSetting::allValues(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (SiteSetting::FIELDS as $fields) {
            foreach (array_keys($fields) as $key) {
                $rules[$key] = match (true) {
                    str_ends_with($key, '_url') => ['nullable', 'url', 'max:255'],
                    str_ends_with($key, '_email') => ['nullable', 'email', 'max:255'],
                    str_ends_with($key, '_percent') => ['nullable', 'numeric', 'min:0', 'max:100'],
                    default => ['nullable', 'string', 'max:500'],
                };
            }
        }

        SiteSetting::setMany($request->validate($rules));

        return back()->with('success', 'Site settings saved. The website picks them up immediately.');
    }
}
