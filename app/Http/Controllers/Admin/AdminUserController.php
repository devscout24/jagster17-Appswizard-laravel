<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Display listing of administrator accounts.
     */
    public function index(): View
    {
        $admins = User::whereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'admin']))
            ->with('roles')
            ->latest()
            ->paginate(15);

        return view('admin.users.index', compact('admins'));
    }

    /**
     * Show create admin form.
     */
    public function create(): View
    {
        $roles = Role::whereIn('name', ['super_admin', 'admin'])->where('guard_name', 'web')->get();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store new admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,admin'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
            'avatar' => 'assets/images/users/user-' . rand(1, 4) . '.jpg',
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', 'Administrator account created successfully.');
    }

    /**
     * Show edit admin form.
     */
    public function edit(int $id): View
    {
        $admin = User::whereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'admin']))
            ->with('roles')
            ->findOrFail($id);
        $roles = Role::whereIn('name', ['super_admin', 'admin'])->where('guard_name', 'web')->get();

        return view('admin.users.edit', compact('admin', 'roles'));
    }

    /**
     * Update admin user.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $admin = User::whereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'admin']))->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $admin->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,admin'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $admin->update($updateData);

        // Sync role
        $admin->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Administrator account updated successfully.');
    }

    /**
     * Delete admin account with self-deletion prevention.
     */
    public function destroy(int $id): RedirectResponse
    {
        if (Auth::guard('web')->id() === $id) {
            return back()->with('error', 'You cannot delete your own administrator account.');
        }

        $admin = User::whereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'admin']))->findOrFail($id);
        $admin->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Administrator account removed successfully.');
    }
}
