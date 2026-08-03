<?php

namespace Soburr\PaymentTracker\Http\Controllers;

use Illuminate\Routing\Controller;
use Soburr\PaymentTracker\Exceptions\InvalidStatusTransitionException;
use Soburr\PaymentTracker\Models\PaymentTrack;

class ConfirmProductAccessController extends Controller
{
    public function __invoke(string $trackingToken)
    {
        $track = PaymentTrack::where('tracking_token', $trackingToken)->first();

        if (! $track) {
            return response()->json(['message' => 'Tracking token not found'], 404);
        }

        try {
            $track->transitionTo('product_access_confirmed');
        } catch (InvalidStatusTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Product access confirmed',
            'status' => $track->status,
        ]);
    }
}