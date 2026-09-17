<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show admin login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle admin login attempt.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();

            if ($user->status && $user->status !== 'active') {
                Auth::guard('web')->logout();
                return back()->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => 'Your account has been deactivated.']);
            }

            $hasAdminRole = $user->hasRole(['admin', 'super_admin']) ||
                $user->hasRole(['admin', 'super_admin'], 'api') ||
                $user->roles()->whereIn('name', ['admin', 'super_admin'])->exists();

            if (!$hasAdminRole) {
                Auth::guard('web')->logout();
                return back()->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => 'Access denied. You do not have administrator permissions.']);
            }

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back to the Admin Dashboard!');
        }

        return back()->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
    }

    /**
     * Log out admin user.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('info', 'You have been successfully logged out.');
    }
}
