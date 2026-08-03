<?php

namespace Soburr\PaymentTracker\Services;

class PaystackSignatureVerifier
{
    public function isValid(string $rawBody, ?string $signatureHeader): bool
    {
        if (empty($signatureHeader)) {
            return false;
        }

        $secretKey = config('payment-tracker.paystack_secret_key');

        if (empty($secretKey)) {
            return false;
        }
        $computedSignature = hash_hmac('sha512', $rawBody, $secretKey);

        return hash_equals($computedSignature, $signatureHeader);
    }
}