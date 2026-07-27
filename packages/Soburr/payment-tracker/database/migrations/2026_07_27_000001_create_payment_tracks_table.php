<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_tracks', function (Blueprint $table) {
            $table->id();

            // The public-facing identifier. Random, unguessable, unique.
            // This — NOT the paystack_reference below — is what gets
            // shown to buyers/creators and put in tracking URLs.
            $table->string('tracking_token', 64)->unique();

            // The real Paystack reference. Stored, but never rendered
            // in any public-facing response. Only our own webhook
            // handler and internal admin tools should ever read this.
            $table->string('paystack_reference')->unique();

            // The state machine. We constrain this to a fixed set of
            // values at the application layer (Milestone 3) rather
            // than trusting any input to set it directly.
            $table->string('status')->default('payment_received');

            // Minimal amount info for display — no full card details,
            // no customer PII beyond what's needed for the receipt.
            $table->unsignedBigInteger('amount_kobo');
            $table->string('currency', 3)->default('NGN');

            // Nullable because these happen later in the lifecycle.
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('payout_scheduled_at')->nullable();
            $table->timestamp('payout_sent_at')->nullable();

            $table->timestamps();

            // Index for fast lookups on the public tracker endpoint.
            $table->index('tracking_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_tracks');
    }
};