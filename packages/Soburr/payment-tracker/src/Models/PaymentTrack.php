<?php

namespace Soburr\PaymentTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Soburr\PaymentTracker\Exceptions\InvalidStatusTransitionException;
use Soburr\PaymentTracker\Services\PaymentStatusMachine;

class PaymentTrack extends Model
{
    /**
     * Which columns are allowed to be set via create()/fill().
     * This is a deliberate security allowlist — Laravel calls this
     * "mass assignment protection." Without it, if any code (or a
     * future bug) accidentally passed extra unexpected fields into
     * create(), they could silently get written to the database.
     * Listing exactly what's allowed here closes that door.
     */
    protected $fillable = [
        'tracking_token',
        'paystack_reference',
        'status',
        'amount_kobo',
        'currency',
        'verified_at',
        'payout_scheduled_at',
        'payout_sent_at',
    ];

    /**
     * Tell Laravel these columns are actual datetime values, not
     * plain strings, so it converts them automatically when we
     * read/write them (e.g. into proper Carbon date objects).
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'product_access_confirmed_at' => 'datetime',
        'payout_scheduled_at' => 'datetime',
        'payout_sent_at' => 'datetime',
    ];

    /**
     * Generates a random, unguessable public tracking token.
     * This is what gets shown to buyers/creators — never the real
     * Paystack reference.
     */
    public static function generateTrackingToken(): string
    {
        $bytes = config('payment-tracker.token_bytes', 16);

        // random_bytes() is PHP's cryptographically secure random
        // generator — NOT the same as rand() or mt_rand(), which
        // are predictable enough to be guessed by an attacker given
        // enough attempts. For anything security-relevant (like a
        // public token that guards access to payment info), we must
        // use a cryptographically secure source.
        return bin2hex(random_bytes($bytes));
    }

    /**
     * The ONLY sanctioned way to change this payment's status.
     * Anything trying to change status by any other means (directly
     * setting $track->status = ... and saving) is bypassing our
     * safety rules and should never be done elsewhere in the codebase.
     *
     * @throws InvalidStatusTransitionException if the move isn't legal
     */
    public function transitionTo(string $newStatus): self
    {
        if (! PaymentStatusMachine::canTransition($this->status, $newStatus)) {
            throw InvalidStatusTransitionException::make($this->status, $newStatus);
        }

        $this->status = $newStatus;

        // Automatically stamp the matching timestamp column, so we
        // always know exactly WHEN each stage happened - not just
        // that it happened. This keeps the "when" and the "what"
        // impossible to get out of sync with each other, since
        // they're set together, in the same method, every time.
        match ($newStatus) {
            'verified' => $this->verified_at = now(),
            'product_access_confirmed' => $this->product_access_confirmed_at = now(),
            default => null,
        };

        $this->save();

        return $this;
    }
}