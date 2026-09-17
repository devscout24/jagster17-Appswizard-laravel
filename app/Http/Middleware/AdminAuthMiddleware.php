<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->guest(route('admin.login'))
                ->with('error', 'Please log in to access the Admin Panel.');
        }

        $user = Auth::guard('web')->user();

        // Check if user status is active
        if ($user->status && $user->status !== 'active') {
            Auth::guard('web')->logout();
            return redirect()->route('admin.login')
                ->with('error', 'Your account has been deactivated. Please contact support.');
        }

        // Allow users with admin or super_admin role, or any user that has access permission
        $hasAdminRole = $user->hasRole(['admin', 'super_admin']) ||
            $user->hasRole(['admin', 'super_admin'], 'api') ||
            $user->roles()->whereIn('name', ['admin', 'super_admin'])->exists();

        if (!$hasAdminRole) {
            Auth::guard('web')->logout();
            return redirect()->route('admin.login')
                ->with('error', 'Access denied. You do not have administrator permissions.');
        }

        return $next($request);
    }
}
