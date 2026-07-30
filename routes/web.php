<?php

use Illuminate\Support\Facades\Route;
use Soburr\PaymentTracker\Http\Controllers\PaystackWebhookController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);
require __DIR__.'/settings.php';
