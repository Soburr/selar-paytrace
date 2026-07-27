<?php

namespace Soburr\PaymentTracker;

use Illuminate\Support\ServiceProvider;

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
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/payment-tracker.php' => config_path('payment-tracker.php'),
        ], 'payment-tracker-config');
    }
}