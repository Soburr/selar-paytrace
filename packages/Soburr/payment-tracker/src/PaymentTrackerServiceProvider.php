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

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/payment-tracker.php',
            'payment-tracker'
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/payment-tracker.php' => config_path('payment-tracker.php'),
        ], 'payment-tracker-config');

        $this->app['router']->aliasMiddleware('internal-secret', VerifyInternalSecret::class);

        RateLimiter::for('payment-tracker-lookup', function (Request $request) {
            $limit = config('payment-tracker.lookup_rate_limit_per_minute', 20);

            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}