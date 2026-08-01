# Payment Tracker for Selar

**A self-service payment status tracker for buyers on Paystack-powered marketplaces — so buyers can check where their money is without emailing support.**

## The problem this solves

When a buyer pays on a platform like Selar, the money doesn't land instantly — it goes through verification and processing. Today, Selar already gives **creators** a way to see this (their "Upcoming Payouts" dashboard). But **buyers have no equivalent**. If a buyer wants to know whether their payment went through, Selar's own support documentation currently tells them to email support with a screenshot and their payment details, and wait for a manual check.

This package closes that specific gap: a public, no-login, reference-based tracking page — the same idea as a courier tracking page, but for a payment — that a buyer can check themselves, immediately, at any time.

**What this package does not do:** it does not replace or duplicate Selar's existing creator payout dashboard, and it does not touch Selar's checkout or payment processing itself. It listens to Paystack webhooks and exposes a tracking layer on top.

## How it works

```
Buyer pays on Selar
        ↓
Paystack processes the charge
        ↓
Paystack sends a webhook → this package verifies the signature,
                             saves the payment, and independently
                             re-confirms it via Paystack's own Verify API
        ↓
A random, unguessable tracking token is generated
        ↓
(Selar includes this token/link in their existing receipt email)
        ↓
Buyer visits /track and enters the token → sees real-time status
```

Three tracked stages: **Payment received → Verified → Access confirmed.** The last stage is intentionally reported by Selar's own system (via an authenticated internal endpoint), since only Selar's backend actually knows whether a buyer received their product.

## Installation

```bash
composer require soburr/payment-tracker
php artisan migrate
```

## Configuration

Add to your `.env`:

```
PAYSTACK_SECRET_KEY=sk_live_or_test_xxxxxxxxxxxx
PAYMENT_TRACKER_INTERNAL_SECRET=a-long-random-string-see-below
```

Generate a strong random value for the internal secret:
```bash
php artisan tinker
>>> bin2hex(random_bytes(32))
```

Publish the config file if you want to customize token length or rate limits:
```bash
php artisan vendor:publish --tag=payment-tracker-config
```

## Setting up the Paystack webhook

In your Paystack Dashboard → Settings → API Keys & Webhooks, set your webhook URL to:
```
https://yourapp.com/webhooks/paystack
```

## Integration: connecting this to Selar's actual buyer flow

This package handles tracking. Two small integration points are needed on the host application's side to make it buyer-facing:

**1. Surface the tracking token to the buyer.** After a `charge.success` webhook is processed, the generated token is available on the `PaymentTrack` model. Include a link like `https://yourapp.com/track/{tracking_token}` in your existing payment receipt email.

**2. Report product access confirmation.** When your own system confirms a buyer has received their product/download, call:

```bash
curl -X POST https://yourapp.com/api/payment-tracks/{tracking_token}/confirm-access \
  -H "x-internal-secret: your-configured-secret"
```

## Security design

- **Webhook signature verification** (HMAC-SHA512, constant-time comparison) — rejects any request not genuinely signed by Paystack.
- **Independent verification** — beyond trusting the webhook payload, every payment is independently re-confirmed against Paystack's own Verify API before being marked `verified`.
- **Opaque public tokens** — the real Paystack transaction reference is never exposed publicly. A separate, cryptographically random token is what buyers ever see or use.
- **Enforced state machine** — payment status can only move through legal transitions (`payment_received → verified → product_access_confirmed`); illegal transitions throw and are blocked at the database layer.
- **Idempotent webhook handling** — duplicate Paystack deliveries (a normal, expected occurrence) are handled gracefully, not treated as errors.
- **Rate-limited public lookups** — the public tracking endpoint is throttled per-IP, independent of token unguessability, as defense in depth.
- **Separated secrets** — the Paystack webhook secret and the internal confirm-access secret are entirely separate, limiting the impact of either one leaking.

## Known limitations / roadmap

Being upfront about what's not yet handled:

- `charge.failed` events are not currently processed — a genuinely failed payment does not yet get its own tracked status.
- Independent Paystack verification currently runs synchronously inside the webhook response. Moving this to a queued job would be a safer production pattern for high webhook volume.
- A payment stuck at `payment_received` for over 15 minutes is flagged as delayed in the API response, but no active alerting/notification exists yet for genuinely stuck payments.

## License

MIT