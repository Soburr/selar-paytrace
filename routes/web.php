<?php

use Illuminate\Support\Facades\Route;
use Soburr\PaymentTracker\Http\Controllers\PaystackWebhookController;
use Soburr\PaymentTracker\Http\Controllers\ConfirmProductAccessController;
use Soburr\PaymentTracker\Http\Controllers\TrackPaymentController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);

Route::post('/api/payment-tracks/{trackingToken}/confirm-access', ConfirmProductAccessController::class)
    ->middleware('internal-secret');

Route::get('/track/{trackingToken}', TrackPaymentController::class)
    ->middleware('throttle:payment-tracker-lookup');

require __DIR__.'/settings.php';
