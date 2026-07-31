<?php

namespace Soburr\PaymentTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalSecret
{
    /**
     * Blocks any request that doesn't present the correct internal
     * secret in the 'x-internal-secret' header. Runs BEFORE the
     * controller even executes - the controller's code never runs
     * at all for an unauthenticated request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('payment-tracker.internal_api_secret');
        $provided = $request->header('x-internal-secret');

        // Same reasoning as our Paystack signature check: if we don't
        // even have a secret configured, fail closed - never treat a
        // missing configuration as "allow everything."
        if (empty($expected) || empty($provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // hash_equals() again - same timing-attack protection reasoning
        // as the Paystack signature check in Step 2.
        if (! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}