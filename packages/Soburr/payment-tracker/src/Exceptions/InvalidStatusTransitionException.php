<?php

namespace Soburr\PaymentTracker\Exceptions;

class InvalidStatusTransitionException extends \Exception
{
    public static function make(string $from, string $to): self
    {
        return new self("Cannot transition payment status from '{$from}' to '{$to}'. This is not a legal transition.");
    }
}