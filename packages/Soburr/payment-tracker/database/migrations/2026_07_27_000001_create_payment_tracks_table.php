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
            $table->string('tracking_token', 64)->unique();
            $table->string('paystack_reference')->unique();
            $table->string('status')->default('payment_received');
            $table->unsignedBigInteger('amount_kobo');
            $table->string('currency', 3)->default('NGN');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('payout_scheduled_at')->nullable();
            $table->timestamp('payout_sent_at')->nullable();

            $table->timestamps();

            $table->index('tracking_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_tracks');
    }
};