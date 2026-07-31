<?php

return [

    /*
    |--------------------------------------------------------------------
    | Paystack Webhook Secret
    |--------------------------------------------------------------------
    | Paystack signs every webhook it sends you with your secret key,
    | so you can PROVE a request actually came from Paystack and not
    | an attacker pretending to be Paystack. We read this from .env —
    | it must NEVER be hardcoded or committed to git.
    */
    'paystack_secret_key' => env('PAYSTACK_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------
    | Tracking Token Length
    |--------------------------------------------------------------------
    | Length (in bytes, before hex-encoding) of the public tracking
    | token we generate. 16 bytes = 32 hex characters. This is what
    | the public sees and types in — NEVER the raw Paystack reference.
    | Longer = harder to brute-force guess. 16 bytes is already far
    | beyond realistically guessable.
    */
    'token_bytes' => 16,

    /*
    |--------------------------------------------------------------------
    | Public Lookup Rate Limit
    |--------------------------------------------------------------------
    | How many tracker lookups a single IP can make per minute.
    | This stops someone from scripting thousands of guesses against
    | the public endpoint even though tokens are unguessable —
    | defense in depth, not reliance on one protection alone.
    */
    'lookup_rate_limit_per_minute' => 20,

    /*
    |--------------------------------------------------------------------
    | Internal API Secret
    |--------------------------------------------------------------------
    | A SEPARATE secret from the Paystack key, used to authenticate
    | calls to our internal "confirm product access" endpoint. This
    | is NOT the Paystack secret - reusing that key here would mean
    | anything that could forge a Paystack signature (which it can't,
    | but hypothetically) could also fake product access confirmation.
    | Keeping these separate limits the blast radius of any one leak.
    */
    'internal_api_secret' => env('PAYMENT_TRACKER_INTERNAL_SECRET'),

];