<?php

namespace Soburr\PaymentTracker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Soburr\PaymentTracker\Models\PaymentTrack;
use Soburr\PaymentTracker\Services\PaystackSignatureVerifier;
use Soburr\PaymentTracker\Services\PaystackVerificationService;

class PaystackWebhookController extends Controller
{
    public function __construct(
        protected PaystackSignatureVerifier $verifier,
        protected PaystackVerificationService $verificationService
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

        $event = $payload['event'] ?? null;

        // We only care about successful charges for now. Paystack sends
        // MANY different event types (transfer.success, refund.processed,
        // etc.) to the same URL. We must not assume every webhook is a
        // charge - always check what event this actually is.
        if ($event !== 'charge.success') {
            // Still respond 200 OK, even though we're not acting on it.
            // Paystack considers anything other than a 2xx response a
            // FAILED delivery and will retry aggressively. Since we
            // genuinely received and understood this event (we just
            // chose not to act on it), a 200 is the honest response.
            return response()->json(['message' => 'Event ignored', 'event' => $event]);
        }

        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;
        $amountKobo = $data['amount'] ?? null;
        $currency = $data['currency'] ?? 'NGN';

        // Defensive check: a genuine Paystack charge.success event will
        // always include a reference and amount, but we never trust
        // that blindly - if either is missing, something is malformed,
        // and we reject rather than insert incomplete/broken data.
        if (! $reference || ! $amountKobo) {
            return response()->json(['message' => 'Malformed payload'], 422);
        }

        // IDEMPOTENCY CHECK - Paystack can and will send the same
        // event more than once (network retries, redundancy systems).
        // Before inserting, check whether we've already recorded this
        // exact reference. If so, this is a repeat delivery of
        // something we already know about - not a new payment, and
        // not an error. We respond successfully without touching the
        // database again.
        $existing = PaymentTrack::where('paystack_reference', $reference)->first();

        if ($existing) {
            return response()->json([
                'message' => 'Already recorded (duplicate delivery)',
                'tracking_token' => $existing->tracking_token,
            ]);
        }

        // Belt-and-suspenders: even with the check above, it's
        // theoretically possible for two near-simultaneous duplicate
        // webhook deliveries to both pass the "does it exist?" check
        // before either has finished inserting (a race condition).
        // The database's own unique constraint (from Milestone 1) is
        // our real, unbreakable safety net - we catch its rejection
        // here and turn it into the same graceful response, instead
        // of letting it crash as a raw 500 error.
        try {
            $track = PaymentTrack::create([
                'tracking_token' => PaymentTrack::generateTrackingToken(),
                'paystack_reference' => $reference,
                'status' => 'payment_received',
                'amount_kobo' => $amountKobo,
                'currency' => $currency,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $existing = PaymentTrack::where('paystack_reference', $reference)->first();

            return response()->json([
                'message' => 'Already recorded (duplicate delivery)',
                'tracking_token' => $existing?->tracking_token,
            ]);
        }

        // Now that the record exists, independently confirm it with
        // Paystack's own Verify API before advancing its status.
        $this->verifyAndAdvance($track);

        return response()->json([
            'message' => 'Payment tracked successfully',
            'tracking_token' => $track->tracking_token,
        ]);
    }

    /**
     * Independently confirms the transaction with Paystack's own API
     * (not just trusting the webhook payload alone) and, only if
     * genuinely confirmed, moves the status forward to 'verified'.
     */
    protected function verifyAndAdvance(PaymentTrack $track): void
    {
        if ($this->verificationService->isSuccessful($track->paystack_reference)) {
            $track->transitionTo('verified');
        }
        // If verification fails or can't be confirmed, we deliberately
        // do nothing here - the record simply stays at
        // 'payment_received' until it can be genuinely confirmed,
        // rather than us guessing.
    }
}