<?php

namespace Soburr\PaymentTracker;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Soburr\PaymentTracker\Http\Middleware\VerifyInternalSecret;

class PaymentTrackerServiceProvider extends ServiceProvider
{
    /**
     * register() runs FIRST, before any provider's boot().
     * Use it only for binding things into Laravel's container.
     * Do NOT touch routes, views, or the database here — the
     * framework isn't fully ready yet.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/payment-tracker.php',
            'payment-tracker'
        );
    }

    /**
     * boot() runs AFTER every provider has registered.
     * This is where you safely touch migrations, routes, views —
     * anything that depends on the rest of the app being ready.
     */
    public function boot(): void
    {
        // Load our migrations so `php artisan migrate` picks them up
        // automatically — the app using this package never needs to
        // copy migration files manually.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Let the app override our default config by publishing it.
        $this->publishes([
            __DIR__.'/../config/payment-tracker.php' => config_path('payment-tracker.php'),
        ], 'payment-tracker-config');

        // Registers a short, readable name for our middleware, so
        // routes can reference it as 'internal-secret' instead of
        // the full class path every time.
        $this->app['router']->aliasMiddleware('internal-secret', VerifyInternalSecret::class);

        // Defines a named rate limit rule ('payment-tracker-lookup')
        // that routes can reference. The limit itself is read from
        // our config file (Milestone 1), so it's adjustable by
        // whoever installs this package, without touching this code.
        RateLimiter::for('payment-tracker-lookup', function (Request $request) {
            $limit = config('payment-tracker.lookup_rate_limit_per_minute', 20);

            // ->by($request->ip()) means the limit applies PER visitor
            // IP address, not globally across everyone. One person
            // hammering the endpoint doesn't lock out anyone else.
            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}