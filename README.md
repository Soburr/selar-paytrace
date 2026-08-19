# Payment Tracker

A Laravel package that gives buyers on Paystack-powered platforms a self-service way to check their payment status — signature-verified webhook ingestion, independent Paystack re-confirmation, an enforced status state machine, and a public token-based tracking endpoint.

**Built with:** Laravel · Vue 3 + Inertia.js (optional, for the included tracker page) · Paystack API

Latest Version on Packagist · Total Downloads · License

## Requirements

- PHP 8.1+
- Laravel 10 through 13
- A Paystack account (test or live secret key)

## Installation

```bash
composer require soburr/payment-tracker
php artisan migrate
```

Routes (webhook receiver, internal confirm-access, public tracking API) are registered automatically — no manual route setup needed.

Add your credentials to `.env`:

```env
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxx
PAYMENT_TRACKER_INTERNAL_SECRET=your-generated-secret
```

Generate a strong internal secret:

```bash
php artisan tinker
>>> bin2hex(random_bytes(32))
```

One manual step: add the webhook and confirm-access routes to your CSRF exceptions in `bootstrap/app.php` (a package can't safely modify your app's security config for you):

```php
$middleware->validateCsrfTokens(except: [
    'webhooks/paystack',
    'api/payment-tracks/*/confirm-access',
]);
```

### Optional: the Vue/Inertia tracker page

The package includes a demo Vue 3 + Inertia component for the buyer-facing tracker page. It requires `inertiajs/inertia-laravel` in your app, and is not installed automatically (the package doesn't assume your frontend stack).

```bash
php artisan vendor:publish --tag="payment-tracker-views"
```

This copies `track.vue` into your own `resources/js/pages/`. Then register the page route in your own `routes/web.php`:

```php
Route::get('/track', fn () => \Inertia\Inertia::render('track'));
```

## Webhook setup

Point your Paystack webhook (Dashboard → Settings → API Keys & Webhooks) at:

```
https://yourapp.com/webhooks/paystack
```

Every incoming request is verified against Paystack's HMAC-SHA512 signature before anything is processed. Unsigned or mismatched requests are rejected with a 401 and never touch the database.

## How a payment moves through the system

```
payment_received → verified → product_access_confirmed
```

Transitions are enforced — you can't skip a stage or move backward:

```php
use Soburr\PaymentTracker\Models\PaymentTrack;

$track = PaymentTrack::where('paystack_reference', $reference)->first();

$track->transitionTo('verified'); // succeeds if legal
$track->transitionTo('payment_received'); // throws InvalidStatusTransitionException
```

`verified` is set automatically — on receiving a `charge.success` webhook, the package independently calls Paystack's own Verify Transaction API before advancing the status. The webhook payload alone is never trusted as sufficient proof.

`product_access_confirmed` is intentionally left to the host application, since only your system knows whether a buyer actually received their product. Report it via the internal endpoint:

```bash
curl -X POST https://yourapp.com/api/payment-tracks/{tracking_token}/confirm-access \
  -H "x-internal-secret: your-configured-secret"
```

## Looking up a payment's status

Buyers never see the real Paystack reference. A separate, cryptographically random token (`random_bytes`, hex-encoded) is generated per payment and is the only identifier ever exposed publicly:

```php
PaymentTrack::generateTrackingToken(); // e.g. "22076282d5a1ea43eb3e95ce74f22d38"
```

Public lookup, rate-limited per IP:

```bash
curl https://yourapp.com/track/{tracking_token}
```

```json
{
  "tracking_token": "22076282d5a1ea43eb3e95ce74f22d38",
  "status": "verified",
  "status_label": "Payment verified - preparing your access",
  "is_delayed": false,
  "amount": 5000,
  "currency": "NGN",
  "timeline": {
    "payment_received_at": "2026-07-30T19:19:53Z",
    "verified_at": "2026-07-30T19:32:10Z",
    "product_access_confirmed_at": null
  }
}
```

A Vue/Inertia tracker page ships with the package at `/track` — a buyer pastes their token and sees the same data rendered as a status timeline.

## Configuration reference

Published to `config/payment-tracker.php`:

| Key | Default | Description |
|---|---|---|
| `paystack_secret_key` | `env('PAYSTACK_SECRET_KEY')` | Used for both webhook signature verification and the Verify API call |
| `token_bytes` | `16` | Byte length of the generated public tracking token before hex-encoding |
| `lookup_rate_limit_per_minute` | `20` | Per-IP throttle on the public `/track/{token}` endpoint |
| `internal_api_secret` | `env('PAYMENT_TRACKER_INTERNAL_SECRET')` | Separate from the Paystack key — authenticates the confirm-access endpoint |

## Idempotency

Paystack retries webhook delivery; the same `charge.success` event can arrive more than once. Duplicate references are detected before insert (and caught at the database's unique-constraint level as a second guard against race conditions) and return the existing tracking token instead of erroring.

## Security notes

- Webhook signatures are compared with `hash_equals()`, not `===`, to avoid timing-attack leakage.
- The internal confirm-access secret is separate from the Paystack secret, so a leak of one doesn't compromise the other.
- The public tracking token is never derived from or reversible to the real Paystack reference.
- All failure modes default to rejecting/doing nothing rather than guessing (fail closed) — a missing config value, a failed HTTP call to Paystack, or an ambiguous payload never results in an assumed success.

## License

MIT
