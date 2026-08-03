<?php

namespace Soburr\PaymentTracker\Services;

class PaymentStatusMachine
{

    public const STATES = [
        'payment_received',
        'verified',
        'product_access_confirmed',
    ];

    public const TRANSITIONS = [
        'payment_received' => ['verified'],
        'verified' => ['product_access_confirmed'],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        if (! in_array($from, self::STATES, true) || ! in_array($to, self::STATES, true)) {
            return false;
        }

        $allowedNextStates = self::TRANSITIONS[$from] ?? [];

        return in_array($to, $allowedNextStates, true);
    }
}