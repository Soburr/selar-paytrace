<?php

namespace Soburr\PaymentTracker;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
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
    }
}