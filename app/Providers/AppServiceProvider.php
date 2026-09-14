<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
    \App\Models\User::observe(\App\Observers\UserRankObserver::class);
    \App\Models\User::observe(\App\Observers\UserActivityObserver::class);
    \Illuminate\Support\Facades\Event::listen(
        \Illuminate\Auth\Events\Login::class,
        \App\Listeners\AwardLoginPoints::class
    );

    // Bypass total para rol Admin: pasa cualquier Gate/Policy check.
    // Devolvemos `true` (autoriza) o `null` (sigue la lógica normal).
    Gate::before(function ($user, $ability) {
        return $user->hasRole('Admin') ? true : null;
    });

    // Existing boot logic...
        $this->configureRateLimiting();
        $this->configureUrlDefaults();
    }

    /**
     * Configure rate limiting for the application
     */
    protected function configureRateLimiting(): void
    {
        // Global rate limiter
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Login attempts - 5 per minute per IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response('Demasiados intentos de login. Por favor intente en unos minutos.', 429);
            });
        });

        // API/Auth routes - 10 per minute per user
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // PDF downloads - 20 per hour per user
        RateLimiter::for('pdf-downloads', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        // Webhook endpoints - 10 per minute
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Battle creation - 10 per hour per user
        RateLimiter::for('battles', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // Pomodoro timer API - 120 requests per hour per user (2 per minute average)
        RateLimiter::for('study', function (Request $request) {
            return Limit::perHour(120)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure URL defaults
     */
    protected function configureUrlDefaults(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
