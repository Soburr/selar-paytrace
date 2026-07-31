<?php

namespace Soburr\PaymentTracker\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackVerificationService
{
    /**
     * Independently confirms a transaction's status directly from
     * Paystack's own API - not just trusting the webhook payload.
     * Returns true only if Paystack's own records say this specific
     * reference is genuinely a successful charge.
     */
    public function isSuccessful(string $reference): bool
    {
        $secretKey = config('payment-tracker.paystack_secret_key');

        try {
            $response = Http::withToken($secretKey)
                ->timeout(10)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network failure talking to Paystack. We do NOT assume
            // success just because we couldn't check - fail closed,
            // same principle as the signature verifier: when unsure,
            // say no, don't say yes.
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

        // Paystack's real response nests the actual transaction status
        // inside data.status, as the string "success" specifically.
        return ($body['data']['status'] ?? null) === 'success';
    }
}