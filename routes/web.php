<?php

use Illuminate\Support\Facades\Route;
use Soburr\PaymentTracker\Http\Controllers\PaystackWebhookController;
use Soburr\PaymentTracker\Http\Controllers\ConfirmProductAccessController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);

Route::post('/api/payment-tracks/{trackingToken}/confirm-access', ConfirmProductAccessController::class)
    ->middleware('internal-secret');





require __DIR__.'/settings.php';
