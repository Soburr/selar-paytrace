<?php

namespace Soburr\PaymentTracker\Services;

class PaystackSignatureVerifier
{
    /**
     * Checks whether an incoming webhook request genuinely came from
     * Paystack, by recomputing the signature ourselves and comparing
     * it to the one Paystack sent in the request header.
     *
     * @param string $rawBody     The EXACT raw request body, unmodified.
     *                            Must be the raw bytes, not a decoded/
     *                            re-encoded version — even whitespace
     *                            differences would break the match.
     * @param string|null $signatureHeader  The value of the
     *                            'x-paystack-signature' header from
     *                            the incoming request.
     */
    public function isValid(string $rawBody, ?string $signatureHeader): bool
    {
        // No signature header at all? Reject immediately — a genuine
        // Paystack webhook always includes one.
        if (empty($signatureHeader)) {
            return false;
        }

        $secretKey = config('payment-tracker.paystack_secret_key');

        if (empty($secretKey)) {
            // If we don't even have a secret key configured, we CANNOT
            // safely verify anything. Fail closed (reject), never fail
            // open (accept) — a missing config value should never
            // silently turn into "trust everything."
            return false;
        }

        // Recompute the hash ourselves, the same way Paystack did:
        // HMAC-SHA512 of the raw body, using our shared secret key.
        $computedSignature = hash_hmac('sha512', $rawBody, $secretKey);

        // hash_equals() instead of === is deliberate — a plain string
        // comparison can leak timing information (how many leading
        // characters matched) that an attacker could exploit to guess
        // the correct signature byte-by-byte over many attempts.
        // hash_equals() takes the same amount of time regardless of
        // where a mismatch occurs, closing that side-channel.
        return hash_equals($computedSignature, $signatureHeader);
    }
}