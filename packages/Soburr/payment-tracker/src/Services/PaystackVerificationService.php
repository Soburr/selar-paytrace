<?php

namespace Soburr\PaymentTracker\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackVerificationService
{
    public function isSuccessful(string $reference): bool
    {
        $secretKey = config('payment-tracker.paystack_secret_key');

        try {
            $response = Http::withToken($secretKey)
                ->timeout(10)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Paystack verify request failed to connect', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Paystack verify returned a non-success HTTP status', [
                'reference' => $reference,
                'status' => $response->status(),
            ]);

            return false;
        }

        $body = $response->json();
        return ($body['data']['status'] ?? null) === 'success';
    }
}