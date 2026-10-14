<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Password reset emails link to the React app instead of a Laravel route.
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return rtrim(config('valorhub.frontend_url'), '/')
                . '/reset-password?token=' . $token . '&email=' . urlencode($user->getEmailForPasswordReset());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by(strtolower((string) $request->input('email')) . '|' . $request->ip());
        });
    }
}
