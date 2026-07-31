<?php

namespace Soburr\PaymentTracker\Http\Controllers;

use Illuminate\Routing\Controller;
use Soburr\PaymentTracker\Exceptions\InvalidStatusTransitionException;
use Soburr\PaymentTracker\Models\PaymentTrack;

class ConfirmProductAccessController extends Controller
{
    public function __invoke(string $trackingToken)
    {
        // We look up by the PUBLIC tracking token here, not the
        // Paystack reference - this endpoint is meant to be called
        // using the same identifier a buyer would have, keeping the
        // real Paystack reference out of this flow entirely too.
        $track = PaymentTrack::where('tracking_token', $trackingToken)->first();

        if (! $track) {
            return response()->json(['message' => 'Tracking token not found'], 404);
        }

        try {
            $track->transitionTo('product_access_confirmed');
        } catch (InvalidStatusTransitionException $e) {
            // This happens if someone tries to confirm access on a
            // payment that hasn't even been verified yet, for example.
            // We report it clearly rather than silently ignoring it.
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Product access confirmed',
            'status' => $track->status,
        ]);
    }
}