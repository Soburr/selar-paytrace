<?php

namespace Soburr\PaymentTracker\Http\Controllers;

use Illuminate\Routing\Controller;
use Soburr\PaymentTracker\Models\PaymentTrack;

class TrackPaymentController extends Controller
{
    public function __invoke(string $trackingToken)
    {
        $track = PaymentTrack::where('tracking_token', $trackingToken)->first();

        if (! $track) {
            return response()->json(['message' => 'Tracking token not found'], 404);
        }

        $isDelayed = $track->status === 'payment_received'
            && $track->created_at->lt(now()->subMinutes(15));

        return response()->json([
            'tracking_token' => $track->tracking_token,
            'status' => $track->status,
            'status_label' => $isDelayed
                ? "This is taking longer than usual. We're still checking - no action needed yet."
                : $this->humanLabel($track->status),
            'is_delayed' => $isDelayed,
            'amount' => $track->amount_kobo / 100,
            'currency' => $track->currency,
            'timeline' => [
                'payment_received_at' => $track->created_at,
                'verified_at' => $track->verified_at,
                'product_access_confirmed_at' => $track->product_access_confirmed_at,
            ],
        ]);
    }

    protected function humanLabel(string $status): string
    {
        return match ($status) {
            'payment_received' => 'Payment received - awaiting verification',
            'verified' => 'Payment verified - preparing your access',
            'product_access_confirmed' => 'Complete - you should have access to your purchase',
            default => 'Unknown status',
        };
    }
}