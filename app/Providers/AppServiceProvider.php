<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        // Password reset emails link to the React site, not the Laravel app
        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim(config('app.frontend_url'), '/')
            . '/reset-password?token=' . $token . '&email=' . urlencode($user->getEmailForPasswordReset()));
    }
}
