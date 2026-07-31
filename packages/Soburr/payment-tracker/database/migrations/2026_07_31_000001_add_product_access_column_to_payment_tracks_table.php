<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_tracks', function (Blueprint $table) {
            // The buyer-relevant final stage: did they actually get
            // access to what they paid for. This is what a BUYER cares
            // about seeing - not payout timing, which is the creator's
            // concern and already handled by Selar's own dashboard.
            $table->timestamp('product_access_confirmed_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_tracks', function (Blueprint $table) {
            $table->dropColumn('product_access_confirmed_at');
        });
    }
};