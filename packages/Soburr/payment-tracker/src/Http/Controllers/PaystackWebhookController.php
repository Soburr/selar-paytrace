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

        $rawBody = $request->getContent();

        $signature = $request->header('x-paystack-signature');

        if (! $this->verifier->isValid($rawBody, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawBody, true);

        $event = $payload['event'] ?? null;

        if ($event !== 'charge.success') {
            return response()->json(['message' => 'Event ignored', 'event' => $event]);
        }

        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;
        $amountKobo = $data['amount'] ?? null;
        $currency = $data['currency'] ?? 'NGN';

        if (! $reference || ! $amountKobo) {
            return response()->json(['message' => 'Malformed payload'], 422);
        }

        $existing = PaymentTrack::where('paystack_reference', $reference)->first();

        if ($existing) {
            return response()->json([
                'message' => 'Already recorded (duplicate delivery)',
                'tracking_token' => $existing->tracking_token,
            ]);
        }

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

        $this->verifyAndAdvance($track);

        return response()->json([
            'message' => 'Payment tracked successfully',
            'tracking_token' => $track->tracking_token,
        ]);
    }

    protected function verifyAndAdvance(PaymentTrack $track): void
    {
        if ($this->verificationService->isSuccessful($track->paystack_reference)) {
            $track->transitionTo('verified');
        }
    }
}