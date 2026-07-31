<?php

namespace Soburr\PaymentTracker\Services;

class PaymentStatusMachine
{
    /**
     * The complete, fixed list of valid states. Nothing outside this
     * list should ever be allowed to be saved as a status.
     */
    public const STATES = [
        'payment_received',
        'verified',
        'product_access_confirmed',
    ];

    /**
     * The map of legal transitions. Each key is a "from" state, and
     * its value is the list of states you're allowed to move TO from
     * there. If a state isn't a key here, it has no legal next step
     * (it's a final/terminal state).
     */
    public const TRANSITIONS = [
        'payment_received' => ['verified'],
        'verified' => ['product_access_confirmed'],
        // 'product_access_confirmed' has no entry - it's terminal,
        // nothing can transition FROM it.
    ];

    /**
     * The single question this whole class exists to answer:
     * is moving from $from to $to a legal transition?
     */
    public static function canTransition(string $from, string $to): bool
    {
        // Guard against typos or unknown states entirely - if either
        // side isn't even a real state we recognize, this can't be
        // a legal transition, full stop.
        if (! in_array($from, self::STATES, true) || ! in_array($to, self::STATES, true)) {
            return false;
        }

        // Look up what $from is allowed to move to. If $from isn't
        // even a key in TRANSITIONS, it has no legal next states
        // (it's terminal) - default to an empty array in that case.
        $allowedNextStates = self::TRANSITIONS[$from] ?? [];

        return in_array($to, $allowedNextStates, true);
    }
}