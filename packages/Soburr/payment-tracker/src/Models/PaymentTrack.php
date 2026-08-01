<?php

namespace Soburr\PaymentTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Soburr\PaymentTracker\Exceptions\InvalidStatusTransitionException;
use Soburr\PaymentTracker\Services\PaymentStatusMachine;

class PaymentTrack extends Model
{
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

    protected $casts = [
        'verified_at' => 'datetime',
        'product_access_confirmed_at' => 'datetime',
        'payout_scheduled_at' => 'datetime',
        'payout_sent_at' => 'datetime',
    ];

    public static function generateTrackingToken(): string
    {
        $bytes = config('payment-tracker.token_bytes', 16);

        return bin2hex(random_bytes($bytes));
    }

    public function transitionTo(string $newStatus): self
    {
        if (! PaymentStatusMachine::canTransition($this->status, $newStatus)) {
            throw InvalidStatusTransitionException::make($this->status, $newStatus);
        }

        $this->status = $newStatus;

        match ($newStatus) {
            'verified' => $this->verified_at = now(),
            'product_access_confirmed' => $this->product_access_confirmed_at = now(),
            default => null,
        };

        $this->save();

        return $this;
    }
}