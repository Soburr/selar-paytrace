<?php

namespace Soburr\PaymentTracker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Soburr\PaymentTracker\Services\PaystackSignatureVerifier;

class PaystackWebhookController extends Controller
{
    public function __construct(
        protected PaystackSignatureVerifier $verifier
    ) {}

    public function handle(Request $request)
    {
        // getContent() gets the RAW, untouched body of the request —
        // exactly the bytes Paystack sent. This matters because the
        // signature was calculated against these exact raw bytes.
        // If Laravel had parsed/reformatted this into an array first
        // and we recombined it, even a tiny formatting difference
        // would make our signature calculation not match Paystack's.
        $rawBody = $request->getContent();

        // Pull the signature Paystack attached, out of the request headers.
        $signature = $request->header('x-paystack-signature');

        // This is the moment everything from Step 2 gets used.
        if (! $this->verifier->isValid($rawBody, $signature)) {
            // 401 = "Unauthorized". We reject with NO further processing.
            // We don't touch the database, don't read the payload as
            // trustworthy data, nothing — an unverified request gets
            // stopped here, permanently.
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // If we reach this line, we've PROVEN this request is genuinely
        // from Paystack. Only now is it safe to look at what's inside.
        $payload = json_decode($rawBody, true);

        // For now, just prove the whole pipeline works end to end.
        // In Step 4, this is where we actually create the database row.
        return response()->json([
            'message' => 'Signature verified successfully',
            'event' => $payload['event'] ?? 'unknown',
        ]);
    }
}